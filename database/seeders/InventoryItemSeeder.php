<?php

namespace Database\Seeders;

use App\Models\InventoryCategory;
use App\Models\InventoryItem;
use App\Models\Supplier;
use App\Models\SupplierItem;
use App\Models\Tenant;
use App\Models\Unit;
use Illuminate\Database\Seeder;

class InventoryItemSeeder extends Seeder
{
    public function run(): void
    {
        $tenants = Tenant::all();

        foreach ($tenants as $tenant) {
            $this->seedItemsForTenant($tenant);
        }
    }

    private function seedItemsForTenant(Tenant $tenant): void
    {
        $categories = InventoryCategory::where('tenant_id', $tenant->id)->get()->keyBy('name');
        $units = Unit::where('tenant_id', $tenant->id)->get()->keyBy('abbreviation');
        $suppliers = Supplier::where('tenant_id', $tenant->id)->get();

        // Proteins - Beef
        $this->createItemsForCategory($tenant, $categories, $units, $suppliers, 'Beef', [
            ['sku' => 'BF-001', 'name' => 'Daging Sapi Has Dalam (Tenderloin)', 'unit' => 'kg', 'cost' => 180000, 'shelf_life' => 5, 'barcode' => '8991234567001'],
            ['sku' => 'BF-002', 'name' => 'Daging Sapi Has Luar (Sirloin)', 'unit' => 'kg', 'cost' => 150000, 'shelf_life' => 5, 'barcode' => '8991234567002'],
            ['sku' => 'BF-003', 'name' => 'Daging Sapi Giling', 'unit' => 'kg', 'cost' => 95000, 'shelf_life' => 3, 'barcode' => '8991234567003'],
            ['sku' => 'BF-004', 'name' => 'Iga Sapi', 'unit' => 'kg', 'cost' => 120000, 'shelf_life' => 5, 'barcode' => '8991234567004'],
            ['sku' => 'BF-005', 'name' => 'Daging Sapi Sandung Lamur', 'unit' => 'kg', 'cost' => 110000, 'shelf_life' => 5, 'barcode' => '8991234567005'],
        ]);

        // Proteins - Poultry
        $this->createItemsForCategory($tenant, $categories, $units, $suppliers, 'Poultry', [
            ['sku' => 'PL-001', 'name' => 'Ayam Utuh', 'unit' => 'kg', 'cost' => 38000, 'shelf_life' => 3, 'barcode' => '8991234567011'],
            ['sku' => 'PL-002', 'name' => 'Dada Ayam Fillet', 'unit' => 'kg', 'cost' => 55000, 'shelf_life' => 3, 'barcode' => '8991234567012'],
            ['sku' => 'PL-003', 'name' => 'Paha Ayam', 'unit' => 'kg', 'cost' => 42000, 'shelf_life' => 3, 'barcode' => '8991234567013'],
            ['sku' => 'PL-004', 'name' => 'Sayap Ayam', 'unit' => 'kg', 'cost' => 35000, 'shelf_life' => 3, 'barcode' => '8991234567014'],
            ['sku' => 'PL-005', 'name' => 'Ayam Cincang', 'unit' => 'kg', 'cost' => 48000, 'shelf_life' => 2, 'barcode' => '8991234567015'],
            ['sku' => 'PL-006', 'name' => 'Bebek Utuh', 'unit' => 'kg', 'cost' => 65000, 'shelf_life' => 3, 'barcode' => '8991234567016'],
        ]);

        // Proteins - Seafood
        $this->createItemsForCategory($tenant, $categories, $units, $suppliers, 'Seafood', [
            ['sku' => 'SF-001', 'name' => 'Udang Vaname Ukuran 30', 'unit' => 'kg', 'cost' => 95000, 'shelf_life' => 2, 'barcode' => '8991234567021'],
            ['sku' => 'SF-002', 'name' => 'Ikan Salmon Fillet', 'unit' => 'kg', 'cost' => 220000, 'shelf_life' => 3, 'barcode' => '8991234567022'],
            ['sku' => 'SF-003', 'name' => 'Ikan Dori Fillet', 'unit' => 'kg', 'cost' => 75000, 'shelf_life' => 3, 'barcode' => '8991234567023'],
            ['sku' => 'SF-004', 'name' => 'Cumi-cumi Segar', 'unit' => 'kg', 'cost' => 85000, 'shelf_life' => 2, 'barcode' => '8991234567024'],
            ['sku' => 'SF-005', 'name' => 'Kerang Hijau', 'unit' => 'kg', 'cost' => 45000, 'shelf_life' => 2, 'barcode' => '8991234567025'],
            ['sku' => 'SF-006', 'name' => 'Kepiting Rajungan', 'unit' => 'kg', 'cost' => 150000, 'shelf_life' => 2, 'barcode' => '8991234567026'],
        ]);

        // Produce - Vegetables
        $this->createItemsForCategory($tenant, $categories, $units, $suppliers, 'Vegetables', [
            ['sku' => 'VG-001', 'name' => 'Wortel', 'unit' => 'kg', 'cost' => 15000, 'shelf_life' => 14, 'barcode' => '8991234567031'],
            ['sku' => 'VG-002', 'name' => 'Kentang', 'unit' => 'kg', 'cost' => 18000, 'shelf_life' => 21, 'barcode' => '8991234567032'],
            ['sku' => 'VG-003', 'name' => 'Bawang Bombay', 'unit' => 'kg', 'cost' => 25000, 'shelf_life' => 30, 'barcode' => '8991234567033'],
            ['sku' => 'VG-004', 'name' => 'Bawang Putih', 'unit' => 'kg', 'cost' => 35000, 'shelf_life' => 30, 'barcode' => '8991234567034'],
            ['sku' => 'VG-005', 'name' => 'Bawang Merah', 'unit' => 'kg', 'cost' => 45000, 'shelf_life' => 21, 'barcode' => '8991234567035'],
            ['sku' => 'VG-006', 'name' => 'Tomat', 'unit' => 'kg', 'cost' => 12000, 'shelf_life' => 7, 'barcode' => '8991234567036'],
            ['sku' => 'VG-007', 'name' => 'Paprika Merah', 'unit' => 'kg', 'cost' => 45000, 'shelf_life' => 10, 'barcode' => '8991234567037'],
            ['sku' => 'VG-008', 'name' => 'Selada Romaine', 'unit' => 'kg', 'cost' => 25000, 'shelf_life' => 5, 'barcode' => '8991234567038'],
            ['sku' => 'VG-009', 'name' => 'Brokoli', 'unit' => 'kg', 'cost' => 30000, 'shelf_life' => 7, 'barcode' => '8991234567039'],
            ['sku' => 'VG-010', 'name' => 'Jamur Champignon', 'unit' => 'kg', 'cost' => 55000, 'shelf_life' => 5, 'barcode' => '8991234567040'],
        ]);

        // Produce - Fruits
        $this->createItemsForCategory($tenant, $categories, $units, $suppliers, 'Fruits', [
            ['sku' => 'FR-001', 'name' => 'Lemon Import', 'unit' => 'kg', 'cost' => 35000, 'shelf_life' => 14, 'barcode' => '8991234567051'],
            ['sku' => 'FR-002', 'name' => 'Jeruk Nipis', 'unit' => 'kg', 'cost' => 20000, 'shelf_life' => 14, 'barcode' => '8991234567052'],
            ['sku' => 'FR-003', 'name' => 'Apel Fuji', 'unit' => 'kg', 'cost' => 35000, 'shelf_life' => 21, 'barcode' => '8991234567053'],
            ['sku' => 'FR-004', 'name' => 'Pisang Cavendish', 'unit' => 'kg', 'cost' => 18000, 'shelf_life' => 7, 'barcode' => '8991234567054'],
            ['sku' => 'FR-005', 'name' => 'Strawberry', 'unit' => 'kg', 'cost' => 85000, 'shelf_life' => 5, 'barcode' => '8991234567055'],
        ]);

        // Produce - Herbs
        $this->createItemsForCategory($tenant, $categories, $units, $suppliers, 'Herbs', [
            ['sku' => 'HB-001', 'name' => 'Daun Basil Segar', 'unit' => 'g', 'cost' => 150, 'shelf_life' => 5, 'barcode' => '8991234567061'],
            ['sku' => 'HB-002', 'name' => 'Daun Mint Segar', 'unit' => 'g', 'cost' => 100, 'shelf_life' => 5, 'barcode' => '8991234567062'],
            ['sku' => 'HB-003', 'name' => 'Rosemary Segar', 'unit' => 'g', 'cost' => 200, 'shelf_life' => 7, 'barcode' => '8991234567063'],
            ['sku' => 'HB-004', 'name' => 'Thyme Segar', 'unit' => 'g', 'cost' => 200, 'shelf_life' => 7, 'barcode' => '8991234567064'],
            ['sku' => 'HB-005', 'name' => 'Daun Ketumbar', 'unit' => 'g', 'cost' => 80, 'shelf_life' => 5, 'barcode' => '8991234567065'],
            ['sku' => 'HB-006', 'name' => 'Serai', 'unit' => 'kg', 'cost' => 20000, 'shelf_life' => 14, 'barcode' => '8991234567066'],
            ['sku' => 'HB-007', 'name' => 'Jahe', 'unit' => 'kg', 'cost' => 35000, 'shelf_life' => 30, 'barcode' => '8991234567067'],
        ]);

        // Dairy - Milk & Cream
        $this->createItemsForCategory($tenant, $categories, $units, $suppliers, 'Milk & Cream', [
            ['sku' => 'DY-001', 'name' => 'Susu Segar Full Cream', 'unit' => 'L', 'cost' => 18000, 'shelf_life' => 7, 'barcode' => '8991234567071', 'track_batches' => true],
            ['sku' => 'DY-002', 'name' => 'Whipping Cream', 'unit' => 'L', 'cost' => 65000, 'shelf_life' => 14, 'barcode' => '8991234567072', 'track_batches' => true],
            ['sku' => 'DY-003', 'name' => 'Cooking Cream', 'unit' => 'L', 'cost' => 45000, 'shelf_life' => 30, 'barcode' => '8991234567073'],
            ['sku' => 'DY-004', 'name' => 'Susu UHT Full Cream', 'unit' => 'L', 'cost' => 15000, 'shelf_life' => 180, 'barcode' => '8991234567074'],
        ]);

        // Dairy - Cheese
        $this->createItemsForCategory($tenant, $categories, $units, $suppliers, 'Cheese', [
            ['sku' => 'CH-001', 'name' => 'Keju Mozzarella', 'unit' => 'kg', 'cost' => 95000, 'shelf_life' => 30, 'barcode' => '8991234567081'],
            ['sku' => 'CH-002', 'name' => 'Keju Cheddar', 'unit' => 'kg', 'cost' => 85000, 'shelf_life' => 60, 'barcode' => '8991234567082'],
            ['sku' => 'CH-003', 'name' => 'Keju Parmesan', 'unit' => 'kg', 'cost' => 250000, 'shelf_life' => 90, 'barcode' => '8991234567083'],
            ['sku' => 'CH-004', 'name' => 'Cream Cheese', 'unit' => 'kg', 'cost' => 120000, 'shelf_life' => 30, 'barcode' => '8991234567084'],
        ]);

        // Dairy - Butter & Margarine
        $this->createItemsForCategory($tenant, $categories, $units, $suppliers, 'Butter & Margarine', [
            ['sku' => 'BT-001', 'name' => 'Butter Unsalted', 'unit' => 'kg', 'cost' => 95000, 'shelf_life' => 60, 'barcode' => '8991234567091'],
            ['sku' => 'BT-002', 'name' => 'Butter Salted', 'unit' => 'kg', 'cost' => 90000, 'shelf_life' => 60, 'barcode' => '8991234567092'],
            ['sku' => 'BT-003', 'name' => 'Margarine', 'unit' => 'kg', 'cost' => 35000, 'shelf_life' => 90, 'barcode' => '8991234567093'],
        ]);

        // Dairy - Eggs
        $this->createItemsForCategory($tenant, $categories, $units, $suppliers, 'Eggs', [
            ['sku' => 'EG-001', 'name' => 'Telur Ayam Negeri', 'unit' => 'kg', 'cost' => 28000, 'shelf_life' => 21, 'barcode' => '8991234567101'],
            ['sku' => 'EG-002', 'name' => 'Telur Ayam Kampung', 'unit' => 'pcs', 'cost' => 3500, 'shelf_life' => 21, 'barcode' => '8991234567102'],
            ['sku' => 'EG-003', 'name' => 'Telur Puyuh', 'unit' => 'kg', 'cost' => 45000, 'shelf_life' => 14, 'barcode' => '8991234567103'],
        ]);

        // Dry Goods - Flour & Grains
        $this->createItemsForCategory($tenant, $categories, $units, $suppliers, 'Flour & Grains', [
            ['sku' => 'FL-001', 'name' => 'Tepung Terigu Protein Tinggi', 'unit' => 'kg', 'cost' => 14000, 'shelf_life' => 180, 'barcode' => '8991234567111'],
            ['sku' => 'FL-002', 'name' => 'Tepung Terigu Protein Sedang', 'unit' => 'kg', 'cost' => 12000, 'shelf_life' => 180, 'barcode' => '8991234567112'],
            ['sku' => 'FL-003', 'name' => 'Tepung Maizena', 'unit' => 'kg', 'cost' => 25000, 'shelf_life' => 365, 'barcode' => '8991234567113'],
            ['sku' => 'FL-004', 'name' => 'Beras Putih Premium', 'unit' => 'kg', 'cost' => 15000, 'shelf_life' => 180, 'barcode' => '8991234567114'],
            ['sku' => 'FL-005', 'name' => 'Pasta Spaghetti', 'unit' => 'kg', 'cost' => 35000, 'shelf_life' => 365, 'barcode' => '8991234567115'],
            ['sku' => 'FL-006', 'name' => 'Pasta Penne', 'unit' => 'kg', 'cost' => 35000, 'shelf_life' => 365, 'barcode' => '8991234567116'],
            ['sku' => 'FL-007', 'name' => 'Mie Telur Kering', 'unit' => 'kg', 'cost' => 25000, 'shelf_life' => 180, 'barcode' => '8991234567117'],
        ]);

        // Dry Goods - Sugar & Sweeteners
        $this->createItemsForCategory($tenant, $categories, $units, $suppliers, 'Sugar & Sweeteners', [
            ['sku' => 'SG-001', 'name' => 'Gula Pasir Putih', 'unit' => 'kg', 'cost' => 16000, 'shelf_life' => 730, 'barcode' => '8991234567121'],
            ['sku' => 'SG-002', 'name' => 'Gula Aren', 'unit' => 'kg', 'cost' => 45000, 'shelf_life' => 365, 'barcode' => '8991234567122'],
            ['sku' => 'SG-003', 'name' => 'Madu Murni', 'unit' => 'kg', 'cost' => 120000, 'shelf_life' => 730, 'barcode' => '8991234567123'],
            ['sku' => 'SG-004', 'name' => 'Sirup Maple', 'unit' => 'L', 'cost' => 250000, 'shelf_life' => 365, 'barcode' => '8991234567124'],
        ]);

        // Dry Goods - Spices & Seasonings
        $this->createItemsForCategory($tenant, $categories, $units, $suppliers, 'Spices & Seasonings', [
            ['sku' => 'SP-001', 'name' => 'Garam Halus', 'unit' => 'kg', 'cost' => 8000, 'shelf_life' => 730, 'barcode' => '8991234567131'],
            ['sku' => 'SP-002', 'name' => 'Merica Bubuk', 'unit' => 'kg', 'cost' => 150000, 'shelf_life' => 365, 'barcode' => '8991234567132'],
            ['sku' => 'SP-003', 'name' => 'Kunyit Bubuk', 'unit' => 'kg', 'cost' => 85000, 'shelf_life' => 365, 'barcode' => '8991234567133'],
            ['sku' => 'SP-004', 'name' => 'Ketumbar Bubuk', 'unit' => 'kg', 'cost' => 95000, 'shelf_life' => 365, 'barcode' => '8991234567134'],
            ['sku' => 'SP-005', 'name' => 'Paprika Bubuk', 'unit' => 'kg', 'cost' => 180000, 'shelf_life' => 365, 'barcode' => '8991234567135'],
            ['sku' => 'SP-006', 'name' => 'Oregano Kering', 'unit' => 'kg', 'cost' => 350000, 'shelf_life' => 365, 'barcode' => '8991234567136'],
            ['sku' => 'SP-007', 'name' => 'MSG', 'unit' => 'kg', 'cost' => 28000, 'shelf_life' => 730, 'barcode' => '8991234567137'],
            ['sku' => 'SP-008', 'name' => 'Kaldu Ayam Bubuk', 'unit' => 'kg', 'cost' => 65000, 'shelf_life' => 365, 'barcode' => '8991234567138'],
        ]);

        // Dry Goods - Canned Goods
        $this->createItemsForCategory($tenant, $categories, $units, $suppliers, 'Canned Goods', [
            ['sku' => 'CN-001', 'name' => 'Tomat Kalengan Utuh', 'unit' => 'pcs', 'cost' => 25000, 'shelf_life' => 730, 'barcode' => '8991234567141'],
            ['sku' => 'CN-002', 'name' => 'Pasta Tomat', 'unit' => 'pcs', 'cost' => 18000, 'shelf_life' => 365, 'barcode' => '8991234567142'],
            ['sku' => 'CN-003', 'name' => 'Jamur Kalengan', 'unit' => 'pcs', 'cost' => 22000, 'shelf_life' => 730, 'barcode' => '8991234567143'],
            ['sku' => 'CN-004', 'name' => 'Jagung Manis Kalengan', 'unit' => 'pcs', 'cost' => 15000, 'shelf_life' => 730, 'barcode' => '8991234567144'],
        ]);

        // Beverages - Coffee & Tea
        $this->createItemsForCategory($tenant, $categories, $units, $suppliers, 'Coffee & Tea', [
            ['sku' => 'CF-001', 'name' => 'Biji Kopi Arabica', 'unit' => 'kg', 'cost' => 180000, 'shelf_life' => 180, 'barcode' => '8991234567151'],
            ['sku' => 'CF-002', 'name' => 'Biji Kopi Robusta', 'unit' => 'kg', 'cost' => 95000, 'shelf_life' => 180, 'barcode' => '8991234567152'],
            ['sku' => 'CF-003', 'name' => 'Teh Hijau Premium', 'unit' => 'kg', 'cost' => 250000, 'shelf_life' => 365, 'barcode' => '8991234567153'],
            ['sku' => 'CF-004', 'name' => 'Teh Hitam', 'unit' => 'kg', 'cost' => 120000, 'shelf_life' => 365, 'barcode' => '8991234567154'],
        ]);

        // Beverages - Soft Drinks
        $this->createItemsForCategory($tenant, $categories, $units, $suppliers, 'Soft Drinks', [
            ['sku' => 'SD-001', 'name' => 'Air Mineral 600ml', 'unit' => 'pcs', 'cost' => 2500, 'shelf_life' => 365, 'barcode' => '8991234567161'],
            ['sku' => 'SD-002', 'name' => 'Coca Cola 330ml', 'unit' => 'pcs', 'cost' => 5500, 'shelf_life' => 180, 'barcode' => '8991234567162'],
            ['sku' => 'SD-003', 'name' => 'Sprite 330ml', 'unit' => 'pcs', 'cost' => 5500, 'shelf_life' => 180, 'barcode' => '8991234567163'],
            ['sku' => 'SD-004', 'name' => 'Teh Botol 350ml', 'unit' => 'pcs', 'cost' => 4000, 'shelf_life' => 180, 'barcode' => '8991234567164'],
        ]);

        // Beverages - Juices
        $this->createItemsForCategory($tenant, $categories, $units, $suppliers, 'Juices', [
            ['sku' => 'JC-001', 'name' => 'Jus Jeruk 1L', 'unit' => 'pcs', 'cost' => 28000, 'shelf_life' => 14, 'barcode' => '8991234567171'],
            ['sku' => 'JC-002', 'name' => 'Jus Apel 1L', 'unit' => 'pcs', 'cost' => 32000, 'shelf_life' => 14, 'barcode' => '8991234567172'],
        ]);

        // Sauces & Condiments
        $this->createItemsForCategory($tenant, $categories, $units, $suppliers, 'Sauces & Condiments', [
            ['sku' => 'SC-001', 'name' => 'Kecap Manis', 'unit' => 'L', 'cost' => 25000, 'shelf_life' => 365, 'barcode' => '8991234567181'],
            ['sku' => 'SC-002', 'name' => 'Kecap Asin', 'unit' => 'L', 'cost' => 18000, 'shelf_life' => 365, 'barcode' => '8991234567182'],
            ['sku' => 'SC-003', 'name' => 'Saus Tiram', 'unit' => 'L', 'cost' => 45000, 'shelf_life' => 365, 'barcode' => '8991234567183'],
            ['sku' => 'SC-004', 'name' => 'Saus Tomat', 'unit' => 'L', 'cost' => 22000, 'shelf_life' => 365, 'barcode' => '8991234567184'],
            ['sku' => 'SC-005', 'name' => 'Mayonnaise', 'unit' => 'kg', 'cost' => 55000, 'shelf_life' => 180, 'barcode' => '8991234567185'],
            ['sku' => 'SC-006', 'name' => 'Saus Sambal', 'unit' => 'L', 'cost' => 28000, 'shelf_life' => 365, 'barcode' => '8991234567186'],
            ['sku' => 'SC-007', 'name' => 'Mustard', 'unit' => 'kg', 'cost' => 85000, 'shelf_life' => 365, 'barcode' => '8991234567187'],
            ['sku' => 'SC-008', 'name' => 'BBQ Sauce', 'unit' => 'L', 'cost' => 65000, 'shelf_life' => 365, 'barcode' => '8991234567188'],
        ]);

        // Oils & Fats
        $this->createItemsForCategory($tenant, $categories, $units, $suppliers, 'Oils & Fats', [
            ['sku' => 'OL-001', 'name' => 'Minyak Goreng Sawit', 'unit' => 'L', 'cost' => 18000, 'shelf_life' => 365, 'barcode' => '8991234567191'],
            ['sku' => 'OL-002', 'name' => 'Minyak Zaitun Extra Virgin', 'unit' => 'L', 'cost' => 180000, 'shelf_life' => 365, 'barcode' => '8991234567192'],
            ['sku' => 'OL-003', 'name' => 'Minyak Wijen', 'unit' => 'L', 'cost' => 120000, 'shelf_life' => 365, 'barcode' => '8991234567193'],
            ['sku' => 'OL-004', 'name' => 'Minyak Kelapa', 'unit' => 'L', 'cost' => 45000, 'shelf_life' => 365, 'barcode' => '8991234567194'],
        ]);

        // Packaging & Supplies
        $this->createItemsForCategory($tenant, $categories, $units, $suppliers, 'Packaging & Supplies', [
            ['sku' => 'PK-001', 'name' => 'Kotak Makan Styrofoam Besar', 'unit' => 'pcs', 'cost' => 1500, 'shelf_life' => null, 'barcode' => '8991234567201'],
            ['sku' => 'PK-002', 'name' => 'Kotak Makan Kertas', 'unit' => 'pcs', 'cost' => 2500, 'shelf_life' => null, 'barcode' => '8991234567202'],
            ['sku' => 'PK-003', 'name' => 'Plastik Kresek Sedang', 'unit' => 'pack', 'cost' => 15000, 'shelf_life' => null, 'barcode' => '8991234567203'],
            ['sku' => 'PK-004', 'name' => 'Sedotan Kertas', 'unit' => 'pack', 'cost' => 25000, 'shelf_life' => null, 'barcode' => '8991234567204'],
            ['sku' => 'PK-005', 'name' => 'Tisue Makan', 'unit' => 'pack', 'cost' => 35000, 'shelf_life' => null, 'barcode' => '8991234567205'],
            ['sku' => 'PK-006', 'name' => 'Cup Plastik 16oz', 'unit' => 'pcs', 'cost' => 800, 'shelf_life' => null, 'barcode' => '8991234567206'],
            ['sku' => 'PK-007', 'name' => 'Sendok Plastik', 'unit' => 'pack', 'cost' => 12000, 'shelf_life' => null, 'barcode' => '8991234567207'],
            ['sku' => 'PK-008', 'name' => 'Garpu Plastik', 'unit' => 'pack', 'cost' => 12000, 'shelf_life' => null, 'barcode' => '8991234567208'],
        ]);

        // Cleaning Supplies
        $this->createItemsForCategory($tenant, $categories, $units, $suppliers, 'Cleaning Supplies', [
            ['sku' => 'CL-001', 'name' => 'Sabun Cuci Piring', 'unit' => 'L', 'cost' => 25000, 'shelf_life' => 730, 'barcode' => '8991234567211'],
            ['sku' => 'CL-002', 'name' => 'Pembersih Lantai', 'unit' => 'L', 'cost' => 18000, 'shelf_life' => 730, 'barcode' => '8991234567212'],
            ['sku' => 'CL-003', 'name' => 'Hand Sanitizer', 'unit' => 'L', 'cost' => 45000, 'shelf_life' => 365, 'barcode' => '8991234567213'],
            ['sku' => 'CL-004', 'name' => 'Sabun Cuci Tangan', 'unit' => 'L', 'cost' => 28000, 'shelf_life' => 730, 'barcode' => '8991234567214'],
            ['sku' => 'CL-005', 'name' => 'Spons Cuci Piring', 'unit' => 'pcs', 'cost' => 3500, 'shelf_life' => null, 'barcode' => '8991234567215'],
            ['sku' => 'CL-006', 'name' => 'Lap Kain Dapur', 'unit' => 'pcs', 'cost' => 8500, 'shelf_life' => null, 'barcode' => '8991234567216'],
        ]);
    }

