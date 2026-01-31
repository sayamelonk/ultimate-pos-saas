<?php

namespace Database\Seeders;

use App\Models\InventoryCategory;
use App\Models\InventoryItem;
use App\Models\Outlet;
use App\Models\Supplier;
use App\Models\Tenant;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Database\Seeder;

class InventoryTestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get or create tenant
        $tenant = Tenant::firstOrCreate(
            ['name' => 'Test Restaurant'],
            [
                'email' => 'test@restaurant.com',
                'phone' => '08123456789',
                'address' => 'Jl. Test No. 123',
                'is_active' => true,
            ]
        );

        // Get or create user
        $user = User::firstOrCreate(
            ['email' => 'admin@test.com'],
            [
                'name' => 'Admin Test',
                'password' => bcrypt('password'),
                'tenant_id' => $tenant->id,
                'outlet_id' => null,
                'role' => 'admin',
            ]
        );

        // Create 2 outlets for multi-outlet testing
        $outletJakarta = Outlet::firstOrCreate(
            ['tenant_id' => $tenant->id, 'name' => 'Jakarta Outlet'],
            [
                'code' => 'JKT',
                'address' => 'Jl. Sudirman No. 1, Jakarta',
                'phone' => '021-1234567',
                'is_active' => true,
            ]
        );

        $outletBandung = Outlet::firstOrCreate(
            ['tenant_id' => $tenant->id, 'name' => 'Bandung Outlet'],
            [
                'code' => 'BDG',
                'address' => 'Jl. Asia Afrika No. 1, Bandung',
                'phone' => '022-1234567',
                'is_active' => true,
            ]
        );

        // Update user outlet
        if (! $user->outlet_id) {
            $user->update(['outlet_id' => $outletJakarta->id]);
        }

        // Create Units
        $units = [
            ['name' => 'Kilogram', 'abbreviation' => 'kg', 'base_unit_id' => null, 'conversion_factor' => null],
            ['name' => 'Gram', 'abbreviation' => 'gr', 'base_unit_id' => null, 'conversion_factor' => null],
            ['name' => 'Piece', 'abbreviation' => 'pcs', 'base_unit_id' => null, 'conversion_factor' => null],
            ['name' => 'Liter', 'abbreviation' => 'L', 'base_unit_id' => null, 'conversion_factor' => null],
            ['name' => 'Milliliter', 'abbreviation' => 'ml', 'base_unit_id' => null, 'conversion_factor' => null],
            ['name' => 'Ton', 'abbreviation' => 'ton', 'base_unit_id' => null, 'conversion_factor' => 1000], // derived unit
        ];

        $createdUnits = [];
        foreach ($units as $unitData) {
            $baseUnitId = $unitData['base_unit_id'];
            if ($baseUnitId && isset($createdUnits['kg'])) {
                $baseUnitId = $createdUnits['kg']->id;
            }

            $unit = Unit::firstOrCreate(
                [
                    'tenant_id' => $tenant->id,
                    'abbreviation' => $unitData['abbreviation'],
                ],
                [
                    'name' => $unitData['name'],
                    'base_unit_id' => $baseUnitId,
                    'conversion_factor' => $unitData['conversion_factor'],
                    'is_active' => true,
                ]
            );
            $createdUnits[$unitData['abbreviation']] = $unit;
        }

        // Create Categories with hierarchy
        $categoryFood = InventoryCategory::firstOrCreate(
            ['tenant_id' => $tenant->id, 'name' => 'Food Ingredients'],
            [
                'description' => 'Raw materials for food preparation',
                'parent_id' => null,
                'is_active' => true,
            ]
        );

        $categoryBeverage = InventoryCategory::firstOrCreate(
            ['tenant_id' => $tenant->id, 'name' => 'Beverages'],
            [
                'description' => 'All beverage ingredients',
                'parent_id' => null,
                'is_active' => true,
            ]
        );

        InventoryCategory::firstOrCreate(
            ['tenant_id' => $tenant->id, 'name' => 'Hot Beverages'],
            [
                'description' => 'Coffee, tea, and other hot drinks',
                'parent_id' => $categoryBeverage->id,
                'is_active' => true,
            ]
        );

        InventoryCategory::firstOrCreate(
            ['tenant_id' => $tenant->id, 'name' => 'Cold Beverages'],
            [
                'description' => 'Cold drinks and juices',
                'parent_id' => $categoryBeverage->id,
                'is_active' => true,
            ]
        );

        // Create Suppliers
        $suppliers = [
            [
                'name' => 'PT Food Supply Indonesia',
                'contact_person' => 'Budi Santoso',
                'email' => 'budi@foodsupply.co.id',
                'phone' => '08123456789',
                'address' => 'Jl. Sudirman No. 123, Jakarta Selatan',
            ],
            [
                'name' => 'CV Minuman Segar',
                'contact_person' => 'Siti Aminah',
                'email' => 'siti@minumansegar.com',
                'phone' => '08198765432',
                'address' => 'Jl. Thamrin No. 45, Jakarta Pusat',
            ],
            [
                'name' => 'UD Bahan Kita',
                'contact_person' => 'Ahmad Wijaya',
                'email' => 'ahmad@bahankita.com',
                'phone' => '08234567890',
                'address' => 'Jl. Gatot Subroto No. 78, Jakarta',
            ],
        ];

        $createdSuppliers = [];
        foreach ($suppliers as $supplierData) {
            $supplier = Supplier::firstOrCreate(
                [
                    'tenant_id' => $tenant->id,
                    'name' => $supplierData['name'],
                ],
                array_merge($supplierData, ['is_active' => true])
            );
            $createdSuppliers[] = $supplier;
        }

        // Create Inventory Items
        $items = [
            [
                'name' => 'Chicken Breast',
                'sku' => 'CHI-001',
                'description' => 'Fresh chicken breast, premium quality',
                'category_id' => $categoryFood->id,
                'unit_id' => $createdUnits['kg']->id,
                'cost_price' => 45000,
                'reorder_point' => 10,
            ],
            [
                'name' => 'Rice',
                'sku' => 'RIC-001',
                'description' => 'Premium white rice, local variety',
                'category_id' => $categoryFood->id,
                'unit_id' => $createdUnits['kg']->id,
                'cost_price' => 15000,
                'reorder_point' => 50,
            ],
            [
                'name' => 'Coffee Beans Arabica',
                'sku' => 'COF-001',
                'description' => 'Premium Arabica coffee beans from Toraja',
                'category_id' => $categoryBeverage->id,
                'unit_id' => $createdUnits['kg']->id,
                'cost_price' => 250000,
                'reorder_point' => 20,
            ],
            [
                'name' => 'Fresh Milk',
                'sku' => 'MLK-001',
                'description' => 'Fresh full cream milk',
                'category_id' => $categoryBeverage->id,
                'unit_id' => $createdUnits['L']->id,
                'cost_price' => 18000,
                'reorder_point' => 15,
            ],
            [
                'name' => 'Sugar',
                'sku' => 'SUG-001',
                'description' => 'Refined white sugar',
                'category_id' => $categoryFood->id,
                'unit_id' => $createdUnits['kg']->id,
                'cost_price' => 16000,
                'reorder_point' => 25,
            ],
            [
                'name' => 'Eggs',
                'sku' => 'EGG-001',
                'description' => 'Fresh chicken eggs, grade A',
                'category_id' => $categoryFood->id,
                'unit_id' => $createdUnits['pcs']->id,
                'cost_price' => 2000,
                'reorder_point' => 100,
            ],
            [
                'name' => 'Cooking Oil',
                'sku' => 'OIL-001',
                'description' => 'Refined cooking oil, 2L bottle',
                'category_id' => $categoryFood->id,
                'unit_id' => $createdUnits['L']->id,
                'cost_price' => 35000,
                'reorder_point' => 10,
            ],
            [
                'name' => 'Tea Leaves',
                'sku' => 'TEA-001',
                'description' => 'Premium black tea leaves',
                'category_id' => $categoryBeverage->id,
                'unit_id' => $createdUnits['kg']->id,
                'cost_price' => 120000,
                'reorder_point' => 10,
            ],
        ];

        $createdItems = [];
        foreach ($items as $itemData) {
            $item = InventoryItem::firstOrCreate(
                [
                    'tenant_id' => $tenant->id,
                    'sku' => $itemData['sku'],
                ],
                array_merge($itemData, ['is_active' => true])
            );
            $createdItems[] = $item;
        }

        // Output summary
        $this->command->info('====================================');
        $this->command->info('Inventory Test Data Created Successfully!');
        $this->command->info('====================================');
        $this->command->info("Tenant: {$tenant->name} (ID: {$tenant->id})");
        $this->command->info("User: {$user->name} ({$user->email})");
        $this->command->info('Password: password');
        $this->command->info('');
        $this->command->info('Outlets: 2');
        $this->command->info("  - {$outletJakarta->name} (ID: {$outletJakarta->id})");
        $this->command->info("  - {$outletBandung->name} (ID: {$outletBandung->id})");
        $this->command->info('');
        $this->command->info('Units: '.count($createdUnits));
        $this->command->info('Categories: 5 (with hierarchy)');
        $this->command->info('Suppliers: 3');
        $this->command->info('Inventory Items: '.count($createdItems));
        $this->command->info('');
        $this->command->info('====================================');
        $this->command->info('Login Credentials:');
        $this->command->info('Email: admin@test.com');
        $this->command->info('Password: password');
        $this->command->info('====================================');
        $this->command->info('');
        $this->command->info('You can now login and start testing!');
        $this->command->info('URL: http://your-domain.test/login');
    }
}
