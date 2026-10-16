<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Customer;
use App\Models\DataImport;
use App\Models\Employee;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\StockBalance;
use App\Models\Store;
use App\Models\Supplier;
use App\Models\User;
use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DataImportService
{
    public function __construct(
        protected AuditService $auditService
    ) {}

    /**
     * Upload and parse import file, performing dry-run row-level validation.
     */
    public function parseAndValidate(
        UploadedFile $file,
        string $datasetType,
        User $user,
        ?string $idempotencyKey = null
    ): DataImport {
        $allowedTypes = ['products', 'opening_stock', 'suppliers', 'customers', 'employees'];
        if (!in_array($datasetType, $allowedTypes)) {
            throw new Exception("Unsupported dataset type: {$datasetType}. Allowed: " . implode(', ', $allowedTypes));
        }

        $idemKey = $idempotencyKey ?: Str::uuid()->toString();
        $existing = DataImport::where('idempotency_key', $idemKey)->first();
        if ($existing) {
            return $existing;
        }

        $storedPath = $file->storeAs('imports', 'import_' . time() . '_' . Str::random(8) . '.csv', 'local');
        $fullPath = Storage::disk('local')->path($storedPath);

        $handle = fopen($fullPath, 'r');
        if (!$handle) {
            throw new Exception("Unable to open uploaded file for parsing.");
        }

        // Read headers
        $bom = fread($handle, 3);
        if ($bom !== "\xEF\xBB\xBF") {
            rewind($handle);
        }

        $headers = fgetcsv($handle);
        if (!$headers) {
            fclose($handle);
            throw new Exception("Uploaded CSV file is empty or missing headers.");
        }

        $headers = array_map(fn ($h) => strtolower(trim($h)), $headers);

        $rowNumber = 1;
        $totalRows = 0;
        $validRows = 0;
        $errors = [];

        while (($row = fgetcsv($handle)) !== false) {
            $rowNumber++;
            if (empty(array_filter($row))) {
                continue; // Skip blank rows
            }

            $totalRows++;
            $rowData = array_combine($headers, array_slice(array_pad($row, count($headers), null), 0, count($headers)));

            $rowError = $this->validateRow($datasetType, $rowData, $rowNumber);
            if ($rowError) {
                $errors[] = [
                    'row' => $rowNumber,
                    'error' => $rowError,
                    'data' => $rowData,
                ];
            } else {
                $validRows++;
            }
        }
        fclose($handle);

        $batchId = SequenceService::generate('IMP', 'HQ');

        $import = DataImport::create([
            'import_batch_id' => $batchId,
            'dataset_type' => $datasetType,
            'file_name' => $file->getClientOriginalName(),
            'file_path' => $storedPath,
            'status' => count($errors) > 0 ? 'validated_with_warnings' : 'validated',
            'total_rows' => $totalRows,
            'successful_rows' => $validRows,
            'failed_rows' => count($errors),
            'validation_errors' => $errors,
            'idempotency_key' => $idemKey,
            'created_by' => $user->id,
        ]);

        AuditService::log(
            action: 'data_import.parsed',
            auditable: $import,
            oldValues: null,
            newValues: ['batch_id' => $batchId, 'total' => $totalRows, 'errors' => count($errors)],
            reason: "Uploaded and validated {$datasetType} import file {$import->file_name}"
        );

        return $import;
    }

    /**
     * Validate an individual row based on dataset schema.
     */
    protected function validateRow(string $type, array $row, int $rowNum): ?string
    {
        switch ($type) {
            case 'suppliers':
                if (empty($row['name'])) return "Missing required supplier name.";
                if (empty($row['phone'])) return "Missing required supplier phone.";
                break;

            case 'customers':
                if (empty($row['phone'])) return "Missing required customer phone number.";
                break;

            case 'products':
                if (empty($row['product_name'])) return "Missing product name.";
                if (empty($row['sku'])) return "Missing product SKU.";
                if (empty($row['size'])) return "Missing variant size.";
                if (empty($row['color'])) return "Missing variant color.";
                if (empty($row['barcode'])) return "Missing variant barcode.";
                if (!isset($row['selling_price']) || $row['selling_price'] === '' || !is_numeric($row['selling_price'])) return "Missing or invalid selling price.";
                break;

            case 'opening_stock':
                if (empty($row['barcode'])) return "Missing product barcode.";
                if (empty($row['store_code'])) return "Missing store code.";
                if (!isset($row['quantity']) || !is_numeric($row['quantity']) || (int)$row['quantity'] < 0) {
                    return "Quantity must be a positive integer.";
                }
                break;

            case 'employees':
                if (empty($row['employee_code'])) return "Missing employee code.";
                if (empty($row['name'])) return "Missing employee name.";
                if (empty($row['designation'])) return "Missing designation.";
                break;
        }

        return null;
    }

    /**
     * Execute confirmed import in database transaction.
     */
    public function executeImport(DataImport $import, User $user): DataImport
    {
        if (in_array($import->status, ['completed', 'confirmed'])) {
            throw new Exception("Import batch {$import->import_batch_id} has already been processed.");
        }

        $fullPath = Storage::disk('local')->path($import->file_path);
        if (!file_exists($fullPath)) {
            throw new Exception("Import source file not found.");
        }

        return DB::transaction(function () use ($import, $user, $fullPath) {
            $handle = fopen($fullPath, 'r');
            $headers = fgetcsv($handle);
            $headers = array_map(fn ($h) => strtolower(trim($h)), $headers);

            $processed = 0;
            $rowNum = 1;
            while (($row = fgetcsv($handle)) !== false) {
                $rowNum++;
                if (empty(array_filter($row))) continue;
                $data = array_combine($headers, array_slice(array_pad($row, count($headers), null), 0, count($headers)));

                // Skip rows that failed validation
                if ($this->validateRow($import->dataset_type, $data, $rowNum) !== null) {
                    continue;
                }

                switch ($import->dataset_type) {
                    case 'suppliers':
                        Supplier::updateOrCreate(
                            ['phone' => $data['phone']],
                            [
                                'name' => $data['name'],
                                'code' => $data['code'] ?? ('SUP-' . Str::upper(Str::random(6))),
                                'address' => $data['address'] ?? null,
                                'is_active' => true,
                            ]
                        );
                        break;

                    case 'customers':
                        Customer::updateOrCreate(
                            ['phone' => $data['phone']],
                            [
                                'name' => $data['name'] ?? 'Walk-in Customer',
                                'email' => $data['email'] ?? null,
                                'address' => $data['address'] ?? null,
                            ]
                        );
                        break;

                    case 'products':
                        $category = Category::firstOrCreate(
                            ['code' => $data['category_code'] ?? 'GEN'],
                            ['name' => $data['category_name'] ?? 'General', 'is_active' => true]
                        );

                        $product = Product::firstOrCreate(
                            ['sku' => $data['sku']],
                            [
                                'name' => $data['product_name'],
                                'category_id' => $category->id,
                                'base_price' => (float) ($data['selling_price'] ?? 0),
                                'is_active' => true,
                            ]
                        );

                        ProductVariant::updateOrCreate(
                            ['barcode' => $data['barcode']],
                            [
                                'product_id' => $product->id,
                                'size' => $data['size'],
                                'color' => $data['color'],
                                'purchase_price' => (float) ($data['purchase_price'] ?? 0),
                                'weighted_average_cost' => (float) ($data['purchase_price'] ?? 0),
                                'selling_price' => (float) ($data['selling_price'] ?? 0),
                                'is_active' => true,
                            ]
                        );
                        break;

                    case 'opening_stock':
                        $store = Store::where('code', $data['store_code'])->first();
                        $variant = ProductVariant::where('barcode', $data['barcode'])->first();

                        if ($store && $variant) {
                            $qty = (int) $data['quantity'];
                            $balance = StockBalance::firstOrCreate(
                                ['store_id' => $store->id, 'product_variant_id' => $variant->id],
                                ['quantity_on_hand' => 0, 'quantity_reserved' => 0, 'damaged_quantity' => 0]
                            );
                            $balance->increment('quantity_on_hand', $qty);
                        }
                        break;

                    case 'employees':
                        $store = Store::where('code', $data['store_code'] ?? '')->first();
                        Employee::updateOrCreate(
                            ['employee_code' => $data['employee_code']],
                            [
                                'store_id' => $store?->id,
                                'name' => $data['name'],
                                'phone' => $data['phone'] ?? null,
                                'email' => $data['email'] ?? null,
                                'designation' => $data['designation'],
                                'joining_date' => $data['joining_date'] ?? now()->format('Y-m-d'),
                                'monthly_salary' => (float) ($data['monthly_salary'] ?? 0),
                                'employment_status' => 'active',
                            ]
                        );
                        break;
                }
                $processed++;
            }
            fclose($handle);

            $import->update([
                'status' => 'completed',
                'confirmed_by' => $user->id,
                'confirmed_at' => now(),
            ]);

            AuditService::log(
                action: 'data_import.executed',
                auditable: $import,
                oldValues: ['status' => 'validated'],
                newValues: ['status' => 'completed', 'processed_rows' => $processed],
                reason: "Confirmed and executed import batch {$import->import_batch_id} ({$processed} rows)"
            );

            return $import;
        });
    }
}