    /**
     * @param  array<string, InventoryCategory>  $categories
     * @param  array<string, Unit>  $units
     * @param  \Illuminate\Database\Eloquent\Collection<int, Supplier>  $suppliers
     * @param  array<int, array<string, mixed>>  $items
     */
    private function createItemsForCategory(
        Tenant $tenant,
        $categories,
        $units,
        $suppliers,
        string $categoryName,
        array $items
    ): void {
        $category = $categories->get($categoryName);
        if (! $category) {
            return;
        }

        foreach ($items as $itemData) {
            $unit = $units->get($itemData['unit']);
            if (! $unit) {
                continue;
            }

            $inventoryItem = InventoryItem::create([
                'tenant_id' => $tenant->id,
                'category_id' => $category->id,
                'unit_id' => $unit->id,
                'sku' => $itemData['sku'],
                'barcode' => $itemData['barcode'] ?? null,
                'name' => $itemData['name'],
                'description' => $itemData['description'] ?? null,
                'cost_price' => $itemData['cost'],
                'min_stock' => $itemData['min_stock'] ?? 10,
                'max_stock' => $itemData['max_stock'] ?? 100,
                'reorder_point' => $itemData['reorder_point'] ?? 20,
                'reorder_qty' => $itemData['reorder_qty'] ?? 50,
                'shelf_life_days' => $itemData['shelf_life'],
                'track_batches' => $itemData['track_batches'] ?? ($itemData['shelf_life'] !== null && $itemData['shelf_life'] <= 30),
                'is_active' => true,
            ]);

            // Assign random supplier to item
            if ($suppliers->isNotEmpty()) {
                $randomSupplier = $suppliers->random();
                SupplierItem::create([
                    'supplier_id' => $randomSupplier->id,
                    'inventory_item_id' => $inventoryItem->id,
                    'supplier_sku' => strtoupper(fake()->bothify('???-####')),
                    'unit_id' => $unit->id,
                    'unit_conversion' => 1,
                    'price' => $itemData['cost'] * fake()->randomFloat(2, 0.9, 1.1),
                    'lead_time_days' => fake()->numberBetween(1, 7),
                    'min_order_qty' => fake()->randomElement([1, 5, 10]),
                    'is_preferred' => true,
                    'is_active' => true,
                ]);
            }
        }
    }
}
