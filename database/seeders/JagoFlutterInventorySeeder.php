<?php

namespace Database\Seeders;

use App\Models\GoodsReceive;
use App\Models\GoodsReceiveItem;
use App\Models\InventoryCategory;
use App\Models\InventoryItem;
use App\Models\InventoryStock;
use App\Models\Outlet;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Recipe;
use App\Models\RecipeItem;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\SupplierItem;
use App\Models\Tenant;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class JagoFlutterInventorySeeder extends Seeder
{
    private Tenant $tenant;

    private $units;

    private $categories;

    private $suppliers;

    private $items;

    public function run(): void
    {
        $this->tenant = Tenant::where('name', 'like', '%Jago Flutter%')->firstOrFail();

        $this->seedUnits();
        $this->seedCategories();
        $this->seedSuppliers();
        $this->seedItems();
        $this->seedPurchaseOrdersAndStock();
        $this->seedRecipes();

        $this->command->info("Seeded inventory data for tenant: {$this->tenant->name}");
    }

    private function seedUnits(): void
    {
        $unitData = [
            ['name' => 'Gram', 'abbreviation' => 'g', 'is_base' => true],
            ['name' => 'Kilogram', 'abbreviation' => 'kg', 'is_base' => false, 'base' => 'g', 'conversion' => 1000],
            ['name' => 'Mililiter', 'abbreviation' => 'ml', 'is_base' => true],
            ['name' => 'Liter', 'abbreviation' => 'L', 'is_base' => false, 'base' => 'ml', 'conversion' => 1000],
            ['name' => 'Piece', 'abbreviation' => 'pcs', 'is_base' => true],
            ['name' => 'Pack', 'abbreviation' => 'pack', 'is_base' => true],
        ];

        foreach ($unitData as $data) {
            Unit::firstOrCreate(
                ['tenant_id' => $this->tenant->id, 'abbreviation' => $data['abbreviation']],
                [
                    'name' => $data['name'],
                    'base_unit_id' => isset($data['base']) ? Unit::where('tenant_id', $this->tenant->id)->where('abbreviation', $data['base'])->first()?->id : null,
                    'conversion_factor' => $data['conversion'] ?? 1,
                    'is_active' => true,
                ]
            );
        }

        $this->units = Unit::where('tenant_id', $this->tenant->id)->get()->keyBy('abbreviation');
    }

    private function seedCategories(): void
    {
        $categoryData = [
            ['name' => 'Daging', 'description' => 'Daging sapi, ayam, ikan'],
            ['name' => 'Sayuran', 'description' => 'Sayuran segar'],
            ['name' => 'Bumbu', 'description' => 'Bumbu dapur'],
            ['name' => 'Dairy', 'description' => 'Susu dan produk dairy'],
            ['name' => 'Tepung & Biji', 'description' => 'Tepung dan biji-bijian'],
            ['name' => 'Minyak & Saus', 'description' => 'Minyak goreng dan saus'],
            ['name' => 'Minuman', 'description' => 'Bahan minuman'],
        ];

        foreach ($categoryData as $data) {
            InventoryCategory::firstOrCreate(
                ['tenant_id' => $this->tenant->id, 'name' => $data['name']],
                [
                    'slug' => Str::slug($data['name']),
                    'description' => $data['description'],
                    'is_active' => true,
                ]
            );
        }

        $this->categories = InventoryCategory::where('tenant_id', $this->tenant->id)->get()->keyBy('name');
    }

    private function seedSuppliers(): void
    {
        $supplierData = [
            ['name' => 'PT Daging Segar', 'code' => 'SUP-001', 'contact' => 'Budi', 'phone' => '081234567890', 'email' => 'daging@supplier.com'],
            ['name' => 'CV Sayur Mayur', 'code' => 'SUP-002', 'contact' => 'Ani', 'phone' => '081234567891', 'email' => 'sayur@supplier.com'],
            ['name' => 'UD Bumbu Nusantara', 'code' => 'SUP-003', 'contact' => 'Citra', 'phone' => '081234567892', 'email' => 'bumbu@supplier.com'],
        ];

        foreach ($supplierData as $data) {
            Supplier::firstOrCreate(
                ['tenant_id' => $this->tenant->id, 'code' => $data['code']],
                [
                    'name' => $data['name'],
                    'contact_person' => $data['contact'],
                    'phone' => $data['phone'],
                    'email' => $data['email'],
                    'address' => 'Jakarta',
                    'payment_terms' => 30,
                    'is_active' => true,
                ]
            );
        }

        $this->suppliers = Supplier::where('tenant_id', $this->tenant->id)->get();
    }

    private function seedItems(): void
    {
        $itemsData = [
            // Daging
            ['sku' => 'DG-001', 'name' => 'Daging Sapi Has Dalam (Tenderloin)', 'category' => 'Daging', 'unit' => 'kg', 'cost' => 180000, 'shelf_life' => 5],
            ['sku' => 'DG-002', 'name' => 'Daging Sapi Has Luar (Sirloin)', 'category' => 'Daging', 'unit' => 'kg', 'cost' => 150000, 'shelf_life' => 5],
            ['sku' => 'DG-003', 'name' => 'Daging Sapi Giling', 'category' => 'Daging', 'unit' => 'kg', 'cost' => 95000, 'shelf_life' => 3],
            ['sku' => 'DG-004', 'name' => 'Dada Ayam Fillet', 'category' => 'Daging', 'unit' => 'kg', 'cost' => 55000, 'shelf_life' => 3],
            ['sku' => 'DG-005', 'name' => 'Paha Ayam', 'category' => 'Daging', 'unit' => 'kg', 'cost' => 42000, 'shelf_life' => 3],
            ['sku' => 'DG-006', 'name' => 'Udang Vaname', 'category' => 'Daging', 'unit' => 'kg', 'cost' => 95000, 'shelf_life' => 2],
            ['sku' => 'DG-007', 'name' => 'Ikan Salmon Fillet', 'category' => 'Daging', 'unit' => 'kg', 'cost' => 220000, 'shelf_life' => 3],
            ['sku' => 'DG-008', 'name' => 'Ikan Dori Fillet', 'category' => 'Daging', 'unit' => 'kg', 'cost' => 75000, 'shelf_life' => 3],

            // Sayuran
            ['sku' => 'SY-001', 'name' => 'Wortel', 'category' => 'Sayuran', 'unit' => 'kg', 'cost' => 15000, 'shelf_life' => 14],
            ['sku' => 'SY-002', 'name' => 'Kentang', 'category' => 'Sayuran', 'unit' => 'kg', 'cost' => 18000, 'shelf_life' => 21],
            ['sku' => 'SY-003', 'name' => 'Bawang Bombay', 'category' => 'Sayuran', 'unit' => 'kg', 'cost' => 25000, 'shelf_life' => 30],
            ['sku' => 'SY-004', 'name' => 'Tomat', 'category' => 'Sayuran', 'unit' => 'kg', 'cost' => 12000, 'shelf_life' => 7],
            ['sku' => 'SY-005', 'name' => 'Paprika Merah', 'category' => 'Sayuran', 'unit' => 'kg', 'cost' => 45000, 'shelf_life' => 10],
            ['sku' => 'SY-006', 'name' => 'Selada Romaine', 'category' => 'Sayuran', 'unit' => 'kg', 'cost' => 25000, 'shelf_life' => 5],
            ['sku' => 'SY-007', 'name' => 'Brokoli', 'category' => 'Sayuran', 'unit' => 'kg', 'cost' => 30000, 'shelf_life' => 7],
            ['sku' => 'SY-008', 'name' => 'Jamur Champignon', 'category' => 'Sayuran', 'unit' => 'kg', 'cost' => 55000, 'shelf_life' => 5],

            // Bumbu
            ['sku' => 'BM-001', 'name' => 'Bawang Putih', 'category' => 'Bumbu', 'unit' => 'kg', 'cost' => 35000, 'shelf_life' => 30],
            ['sku' => 'BM-002', 'name' => 'Bawang Merah', 'category' => 'Bumbu', 'unit' => 'kg', 'cost' => 45000, 'shelf_life' => 21],
            ['sku' => 'BM-003', 'name' => 'Jahe', 'category' => 'Bumbu', 'unit' => 'kg', 'cost' => 35000, 'shelf_life' => 30],
            ['sku' => 'BM-004', 'name' => 'Garam Halus', 'category' => 'Bumbu', 'unit' => 'kg', 'cost' => 8000, 'shelf_life' => 730],
            ['sku' => 'BM-005', 'name' => 'Merica Bubuk', 'category' => 'Bumbu', 'unit' => 'kg', 'cost' => 150000, 'shelf_life' => 365],
            ['sku' => 'BM-006', 'name' => 'Gula Pasir', 'category' => 'Bumbu', 'unit' => 'kg', 'cost' => 16000, 'shelf_life' => 730],
            ['sku' => 'BM-007', 'name' => 'Serai', 'category' => 'Bumbu', 'unit' => 'kg', 'cost' => 20000, 'shelf_life' => 14],
            ['sku' => 'BM-008', 'name' => 'Daun Jeruk', 'category' => 'Bumbu', 'unit' => 'pack', 'cost' => 5000, 'shelf_life' => 7],

            // Dairy
            ['sku' => 'DR-001', 'name' => 'Susu Full Cream', 'category' => 'Dairy', 'unit' => 'L', 'cost' => 18000, 'shelf_life' => 7],
            ['sku' => 'DR-002', 'name' => 'Whipping Cream', 'category' => 'Dairy', 'unit' => 'L', 'cost' => 65000, 'shelf_life' => 14],
            ['sku' => 'DR-003', 'name' => 'Keju Mozzarella', 'category' => 'Dairy', 'unit' => 'kg', 'cost' => 95000, 'shelf_life' => 30],
            ['sku' => 'DR-004', 'name' => 'Keju Parmesan', 'category' => 'Dairy', 'unit' => 'kg', 'cost' => 250000, 'shelf_life' => 90],
            ['sku' => 'DR-005', 'name' => 'Butter Unsalted', 'category' => 'Dairy', 'unit' => 'kg', 'cost' => 95000, 'shelf_life' => 60],
            ['sku' => 'DR-006', 'name' => 'Telur Ayam', 'category' => 'Dairy', 'unit' => 'kg', 'cost' => 28000, 'shelf_life' => 21],

            // Tepung & Biji
            ['sku' => 'TP-001', 'name' => 'Tepung Terigu Protein Tinggi', 'category' => 'Tepung & Biji', 'unit' => 'kg', 'cost' => 14000, 'shelf_life' => 180],
            ['sku' => 'TP-002', 'name' => 'Tepung Maizena', 'category' => 'Tepung & Biji', 'unit' => 'kg', 'cost' => 25000, 'shelf_life' => 365],
            ['sku' => 'TP-003', 'name' => 'Beras Putih Premium', 'category' => 'Tepung & Biji', 'unit' => 'kg', 'cost' => 15000, 'shelf_life' => 180],
            ['sku' => 'TP-004', 'name' => 'Pasta Spaghetti', 'category' => 'Tepung & Biji', 'unit' => 'kg', 'cost' => 35000, 'shelf_life' => 365],
            ['sku' => 'TP-005', 'name' => 'Mie Telur', 'category' => 'Tepung & Biji', 'unit' => 'kg', 'cost' => 25000, 'shelf_life' => 180],

            // Minyak & Saus
            ['sku' => 'MS-001', 'name' => 'Minyak Goreng', 'category' => 'Minyak & Saus', 'unit' => 'L', 'cost' => 18000, 'shelf_life' => 365],
            ['sku' => 'MS-002', 'name' => 'Minyak Zaitun', 'category' => 'Minyak & Saus', 'unit' => 'L', 'cost' => 180000, 'shelf_life' => 365],
            ['sku' => 'MS-003', 'name' => 'Kecap Manis', 'category' => 'Minyak & Saus', 'unit' => 'L', 'cost' => 25000, 'shelf_life' => 365],
            ['sku' => 'MS-004', 'name' => 'Kecap Asin', 'category' => 'Minyak & Saus', 'unit' => 'L', 'cost' => 18000, 'shelf_life' => 365],
            ['sku' => 'MS-005', 'name' => 'Saus Tiram', 'category' => 'Minyak & Saus', 'unit' => 'L', 'cost' => 45000, 'shelf_life' => 365],
            ['sku' => 'MS-006', 'name' => 'Saus Sambal', 'category' => 'Minyak & Saus', 'unit' => 'L', 'cost' => 28000, 'shelf_life' => 365],
            ['sku' => 'MS-007', 'name' => 'Mayonnaise', 'category' => 'Minyak & Saus', 'unit' => 'kg', 'cost' => 55000, 'shelf_life' => 180],

            // Minuman
            ['sku' => 'MN-001', 'name' => 'Kopi Arabica', 'category' => 'Minuman', 'unit' => 'kg', 'cost' => 180000, 'shelf_life' => 180],
            ['sku' => 'MN-002', 'name' => 'Teh Hijau', 'category' => 'Minuman', 'unit' => 'kg', 'cost' => 120000, 'shelf_life' => 365],
            ['sku' => 'MN-003', 'name' => 'Gula Aren', 'category' => 'Minuman', 'unit' => 'kg', 'cost' => 45000, 'shelf_life' => 365],
            ['sku' => 'MN-004', 'name' => 'Cokelat Bubuk', 'category' => 'Minuman', 'unit' => 'kg', 'cost' => 85000, 'shelf_life' => 365],
        ];

        foreach ($itemsData as $data) {
            $category = $this->categories->get($data['category']);
            $unit = $this->units->get($data['unit']);

            if (! $category || ! $unit) {
                continue;
            }

            $item = InventoryItem::firstOrCreate(
                ['tenant_id' => $this->tenant->id, 'sku' => $data['sku']],
                [
                    'category_id' => $category->id,
                    'unit_id' => $unit->id,
                    'name' => $data['name'],
                    'cost_price' => $data['cost'],
                    'min_stock' => 5,
                    'max_stock' => 100,
                    'reorder_point' => 10,
                    'reorder_qty' => 20,
                    'shelf_life_days' => $data['shelf_life'],
                    'track_batches' => $data['shelf_life'] <= 30,
                    'is_active' => true,
                ]
            );

            // Link to supplier
            if ($this->suppliers->isNotEmpty()) {
                SupplierItem::firstOrCreate(
                    ['supplier_id' => $this->suppliers->random()->id, 'inventory_item_id' => $item->id],
                    [
                        'supplier_sku' => 'SUP-'.$data['sku'],
                        'unit_id' => $unit->id,
                        'unit_conversion' => 1,
                        'price' => $data['cost'],
                        'lead_time_days' => 2,
                        'min_order_qty' => 1,
                        'is_preferred' => true,
                        'is_active' => true,
                    ]
                );
            }
        }

        $this->items = InventoryItem::where('tenant_id', $this->tenant->id)->get()->keyBy('sku');
    }

    private function seedPurchaseOrdersAndStock(): void
    {
        $outlet = Outlet::where('tenant_id', $this->tenant->id)->first();
        $user = User::where('tenant_id', $this->tenant->id)->first();
        $supplier = $this->suppliers->first();

        if (! $outlet || ! $user || ! $supplier) {
            return;
        }

        // Create a completed PO
        $po = PurchaseOrder::create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $outlet->id,
            'supplier_id' => $supplier->id,
            'po_number' => 'PO-JF-'.now()->format('Ymd').'-001',
            'order_date' => now()->subDays(7),
            'expected_date' => now()->subDays(5),
            'status' => PurchaseOrder::STATUS_RECEIVED,
            'subtotal' => 0,
            'tax_amount' => 0,
            'discount_amount' => 0,
            'total' => 0,
            'notes' => 'Initial stock seeding',
            'created_by' => $user->id,
            'approved_by' => $user->id,
            'approved_at' => now()->subDays(6),
        ]);

        $subtotal = 0;
        $taxTotal = 0;

        foreach ($this->items as $item) {
            $quantity = rand(20, 50);
            $unitPrice = (float) $item->cost_price;
            $taxPercent = 11;

            $itemSubtotal = $quantity * $unitPrice;
            $taxAmount = $itemSubtotal * ($taxPercent / 100);
            $total = $itemSubtotal + $taxAmount;

            PurchaseOrderItem::create([
                'purchase_order_id' => $po->id,
                'inventory_item_id' => $item->id,
                'unit_id' => $item->unit_id,
                'unit_conversion' => 1,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'discount_percent' => 0,
                'discount_amount' => 0,
                'tax_percent' => $taxPercent,
                'tax_amount' => $taxAmount,
                'total' => $total,
                'received_qty' => $quantity,
            ]);

            $subtotal += $itemSubtotal;
            $taxTotal += $taxAmount;
        }

        $po->update([
            'subtotal' => $subtotal,
            'tax_amount' => $taxTotal,
            'total' => $subtotal + $taxTotal,
        ]);

        // Create Goods Receive
        $gr = GoodsReceive::create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $outlet->id,
            'purchase_order_id' => $po->id,
            'supplier_id' => $supplier->id,
            'gr_number' => 'GR-JF-'.now()->format('Ymd').'-001',
            'receive_date' => now()->subDays(5),
            'status' => GoodsReceive::STATUS_COMPLETED,
            'invoice_number' => 'INV-'.rand(100000, 999999),
            'invoice_date' => now()->subDays(5),
            'subtotal' => $subtotal,
            'tax_amount' => $taxTotal,
            'discount_amount' => 0,
            'total' => $subtotal + $taxTotal,
            'received_by' => $user->id,
        ]);

        // Create GR Items and Stock
        foreach ($po->items as $poItem) {
            $expiryDate = $poItem->inventoryItem->shelf_life_days
                ? now()->addDays($poItem->inventoryItem->shelf_life_days)
                : null;

            GoodsReceiveItem::create([
                'goods_receive_id' => $gr->id,
                'purchase_order_item_id' => $poItem->id,
                'inventory_item_id' => $poItem->inventory_item_id,
                'unit_id' => $poItem->unit_id,
                'unit_conversion' => 1,
                'quantity' => $poItem->quantity,
                'stock_qty' => $poItem->quantity,
                'unit_price' => $poItem->unit_price,
                'discount_percent' => 0,
                'discount_amount' => 0,
                'tax_percent' => $poItem->tax_percent,
                'tax_amount' => $poItem->tax_amount,
                'total' => $poItem->total,
                'batch_number' => $poItem->inventoryItem->track_batches ? 'BATCH-'.rand(100000, 999999) : null,
                'expiry_date' => $expiryDate,
            ]);

            // Create/Update Stock
            $stock = InventoryStock::firstOrCreate(
                [
                    'outlet_id' => $outlet->id,
                    'inventory_item_id' => $poItem->inventory_item_id,
                ],
                [
                    'quantity' => 0,
                    'reserved_qty' => 0,
                    'avg_cost' => $poItem->unit_price,
                    'last_cost' => $poItem->unit_price,
                ]
            );

            $stockBefore = $stock->quantity;
            $stock->increment('quantity', $poItem->quantity);
            $stock->update([
                'avg_cost' => $poItem->unit_price,
                'last_cost' => $poItem->unit_price,
                'last_received_at' => now(),
            ]);

            // Create Stock Movement
            StockMovement::create([
                'outlet_id' => $outlet->id,
                'inventory_item_id' => $poItem->inventory_item_id,
                'type' => StockMovement::TYPE_IN,
                'reference_type' => GoodsReceive::class,
                'reference_id' => $gr->id,
                'quantity' => $poItem->quantity,
                'cost_price' => $poItem->unit_price,
                'stock_before' => $stockBefore,
                'stock_after' => $stock->quantity,
                'notes' => 'Initial stock from GR: '.$gr->gr_number,
                'created_by' => $user->id,
            ]);
        }
    }

    private function seedRecipes(): void
    {
        $pcsUnit = $this->units->get('pcs');
        $gUnit = $this->units->get('g');
        $mlUnit = $this->units->get('ml');

        if (! $pcsUnit) {
            return;
        }

        $recipes = [
            [
                'name' => 'Nasi Goreng Spesial',
                'description' => 'Nasi goreng dengan ayam, udang, dan telur',
                'yield_qty' => 1,
                'yield_unit' => 'pcs',
                'prep_time' => 10,
                'cook_time' => 8,
                'ingredients' => [
                    ['sku' => 'TP-003', 'qty' => 200, 'unit' => 'g', 'notes' => 'Nasi matang'],
                    ['sku' => 'DG-004', 'qty' => 80, 'unit' => 'g'],
                    ['sku' => 'DG-006', 'qty' => 50, 'unit' => 'g'],
                    ['sku' => 'DR-006', 'qty' => 55, 'unit' => 'g', 'notes' => '1 butir'],
                    ['sku' => 'BM-001', 'qty' => 10, 'unit' => 'g'],
                    ['sku' => 'BM-002', 'qty' => 15, 'unit' => 'g'],
                    ['sku' => 'MS-003', 'qty' => 20, 'unit' => 'ml'],
                    ['sku' => 'MS-006', 'qty' => 10, 'unit' => 'ml'],
                    ['sku' => 'MS-001', 'qty' => 30, 'unit' => 'ml'],
                    ['sku' => 'BM-004', 'qty' => 2, 'unit' => 'g'],
                ],
            ],
            [
                'name' => 'Ayam Goreng Crispy',
                'description' => 'Ayam goreng tepung crispy',
                'yield_qty' => 1,
                'yield_unit' => 'pcs',
                'prep_time' => 15,
                'cook_time' => 12,
                'ingredients' => [
                    ['sku' => 'DG-005', 'qty' => 250, 'unit' => 'g'],
                    ['sku' => 'TP-001', 'qty' => 50, 'unit' => 'g'],
                    ['sku' => 'TP-002', 'qty' => 20, 'unit' => 'g'],
                    ['sku' => 'BM-001', 'qty' => 15, 'unit' => 'g'],
                    ['sku' => 'BM-005', 'qty' => 3, 'unit' => 'g'],
                    ['sku' => 'BM-004', 'qty' => 5, 'unit' => 'g'],
                    ['sku' => 'MS-001', 'qty' => 200, 'unit' => 'ml'],
                ],
            ],
            [
                'name' => 'Spaghetti Carbonara',
                'description' => 'Pasta dengan saus krim dan bacon',
                'yield_qty' => 1,
                'yield_unit' => 'pcs',
                'prep_time' => 5,
                'cook_time' => 15,
                'ingredients' => [
                    ['sku' => 'TP-004', 'qty' => 120, 'unit' => 'g'],
                    ['sku' => 'DR-006', 'qty' => 110, 'unit' => 'g', 'notes' => '2 butir'],
                    ['sku' => 'DR-004', 'qty' => 40, 'unit' => 'g'],
                    ['sku' => 'DG-003', 'qty' => 60, 'unit' => 'g'],
                    ['sku' => 'BM-001', 'qty' => 8, 'unit' => 'g'],
                    ['sku' => 'BM-005', 'qty' => 2, 'unit' => 'g'],
                    ['sku' => 'MS-002', 'qty' => 15, 'unit' => 'ml'],
                ],
            ],
            [
                'name' => 'Es Kopi Susu',
                'description' => 'Kopi susu gula aren',
                'yield_qty' => 1,
                'yield_unit' => 'pcs',
                'prep_time' => 2,
                'cook_time' => 3,
                'ingredients' => [
                    ['sku' => 'MN-001', 'qty' => 18, 'unit' => 'g'],
                    ['sku' => 'DR-001', 'qty' => 150, 'unit' => 'ml'],
                    ['sku' => 'MN-003', 'qty' => 20, 'unit' => 'g'],
                ],
            ],
            [
                'name' => 'Salmon Teriyaki',
                'description' => 'Salmon panggang dengan saus teriyaki',
                'yield_qty' => 1,
                'yield_unit' => 'pcs',
                'prep_time' => 5,
                'cook_time' => 10,
                'ingredients' => [
                    ['sku' => 'DG-007', 'qty' => 180, 'unit' => 'g'],
                    ['sku' => 'MS-004', 'qty' => 30, 'unit' => 'ml'],
                    ['sku' => 'BM-006', 'qty' => 15, 'unit' => 'g'],
                    ['sku' => 'BM-003', 'qty' => 5, 'unit' => 'g'],
                    ['sku' => 'BM-001', 'qty' => 5, 'unit' => 'g'],
                    ['sku' => 'MS-001', 'qty' => 15, 'unit' => 'ml'],
                ],
            ],
            [
                'name' => 'French Fries',
                'description' => 'Kentang goreng crispy',
                'yield_qty' => 1,
                'yield_unit' => 'pcs',
                'prep_time' => 15,
                'cook_time' => 10,
                'ingredients' => [
                    ['sku' => 'SY-002', 'qty' => 200, 'unit' => 'g'],
                    ['sku' => 'MS-001', 'qty' => 300, 'unit' => 'ml'],
                    ['sku' => 'BM-004', 'qty' => 3, 'unit' => 'g'],
                ],
            ],
        ];

        foreach ($recipes as $recipeData) {
            $yieldUnit = $this->units->get($recipeData['yield_unit']);
            if (! $yieldUnit) {
                continue;
            }

            $recipe = Recipe::firstOrCreate(
                ['tenant_id' => $this->tenant->id, 'name' => $recipeData['name']],
                [
                    'description' => $recipeData['description'],
                    'yield_qty' => $recipeData['yield_qty'],
                    'yield_unit_id' => $yieldUnit->id,
                    'estimated_cost' => 0,
                    'prep_time_minutes' => $recipeData['prep_time'],
                    'cook_time_minutes' => $recipeData['cook_time'],
                    'version' => 1,
                    'is_active' => true,
                ]
            );

            $totalCost = 0;
            $sortOrder = 1;

            foreach ($recipeData['ingredients'] as $ingredient) {
                $item = $this->items->get($ingredient['sku']);
                $unit = $this->units->get($ingredient['unit']);

                if (! $item || ! $unit) {
                    continue;
                }

                RecipeItem::firstOrCreate(
                    ['recipe_id' => $recipe->id, 'inventory_item_id' => $item->id],
                    [
                        'quantity' => $ingredient['qty'],
                        'unit_id' => $unit->id,
                        'waste_percentage' => 0,
                        'notes' => $ingredient['notes'] ?? null,
                        'sort_order' => $sortOrder++,
                    ]
                );

                $totalCost += $ingredient['qty'] * (float) $item->cost_price;
            }

            $recipe->update(['estimated_cost' => $totalCost]);
        }
    }
}
