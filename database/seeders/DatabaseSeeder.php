<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\AppSetting;
use App\Models\Category;
use App\Models\PriceHistory;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Store;
use App\Models\User;
use App\Services\BarcodeService;
use App\Services\InventoryService;
use App\Services\SkuGeneratorService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database with initial production-oriented dataset.
     */
    public function run(): void
    {
        // 1. Super Admin Account
        $superAdmin = User::create([
            'name' => 'Super Administrator',
            'email' => 'admin@clothingpos.com',
            'phone' => '+8801700000001',
            'password' => Hash::make('Admin@2026!Pos'),
            'role' => UserRole::SUPER_ADMIN,
            'is_active' => true,
            'locale' => 'en',
            'theme' => 'light',
        ]);

        // 2. Central Warehouse
        $centralWarehouse = Store::create([
            'name' => 'Central Logistics Warehouse',
            'code' => 'WH-001',
            'address' => 'Plot 42, Tejgaon Industrial Area, Dhaka-1208',
            'phone' => '+8801700000100',
            'email' => 'warehouse@clothingpos.com',
            'opening_time' => '08:00:00',
            'closing_time' => '20:00:00',
            'is_warehouse' => true,
            'is_active' => true,
        ]);

        // 3. Retail Stores (6 Stores across Bangladesh)
        $storeData = [
            ['name' => 'Dhanmondi Flagship Store', 'code' => 'STR-001', 'address' => 'House 12, Road 27 (Old), Dhanmondi, Dhaka', 'phone' => '+8801700000101'],
            ['name' => 'Uttara Branch', 'code' => 'STR-002', 'address' => 'Sector 7, Jasimuddin Avenue, Uttara, Dhaka', 'phone' => '+8801700000102'],
            ['name' => 'Mirpur Outlet', 'code' => 'STR-003', 'address' => 'Plot 10, Block D, Mirpur-1, Dhaka', 'phone' => '+8801700000103'],
            ['name' => 'GEC Circle Branch (Chattogram)', 'code' => 'STR-004', 'address' => 'GEC Circle, CDA Avenue, Chattogram', 'phone' => '+8801700000104'],
            ['name' => 'Zindabazar Outlet (Sylhet)', 'code' => 'STR-005', 'address' => 'Zindabazar Point, East Zindabazar, Sylhet', 'phone' => '+8801700000105'],
            ['name' => 'Shaheb Bazar Branch (Rajshahi)', 'code' => 'STR-006', 'address' => 'Shaheb Bazar Zero Point, Rajshahi', 'phone' => '+8801700000106'],
        ];

        $createdStores = [];
        foreach ($storeData as $s) {
            $createdStores[] = Store::create([
                'name' => $s['name'],
                'code' => $s['code'],
                'address' => $s['address'],
                'phone' => $s['phone'],
                'email' => strtolower(str_replace([' ', '(', ')'], '', $s['code'])) . '@clothingpos.com',
                'opening_time' => '10:00:00',
                'closing_time' => '22:00:00',
                'is_warehouse' => false,
                'is_active' => true,
            ]);
        }

        // 4. Warehouse Staff
        $whStaff = User::create([
            'name' => 'Rafiqul Islam',
            'email' => 'rafiq@clothingpos.com',
            'phone' => '+8801700000002',
            'password' => Hash::make('Warehouse@2026!'),
            'role' => UserRole::WAREHOUSE_STAFF,
            'store_id' => $centralWarehouse->id,
            'is_active' => true,
        ]);

        // 5. Store Managers & Cashiers for each store
        $dhanmondiStore = $createdStores[0];
        $dhanmondiManager = User::create([
            'name' => 'Tanvir Ahmed',
            'email' => 'manager.dhanmondi@clothingpos.com',
            'phone' => '+8801700000003',
            'password' => Hash::make('Manager@2026!'),
            'role' => UserRole::STORE_MANAGER,
            'store_id' => $dhanmondiStore->id,
            'is_active' => true,
        ]);
        $dhanmondiStore->update(['manager_id' => $dhanmondiManager->id]);

        $dhanmondiCashier = User::create([
            'name' => 'Fatima Begum',
            'email' => 'cashier.dhanmondi@clothingpos.com',
            'phone' => '+8801700000004',
            'password' => Hash::make('Cashier@2026!'),
            'role' => UserRole::CASHIER,
            'store_id' => $dhanmondiStore->id,
            'is_active' => true,
        ]);

        // Uttara Manager
        $uttaraStore = $createdStores[1];
        $uttaraManager = User::create([
            'name' => 'Kamrul Hasan',
            'email' => 'manager.uttara@clothingpos.com',
            'phone' => '+8801700000005',
            'password' => Hash::make('Manager@2026!'),
            'role' => UserRole::STORE_MANAGER,
            'store_id' => $uttaraStore->id,
            'is_active' => true,
        ]);
        $uttaraStore->update(['manager_id' => $uttaraManager->id]);

        // 6. Accountant Account
        $accountant = User::create([
            'name' => 'Mahmudul Karim (CPA)',
            'email' => 'accountant@clothingpos.com',
            'phone' => '+8801700000006',
            'password' => Hash::make('Accounts@2026!'),
            'role' => UserRole::ACCOUNTANT,
            'is_active' => true,
        ]);

        // 7. Categories & Subcategories
        $categoriesData = [
            [
                'name' => "Men's Formal & Casual Shirts",
                'code' => 'SH',
                'description' => 'Cotton, Linen, and blended shirts',
                'subs' => ['Cotton Casual', 'Formal Executive', 'Denim Shirts'],
            ],
            [
                'name' => 'Panjabi & Traditional Wear',
                'code' => 'PJ',
                'description' => 'Eid and celebration Panjabi collection',
                'subs' => ['Semi-fitted Cotton', 'Silk Kabli', 'Embroidery Special'],
            ],
            [
                'name' => 'Trousers & Chinos',
                'code' => 'PT',
                'description' => 'Formal trousers, stretch chinos, and denim',
                'subs' => ['Slim Fit Chino', 'Executive Formal Pant', 'Comfort Denim'],
            ],
            [
                'name' => 'Polo & Casual T-Shirts',
                'code' => 'TS',
                'description' => 'Pique cotton polos and crew-neck tees',
                'subs' => ['Pique Polo', 'Organic Crewneck', 'Graphic T-Shirt'],
            ],
            [
                'name' => "Women's Sarees & Kurtis",
                'code' => 'SR',
                'description' => 'Jamdani, Silk, Cotton Sarees and designer kurtis',
                'subs' => ['Jamdani Silk', 'Daily Cotton Kurti', 'Georgette Partywear'],
            ],
        ];

        $allCategories = [];
        foreach ($categoriesData as $c) {
            $parent = Category::create([
                'name' => $c['name'],
                'code' => $c['code'],
                'description' => $c['description'],
                'is_active' => true,
            ]);

            $allCategories[$c['code']] = $parent;

            foreach ($c['subs'] as $subIdx => $subName) {
                Category::create([
                    'name' => $subName,
                    'code' => $c['code'] . ($subIdx + 1),
                    'parent_id' => $parent->id,
                    'is_active' => true,
                ]);
            }
        }

        // 8. Sample Products & Variants
        $sampleProducts = [
            [
                'name' => 'Royal Oxford Cotton Shirt',
                'cat_code' => 'SH',
                'brand' => 'Apex Attire',
                'season' => 'Summer 2026',
                'fabric' => '100% Egyptian Cotton',
                'cost' => 850.00,
                'price' => 1750.00,
                'variants' => [
                    ['size' => 'M', 'color' => 'Classic Navy'],
                    ['size' => 'L', 'color' => 'Classic Navy'],
                    ['size' => 'XL', 'color' => 'Classic Navy'],
                    ['size' => 'M', 'color' => 'Crisp White'],
                    ['size' => 'L', 'color' => 'Crisp White'],
                ],
            ],
            [
                'name' => 'Festive Silk Blend Panjabi',
                'cat_code' => 'PJ',
                'brand' => 'Heritage Bengal',
                'season' => 'Eid Collection 2026',
                'fabric' => 'Silk & Linen Blend',
                'cost' => 1400.00,
                'price' => 3200.00,
                'variants' => [
                    ['size' => '40', 'color' => 'Maroon Gold'],
                    ['size' => '42', 'color' => 'Maroon Gold'],
                    ['size' => '44', 'color' => 'Maroon Gold'],
                    ['size' => '40', 'color' => 'Midnight Blue'],
                    ['size' => '42', 'color' => 'Midnight Blue'],
                ],
            ],
            [
                'name' => 'Premium Stretch Chino Trouser',
                'cat_code' => 'PT',
                'brand' => 'Urban Fit',
                'season' => 'All Season',
                'fabric' => '98% Cotton 2% Spandex',
                'cost' => 950.00,
                'price' => 2150.00,
                'variants' => [
                    ['size' => '32', 'color' => 'Khaki Beige'],
                    ['size' => '34', 'color' => 'Khaki Beige'],
                    ['size' => '36', 'color' => 'Khaki Beige'],
                    ['size' => '32', 'color' => 'Charcoal Grey'],
                    ['size' => '34', 'color' => 'Charcoal Grey'],
                ],
            ],
            [
                'name' => 'Pique Mercerized Polo Shirt',
                'cat_code' => 'TS',
                'brand' => 'Apex Attire',
                'season' => 'Summer 2026',
                'fabric' => 'Mercerized Combed Cotton',
                'cost' => 600.00,
                'price' => 1350.00,
                'variants' => [
                    ['size' => 'M', 'color' => 'Emerald Green'],
                    ['size' => 'L', 'color' => 'Emerald Green'],
                    ['size' => 'M', 'color' => 'Crimson Red'],
                    ['size' => 'L', 'color' => 'Crimson Red'],
                ],
            ],
        ];

        foreach ($sampleProducts as $pData) {
            $cat = $allCategories[$pData['cat_code']];
            $sku = SkuGeneratorService::generate($cat);

            $product = Product::create([
                'name' => $pData['name'],
                'sku' => $sku,
                'category_id' => $cat->id,
                'brand' => $pData['brand'],
                'season' => $pData['season'],
                'fabric' => $pData['fabric'],
                'base_purchase_price' => $pData['cost'],
                'base_selling_price' => $pData['price'],
                'is_active' => true,
            ]);

            PriceHistory::create([
                'product_id' => $product->id,
                'old_price' => 0.00,
                'new_price' => $product->base_selling_price,
                'reason' => 'Seeded catalog initial pricing',
                'user_id' => $superAdmin->id,
                'created_at' => now(),
            ]);

            foreach ($pData['variants'] as $v) {
                $barcode = BarcodeService::generateUniqueBarcode();

                $variant = ProductVariant::create([
                    'product_id' => $product->id,
                    'size' => $v['size'],
                    'color' => $v['color'],
                    'barcode' => $barcode,
                    'is_active' => true,
                ]);

                // 9. Initial Opening Balances in Central Warehouse and Dhanmondi Store
                InventoryService::setOpeningStock(
                    variantId: $variant->id,
                    storeId: $centralWarehouse->id,
                    quantity: 50,
                    unitCost: (float) $product->base_purchase_price,
                    notes: 'Seeded initial central warehouse stock',
                    user: $superAdmin
                );

                InventoryService::setOpeningStock(
                    variantId: $variant->id,
                    storeId: $dhanmondiStore->id,
                    quantity: 15,
                    unitCost: (float) $product->base_purchase_price,
                    notes: 'Seeded Dhanmondi initial store stock',
                    user: $superAdmin
                );
            }
        }

        // 10. Default System Settings & Branding
        AppSetting::set('vat_enabled', '0', 'tax');
        AppSetting::set('vat_rate', '0.00', 'tax');
        AppSetting::set('vat_label', 'Tax / VAT', 'tax');
        AppSetting::set('app_name', 'SR Fashion', 'branding');
        AppSetting::set('app_tagline', 'Smart Retail POS & ERP System', 'branding');
        AppSetting::set('company_address', 'Dhaka gazipur chondra, pollibudut, kaliakor, modina garments', 'branding');
        AppSetting::set('company_phone', '01310997902', 'branding');
        AppSetting::set('company_email', 'sojolstudent66@gmail.com', 'branding');
        AppSetting::set('receipt_footer', 'Thank you for shopping with us!', 'branding');
        AppSetting::set('app_logo', 'branding/vTaThSwQnvXxYivSd1AokiRtwXPzJsxJSfhGVV1v.png', 'branding');
    }
}
