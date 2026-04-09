<?php

namespace Tests\Unit\Models;

use App\Models\InventoryItem;
use App\Models\InventoryStock;
use App\Models\Outlet;
use App\Models\Tenant;
use App\Models\Unit;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryStockTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;

    protected Outlet $outlet;

    protected Unit $unit;

    protected InventoryItem $item;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create();
        $this->outlet = Outlet::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->unit = Unit::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->item = InventoryItem::factory()->create([
            'tenant_id' => $this->tenant->id,
            'unit_id' => $this->unit->id,
            'reorder_point' => 20,
            'max_stock' => 200,
        ]);
    }

    // ============================================================
    // BASIC CREATION TESTS
    // ============================================================

    public function test_can_create_inventory_stock(): void
    {
        $stock = InventoryStock::factory()->create([
            'outlet_id' => $this->outlet->id,
            'inventory_item_id' => $this->item->id,
            'quantity' => 100,
            'avg_cost' => 10000,
        ]);

        $this->assertDatabaseHas('inventory_stocks', [
            'id' => $stock->id,
            'outlet_id' => $this->outlet->id,
            'inventory_item_id' => $this->item->id,
        ]);
    }

    public function test_inventory_stock_belongs_to_outlet(): void
    {
        $stock = InventoryStock::factory()->create([
            'outlet_id' => $this->outlet->id,
            'inventory_item_id' => $this->item->id,
        ]);

        $this->assertInstanceOf(Outlet::class, $stock->outlet);
        $this->assertEquals($this->outlet->id, $stock->outlet->id);
    }

    public function test_inventory_stock_belongs_to_inventory_item(): void
    {
        $stock = InventoryStock::factory()->create([
            'outlet_id' => $this->outlet->id,
            'inventory_item_id' => $this->item->id,
        ]);

        $this->assertInstanceOf(InventoryItem::class, $stock->inventoryItem);
        $this->assertEquals($this->item->id, $stock->inventoryItem->id);
    }

    // ============================================================
    // AVAILABLE QUANTITY TESTS
    // ============================================================

    public function test_get_available_quantity_with_no_reservation(): void
    {
        $stock = InventoryStock::factory()->create([
            'outlet_id' => $this->outlet->id,
            'inventory_item_id' => $this->item->id,
            'quantity' => 100,
            'reserved_qty' => 0,
        ]);

        $this->assertEquals(100, $stock->getAvailableQuantity());
    }

    public function test_get_available_quantity_with_reservation(): void
    {
        $stock = InventoryStock::factory()->create([
            'outlet_id' => $this->outlet->id,
            'inventory_item_id' => $this->item->id,
            'quantity' => 100,
            'reserved_qty' => 30,
        ]);

        $this->assertEquals(70, $stock->getAvailableQuantity());
    }

    public function test_get_available_quantity_all_reserved(): void
    {
        $stock = InventoryStock::factory()->create([
            'outlet_id' => $this->outlet->id,
            'inventory_item_id' => $this->item->id,
            'quantity' => 50,
            'reserved_qty' => 50,
        ]);

        $this->assertEquals(0, $stock->getAvailableQuantity());
    }

    // ============================================================
    // STOCK VALUE TESTS
    // ============================================================

    public function test_get_stock_value(): void
    {
        $stock = InventoryStock::factory()->create([
            'outlet_id' => $this->outlet->id,
            'inventory_item_id' => $this->item->id,
            'quantity' => 100,
            'avg_cost' => 15000,
        ]);

        $this->assertEquals(1500000, $stock->getStockValue());
    }

    public function test_get_stock_value_with_zero_quantity(): void
    {
        $stock = InventoryStock::factory()->create([
            'outlet_id' => $this->outlet->id,
            'inventory_item_id' => $this->item->id,
            'quantity' => 0,
            'avg_cost' => 15000,
        ]);

        $this->assertEquals(0, $stock->getStockValue());
    }

    public function test_get_stock_value_with_decimal_quantity(): void
    {
        $stock = InventoryStock::factory()->create([
            'outlet_id' => $this->outlet->id,
            'inventory_item_id' => $this->item->id,
            'quantity' => 10.5,
            'avg_cost' => 10000,
        ]);

        $this->assertEquals(105000, $stock->getStockValue());
    }

    // ============================================================
    // LOW STOCK TESTS
    // ============================================================

    public function test_is_low_stock_returns_true_when_below_reorder_point(): void
    {
        $stock = InventoryStock::factory()->create([
            'outlet_id' => $this->outlet->id,
            'inventory_item_id' => $this->item->id,
            'quantity' => 15, // Below reorder point (20)
        ]);

        $this->assertTrue($stock->isLowStock());
    }

    public function test_is_low_stock_returns_true_when_at_reorder_point(): void
    {
        $stock = InventoryStock::factory()->create([
            'outlet_id' => $this->outlet->id,
            'inventory_item_id' => $this->item->id,
            'quantity' => 20, // Equal to reorder point
        ]);

        $this->assertTrue($stock->isLowStock());
    }

    public function test_is_low_stock_returns_false_when_above_reorder_point(): void
    {
        $stock = InventoryStock::factory()->create([
            'outlet_id' => $this->outlet->id,
            'inventory_item_id' => $this->item->id,
            'quantity' => 50, // Above reorder point
        ]);

        $this->assertFalse($stock->isLowStock());
    }

    // ============================================================
    // OVER STOCK TESTS
    // ============================================================

    public function test_is_over_stock_returns_true_when_above_max(): void
    {
        $stock = InventoryStock::factory()->create([
            'outlet_id' => $this->outlet->id,
            'inventory_item_id' => $this->item->id,
            'quantity' => 250, // Above max_stock (200)
        ]);

        $this->assertTrue($stock->isOverStock());
    }

    public function test_is_over_stock_returns_false_when_at_max(): void
    {
        $stock = InventoryStock::factory()->create([
            'outlet_id' => $this->outlet->id,
            'inventory_item_id' => $this->item->id,
            'quantity' => 200, // Equal to max_stock
        ]);

        $this->assertFalse($stock->isOverStock());
    }

    public function test_is_over_stock_returns_false_when_below_max(): void
    {
        $stock = InventoryStock::factory()->create([
            'outlet_id' => $this->outlet->id,
            'inventory_item_id' => $this->item->id,
            'quantity' => 100, // Below max_stock
        ]);

        $this->assertFalse($stock->isOverStock());
    }

    public function test_is_over_stock_returns_false_when_no_max_set(): void
    {
        $itemNoMax = InventoryItem::factory()->create([
            'tenant_id' => $this->tenant->id,
            'unit_id' => $this->unit->id,
            'max_stock' => null,
        ]);

        $stock = InventoryStock::factory()->create([
            'outlet_id' => $this->outlet->id,
            'inventory_item_id' => $itemNoMax->id,
            'quantity' => 1000,
        ]);

        $this->assertFalse($stock->isOverStock());
    }

    // ============================================================
    // CASTING TESTS
    // ============================================================

    public function test_decimal_fields_are_properly_cast(): void
    {
        $stock = InventoryStock::factory()->create([
            'outlet_id' => $this->outlet->id,
            'inventory_item_id' => $this->item->id,
            'quantity' => 100.5000,
            'reserved_qty' => 10.2500,
            'avg_cost' => 15000.50,
            'last_cost' => 15500.00,
        ]);

        $this->assertIsString($stock->quantity);
        $this->assertIsString($stock->reserved_qty);
        $this->assertIsString($stock->avg_cost);
        $this->assertIsString($stock->last_cost);
    }

    public function test_datetime_fields_are_properly_cast(): void
    {
        $stock = InventoryStock::factory()->create([
            'outlet_id' => $this->outlet->id,
            'inventory_item_id' => $this->item->id,
            'last_received_at' => now(),
            'last_issued_at' => now(),
        ]);

        $this->assertInstanceOf(Carbon::class, $stock->last_received_at);
        $this->assertInstanceOf(Carbon::class, $stock->last_issued_at);
    }

    // ============================================================
    // FACTORY STATE TESTS
    // ============================================================

    public function test_low_stock_factory_state(): void
    {
        $stock = InventoryStock::factory()->lowStock()->create([
            'outlet_id' => $this->outlet->id,
            'inventory_item_id' => $this->item->id,
        ]);

        $this->assertLessThanOrEqual(10, $stock->quantity);
    }

    public function test_out_of_stock_factory_state(): void
    {
        $stock = InventoryStock::factory()->outOfStock()->create([
            'outlet_id' => $this->outlet->id,
            'inventory_item_id' => $this->item->id,
        ]);

        $this->assertEquals(0, $stock->quantity);
        $this->assertEquals(0, $stock->reserved_qty);
    }
}
