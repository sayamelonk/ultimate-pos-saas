<?php

namespace Tests\Unit\Models;

use App\Models\InventoryCategory;
use App\Models\InventoryItem;
use App\Models\InventoryStock;
use App\Models\Outlet;
use App\Models\StockBatch;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\SupplierItem;
use App\Models\Tenant;
use App\Models\Unit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryItemTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;

    protected Outlet $outlet;

    protected Unit $unit;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create();
        $this->outlet = Outlet::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->unit = Unit::factory()->create(['tenant_id' => $this->tenant->id]);
    }

    // ============================================================
    // BASIC CREATION TESTS
    // ============================================================

    public function test_can_create_inventory_item(): void
    {
        $item = InventoryItem::factory()->create([
            'tenant_id' => $this->tenant->id,
            'unit_id' => $this->unit->id,
            'name' => 'Test Item',
            'sku' => 'SKU-001',
        ]);

        $this->assertDatabaseHas('inventory_items', [
            'id' => $item->id,
            'name' => 'Test Item',
            'sku' => 'SKU-001',
        ]);
    }

    public function test_inventory_item_belongs_to_tenant(): void
    {
        $item = InventoryItem::factory()->create([
            'tenant_id' => $this->tenant->id,
            'unit_id' => $this->unit->id,
        ]);

        $this->assertInstanceOf(Tenant::class, $item->tenant);
        $this->assertEquals($this->tenant->id, $item->tenant->id);
    }

    public function test_inventory_item_belongs_to_unit(): void
    {
        $item = InventoryItem::factory()->create([
            'tenant_id' => $this->tenant->id,
            'unit_id' => $this->unit->id,
        ]);

        $this->assertInstanceOf(Unit::class, $item->unit);
        $this->assertEquals($this->unit->id, $item->unit->id);
    }

    public function test_inventory_item_can_have_category(): void
    {
        $category = InventoryCategory::factory()->create(['tenant_id' => $this->tenant->id]);
        $item = InventoryItem::factory()->withCategory($category)->create([
            'unit_id' => $this->unit->id,
        ]);

        $this->assertInstanceOf(InventoryCategory::class, $item->category);
        $this->assertEquals($category->id, $item->category->id);
    }

    public function test_inventory_item_can_have_purchase_unit(): void
    {
        $purchaseUnit = Unit::factory()->create(['tenant_id' => $this->tenant->id]);
        $item = InventoryItem::factory()->create([
            'tenant_id' => $this->tenant->id,
            'unit_id' => $this->unit->id,
            'purchase_unit_id' => $purchaseUnit->id,
            'purchase_unit_conversion' => 12, // 1 box = 12 pcs
        ]);

        $this->assertInstanceOf(Unit::class, $item->purchaseUnit);
        $this->assertEquals(12, $item->purchase_unit_conversion);
    }

    // ============================================================
    // STOCK RELATIONSHIP TESTS
    // ============================================================

    public function test_inventory_item_has_many_stocks(): void
    {
        $item = InventoryItem::factory()->create([
            'tenant_id' => $this->tenant->id,
            'unit_id' => $this->unit->id,
        ]);

        $outlet2 = Outlet::factory()->create(['tenant_id' => $this->tenant->id]);

        InventoryStock::factory()->create([
            'outlet_id' => $this->outlet->id,
            'inventory_item_id' => $item->id,
            'quantity' => 100,
        ]);

        InventoryStock::factory()->create([
            'outlet_id' => $outlet2->id,
            'inventory_item_id' => $item->id,
            'quantity' => 50,
        ]);

        $this->assertCount(2, $item->stocks);
    }

    public function test_get_stock_for_outlet(): void
    {
        $item = InventoryItem::factory()->create([
            'tenant_id' => $this->tenant->id,
            'unit_id' => $this->unit->id,
        ]);

        $stock = InventoryStock::factory()->create([
            'outlet_id' => $this->outlet->id,
            'inventory_item_id' => $item->id,
            'quantity' => 100,
        ]);

        $foundStock = $item->getStockForOutlet($this->outlet->id);

        $this->assertInstanceOf(InventoryStock::class, $foundStock);
        $this->assertEquals(100, $foundStock->quantity);
    }

    public function test_get_stock_for_outlet_returns_null_when_not_found(): void
    {
        $item = InventoryItem::factory()->create([
            'tenant_id' => $this->tenant->id,
            'unit_id' => $this->unit->id,
        ]);

        $foundStock = $item->getStockForOutlet($this->outlet->id);

        $this->assertNull($foundStock);
    }

    public function test_get_total_stock(): void
    {
        $item = InventoryItem::factory()->create([
            'tenant_id' => $this->tenant->id,
            'unit_id' => $this->unit->id,
        ]);

        $outlet2 = Outlet::factory()->create(['tenant_id' => $this->tenant->id]);

        InventoryStock::factory()->create([
            'outlet_id' => $this->outlet->id,
            'inventory_item_id' => $item->id,
            'quantity' => 100,
        ]);

        InventoryStock::factory()->create([
            'outlet_id' => $outlet2->id,
            'inventory_item_id' => $item->id,
            'quantity' => 50,
        ]);

        $totalStock = $item->getTotalStock();

        $this->assertEquals(150, $totalStock);
    }

    public function test_get_total_stock_returns_zero_when_no_stock(): void
    {
        $item = InventoryItem::factory()->create([
            'tenant_id' => $this->tenant->id,
            'unit_id' => $this->unit->id,
        ]);

        $totalStock = $item->getTotalStock();

        $this->assertEquals(0, $totalStock);
    }

    // ============================================================
    // LOW STOCK TESTS
    // ============================================================

    public function test_is_low_stock_returns_true_when_below_reorder_point(): void
    {
        $item = InventoryItem::factory()->create([
            'tenant_id' => $this->tenant->id,
            'unit_id' => $this->unit->id,
            'reorder_point' => 20,
        ]);

        InventoryStock::factory()->create([
            'outlet_id' => $this->outlet->id,
            'inventory_item_id' => $item->id,
            'quantity' => 15, // Below reorder point
        ]);

        $this->assertTrue($item->isLowStock($this->outlet->id));
    }

    public function test_is_low_stock_returns_true_when_at_reorder_point(): void
    {
        $item = InventoryItem::factory()->create([
            'tenant_id' => $this->tenant->id,
            'unit_id' => $this->unit->id,
            'reorder_point' => 20,
        ]);

        InventoryStock::factory()->create([
            'outlet_id' => $this->outlet->id,
            'inventory_item_id' => $item->id,
            'quantity' => 20, // Equal to reorder point
        ]);

        $this->assertTrue($item->isLowStock($this->outlet->id));
    }

    public function test_is_low_stock_returns_false_when_above_reorder_point(): void
    {
        $item = InventoryItem::factory()->create([
            'tenant_id' => $this->tenant->id,
            'unit_id' => $this->unit->id,
            'reorder_point' => 20,
        ]);

        InventoryStock::factory()->create([
            'outlet_id' => $this->outlet->id,
            'inventory_item_id' => $item->id,
            'quantity' => 50, // Above reorder point
        ]);

        $this->assertFalse($item->isLowStock($this->outlet->id));
    }

    public function test_is_low_stock_returns_false_when_no_stock_record(): void
    {
        $item = InventoryItem::factory()->create([
            'tenant_id' => $this->tenant->id,
            'unit_id' => $this->unit->id,
            'reorder_point' => 20,
        ]);

        // No stock record created
        $this->assertFalse($item->isLowStock($this->outlet->id));
    }

    // ============================================================
    // BATCH TRACKING TESTS
    // ============================================================

    public function test_inventory_item_can_track_batches(): void
    {
        $item = InventoryItem::factory()->trackBatches()->create([
            'tenant_id' => $this->tenant->id,
            'unit_id' => $this->unit->id,
        ]);

        $this->assertTrue($item->track_batches);
    }

    public function test_inventory_item_has_many_batches(): void
    {
        $item = InventoryItem::factory()->trackBatches()->create([
            'tenant_id' => $this->tenant->id,
            'unit_id' => $this->unit->id,
        ]);

        StockBatch::factory()->count(3)->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'inventory_item_id' => $item->id,
        ]);

        $this->assertCount(3, $item->batches);
        $this->assertCount(3, $item->stockBatches);
    }

    // ============================================================
    // STOCK MOVEMENT TESTS
    // ============================================================

    public function test_inventory_item_has_many_movements(): void
    {
        $item = InventoryItem::factory()->create([
            'tenant_id' => $this->tenant->id,
            'unit_id' => $this->unit->id,
        ]);

        StockMovement::factory()->count(5)->create([
            'outlet_id' => $this->outlet->id,
            'inventory_item_id' => $item->id,
        ]);

        $this->assertCount(5, $item->movements);
    }

    // ============================================================
    // SUPPLIER RELATIONSHIP TESTS
    // ============================================================

    public function test_inventory_item_has_many_supplier_items(): void
    {
        $item = InventoryItem::factory()->create([
            'tenant_id' => $this->tenant->id,
            'unit_id' => $this->unit->id,
        ]);

        $supplier1 = Supplier::factory()->create(['tenant_id' => $this->tenant->id]);
        $supplier2 = Supplier::factory()->create(['tenant_id' => $this->tenant->id]);

        SupplierItem::factory()->create([
            'supplier_id' => $supplier1->id,
            'inventory_item_id' => $item->id,
            'unit_id' => $this->unit->id,
            'is_preferred' => false,
        ]);

        SupplierItem::factory()->create([
            'supplier_id' => $supplier2->id,
            'inventory_item_id' => $item->id,
            'unit_id' => $this->unit->id,
            'is_preferred' => true,
        ]);

        $this->assertCount(2, $item->supplierItems);
    }

    public function test_inventory_item_can_have_preferred_supplier(): void
    {
        $item = InventoryItem::factory()->create([
            'tenant_id' => $this->tenant->id,
            'unit_id' => $this->unit->id,
        ]);

        $supplier = Supplier::factory()->create(['tenant_id' => $this->tenant->id]);

        SupplierItem::factory()->create([
            'supplier_id' => $supplier->id,
            'inventory_item_id' => $item->id,
            'unit_id' => $this->unit->id,
            'is_preferred' => true,
        ]);

        $this->assertInstanceOf(SupplierItem::class, $item->preferredSupplier);
        $this->assertTrue($item->preferredSupplier->is_preferred);
    }

    // ============================================================
    // CASTING TESTS
    // ============================================================

    public function test_decimal_fields_are_properly_cast(): void
    {
        $item = InventoryItem::factory()->create([
            'tenant_id' => $this->tenant->id,
            'unit_id' => $this->unit->id,
            'cost_price' => 15000.50,
            'min_stock' => 10.2500,
            'max_stock' => 100.5000,
        ]);

        $this->assertIsString($item->cost_price);
        $this->assertIsString($item->min_stock);
        $this->assertIsString($item->max_stock);
    }

    public function test_boolean_fields_are_properly_cast(): void
    {
        $item = InventoryItem::factory()->create([
            'tenant_id' => $this->tenant->id,
            'unit_id' => $this->unit->id,
            'is_perishable' => 1,
            'track_batches' => 0,
            'is_active' => 1,
        ]);

        $this->assertTrue($item->is_perishable);
        $this->assertFalse($item->track_batches);
        $this->assertTrue($item->is_active);
    }

    // ============================================================
    // FACTORY STATE TESTS
    // ============================================================

    public function test_inactive_factory_state(): void
    {
        $item = InventoryItem::factory()->inactive()->create([
            'tenant_id' => $this->tenant->id,
            'unit_id' => $this->unit->id,
        ]);

        $this->assertFalse($item->is_active);
    }

    public function test_track_batches_factory_state(): void
    {
        $item = InventoryItem::factory()->trackBatches()->create([
            'tenant_id' => $this->tenant->id,
            'unit_id' => $this->unit->id,
        ]);

        $this->assertTrue($item->track_batches);
    }
}
