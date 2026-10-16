<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\DataImport;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use App\Services\ExportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class DataImportExportAndSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected User $cashier;
    protected Store $store;
    protected ExportService $exportService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->store = Store::create(['name' => 'Main Hub', 'code' => 'MAIN-01', 'is_active' => true]);

        $this->superAdmin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@pos.test',
            'password' => bcrypt('password'),
            'role' => 'super_admin',
            'is_active' => true,
        ]);

        $this->cashier = User::create([
            'name' => 'Cashier User',
            'email' => 'cashier@pos.test',
            'password' => bcrypt('password'),
            'role' => 'cashier',
            'store_id' => $this->store->id,
            'is_active' => true,
        ]);

        $this->exportService = app(ExportService::class);
    }

    public function test_formula_injection_prevention_on_csv_export(): void
    {
        // CWE-1236: formulas starting with =,+,-,@,\t,\r must be prefixed with single quote '
        $maliciousData = [
            ['Name', 'FormulaPayload'],
            ['Normal Customer', '=cmd|"/C calc"!A0'],
            ['Another Customer', '+cmd|"/C notepad"!A0'],
            ['Third Customer', '@SUM(1+1)'],
            ['Fourth Customer', "-2+3*cmd|' /C calc'!A0"],
        ];

        $csvStream = $this->exportService->exportCsv($maliciousData, 'test_sanitization.csv');

        ob_start();
        $csvStream->sendContent();
        $content = ob_get_clean();

        // Must prepend single quote
        $this->assertStringContainsString("'=cmd", $content);
        $this->assertStringContainsString("'+cmd", $content);
        $this->assertStringContainsString("'@SUM", $content);
        $this->assertStringContainsString("'-2+3", $content);
    }

    public function test_product_import_preview_and_atomic_confirmation(): void
    {
        $this->actingAs($this->superAdmin);

        Category::create(['name' => 'Men Fashion', 'code' => 'MEN', 'is_active' => true]);

        $csvData = "product_name,category_name,sku,barcode,size,color,cost_price,selling_price\n" .
                   "Casual Linen Shirt,Men Fashion,LIN-WHT-M,894110022331,M,White,650.00,1450.00\n" .
                   "Casual Linen Shirt,Men Fashion,LIN-WHT-L,894110022332,L,White,650.00,1450.00\n" .
                   "Invalid Row Missing Price,Men Fashion,INV-001,894110022333,XL,Blue,500.00,\n"; // Missing selling price

        $file = UploadedFile::fake()->createWithContent('products.csv', $csvData);

        // 1. Upload & Validate
        $uploadResponse = $this->post(route('imports.upload'), [
            'dataset_type' => 'products',
            'file' => $file,
        ]);

        $uploadResponse->assertRedirect();
        
        $importJob = DataImport::latest()->first();
        $this->assertNotNull($importJob);
        $this->assertEquals(3, $importJob->total_rows);
        $this->assertEquals(2, $importJob->successful_rows);
        $this->assertEquals(1, $importJob->failed_rows);
        $this->assertStringContainsString('validated', $importJob->status);

        // 2. Confirm & Commit valid rows
        $confirmResponse = $this->post(route('imports.confirm', $importJob));
        $confirmResponse->assertRedirect(route('imports.index'));

        $this->assertEquals('completed', $importJob->fresh()->status);

        $this->assertDatabaseHas('products', ['name' => 'Casual Linen Shirt']);
        $this->assertDatabaseHas('product_variants', ['barcode' => '894110022331', 'selling_price' => 1450.00]);
        $this->assertDatabaseHas('product_variants', ['barcode' => '894110022332', 'selling_price' => 1450.00]);
        $this->assertDatabaseMissing('product_variants', ['barcode' => '894110022333']);
    }

    public function test_unauthorized_user_cannot_access_import_pipeline(): void
    {
        $this->actingAs($this->cashier);

        $response = $this->get(route('imports.index'));
        $response->assertStatus(403);
    }
}
