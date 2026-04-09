<?php

namespace Tests\Unit\Models;

use App\Models\InventoryItem;
use App\Models\Outlet;
use App\Models\StockBatch;
use App\Models\StockMovement;
use App\Models\Tenant;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockMovementTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;

    protected Outlet $outlet;

    protected Unit $unit;

    protected InventoryItem $item;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create();
        $this->outlet = Outlet::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->unit = Unit::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->user = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->item = InventoryItem::factory()->create([
            'tenant_id' => $this->tenant->id,
            'unit_id' => $this->unit->id,
        ]);
    }

    // ============================================================
    // BASIC CREATION TESTS
    // ============================================================

    public function test_can_create_stock_movement(): void
    {
        $movement = StockMovement::factory()->create([
            'outlet_id' => $this->outlet->id,
            'inventory_item_id' => $this->item->id,
            'created_by' => $this->user->id,
            'type' => StockMovement::TYPE_IN,
            'quantity' => 50,
        ]);

        $this->assertDatabaseHas('stock_movements', [
            'id' => $movement->id,
            'type' => StockMovement::TYPE_IN,
        ]);
    }

    public function test_stock_movement_belongs_to_outlet(): void
    {
        $movement = StockMovement::factory()->create([
            'outlet_id' => $this->outlet->id,
            'inventory_item_id' => $this->item->id,
            'created_by' => $this->user->id,
        ]);

        $this->assertInstanceOf(Outlet::class, $movement->outlet);
        $this->assertEquals($this->outlet->id, $movement->outlet->id);
    }

    public function test_stock_movement_belongs_to_inventory_item(): void
    {
        $movement = StockMovement::factory()->create([
            'outlet_id' => $this->outlet->id,
            'inventory_item_id' => $this->item->id,
            'created_by' => $this->user->id,
        ]);

        $this->assertInstanceOf(InventoryItem::class, $movement->inventoryItem);
        $this->assertEquals($this->item->id, $movement->inventoryItem->id);
    }

    public function test_stock_movement_belongs_to_user(): void
    {
        $movement = StockMovement::factory()->create([
            'outlet_id' => $this->outlet->id,
            'inventory_item_id' => $this->item->id,
            'created_by' => $this->user->id,
        ]);

        $this->assertInstanceOf(User::class, $movement->createdBy);
        $this->assertEquals($this->user->id, $movement->createdBy->id);
    }

    public function test_stock_movement_can_belong_to_batch(): void
    {
        $batch = StockBatch::factory()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'inventory_item_id' => $this->item->id,
        ]);

        $movement = StockMovement::factory()->create([
            'outlet_id' => $this->outlet->id,
            'inventory_item_id' => $this->item->id,
            'batch_id' => $batch->id,
            'created_by' => $this->user->id,
        ]);

        $this->assertInstanceOf(StockBatch::class, $movement->batch);
        $this->assertEquals($batch->id, $movement->batch->id);
    }

    // ============================================================
    // MOVEMENT TYPE TESTS
    // ============================================================

    public function test_movement_type_constants(): void
    {
        $this->assertEquals('in', StockMovement::TYPE_IN);
        $this->assertEquals('out', StockMovement::TYPE_OUT);
        $this->assertEquals('adjustment', StockMovement::TYPE_ADJUSTMENT);
        $this->assertEquals('transfer_in', StockMovement::TYPE_TRANSFER_IN);
        $this->assertEquals('transfer_out', StockMovement::TYPE_TRANSFER_OUT);
        $this->assertEquals('waste', StockMovement::TYPE_WASTE);
    }

    // ============================================================
    // IS INCOMING TESTS
    // ============================================================

    public function test_is_incoming_returns_true_for_type_in(): void
    {
        $movement = StockMovement::factory()->incoming()->create([
            'outlet_id' => $this->outlet->id,
            'inventory_item_id' => $this->item->id,
            'created_by' => $this->user->id,
            'quantity' => 50,
        ]);

        $this->assertTrue($movement->isIncoming());
    }

    public function test_is_incoming_returns_true_for_transfer_in(): void
    {
        $movement = StockMovement::factory()->transferIn()->create([
            'outlet_id' => $this->outlet->id,
            'inventory_item_id' => $this->item->id,
            'created_by' => $this->user->id,
            'quantity' => 50,
        ]);

        $this->assertTrue($movement->isIncoming());
    }

    public function test_is_incoming_returns_true_for_positive_adjustment(): void
    {
        $movement = StockMovement::factory()->adjustment()->create([
            'outlet_id' => $this->outlet->id,
            'inventory_item_id' => $this->item->id,
            'created_by' => $this->user->id,
            'quantity' => 50, // Positive
        ]);

        $this->assertTrue($movement->isIncoming());
    }

    public function test_is_incoming_returns_false_for_type_out(): void
    {
        $movement = StockMovement::factory()->outgoing()->create([
            'outlet_id' => $this->outlet->id,
            'inventory_item_id' => $this->item->id,
            'created_by' => $this->user->id,
        ]);

        $this->assertFalse($movement->isIncoming());
    }

    // ============================================================
    // IS OUTGOING TESTS
    // ============================================================

    public function test_is_outgoing_returns_true_for_type_out(): void
    {
        $movement = StockMovement::factory()->outgoing()->create([
            'outlet_id' => $this->outlet->id,
            'inventory_item_id' => $this->item->id,
            'created_by' => $this->user->id,
        ]);

        $this->assertTrue($movement->isOutgoing());
    }

    public function test_is_outgoing_returns_true_for_transfer_out(): void
    {
        $movement = StockMovement::factory()->transferOut()->create([
            'outlet_id' => $this->outlet->id,
            'inventory_item_id' => $this->item->id,
            'created_by' => $this->user->id,
        ]);

        $this->assertTrue($movement->isOutgoing());
    }

    public function test_is_outgoing_returns_true_for_waste(): void
    {
        $movement = StockMovement::factory()->waste()->create([
            'outlet_id' => $this->outlet->id,
            'inventory_item_id' => $this->item->id,
            'created_by' => $this->user->id,
        ]);

        $this->assertTrue($movement->isOutgoing());
    }

    public function test_is_outgoing_returns_true_for_negative_quantity(): void
    {
        $movement = StockMovement::factory()->create([
            'outlet_id' => $this->outlet->id,
            'inventory_item_id' => $this->item->id,
            'created_by' => $this->user->id,
            'type' => StockMovement::TYPE_ADJUSTMENT,
            'quantity' => -20, // Negative adjustment
        ]);

        $this->assertTrue($movement->isOutgoing());
    }

    public function test_is_outgoing_returns_false_for_type_in(): void
    {
        $movement = StockMovement::factory()->incoming()->create([
            'outlet_id' => $this->outlet->id,
            'inventory_item_id' => $this->item->id,
            'created_by' => $this->user->id,
            'quantity' => 50,
        ]);

        $this->assertFalse($movement->isOutgoing());
    }

    // ============================================================
    // MOVEMENT VALUE TESTS
    // ============================================================

    public function test_get_movement_value_for_positive_quantity(): void
    {
        $movement = StockMovement::factory()->create([
            'outlet_id' => $this->outlet->id,
            'inventory_item_id' => $this->item->id,
            'created_by' => $this->user->id,
            'quantity' => 50,
            'cost_price' => 10000,
        ]);

        $this->assertEquals(500000, $movement->getMovementValue());
    }

    public function test_get_movement_value_for_negative_quantity(): void
    {
        $movement = StockMovement::factory()->create([
            'outlet_id' => $this->outlet->id,
            'inventory_item_id' => $this->item->id,
            'created_by' => $this->user->id,
            'quantity' => -30,
            'cost_price' => 15000,
        ]);

        // Should return absolute value
        $this->assertEquals(450000, $movement->getMovementValue());
    }

    public function test_get_movement_value_with_decimal_quantity(): void
    {
        $movement = StockMovement::factory()->create([
            'outlet_id' => $this->outlet->id,
            'inventory_item_id' => $this->item->id,
            'created_by' => $this->user->id,
            'quantity' => 2.5,
            'cost_price' => 10000,
        ]);

        $this->assertEquals(25000, $movement->getMovementValue());
    }

    // ============================================================
    // CASTING TESTS
    // ============================================================

    public function test_decimal_fields_are_properly_cast(): void
    {
        $movement = StockMovement::factory()->create([
            'outlet_id' => $this->outlet->id,
            'inventory_item_id' => $this->item->id,
            'created_by' => $this->user->id,
            'quantity' => 50.5000,
            'cost_price' => 10000.50,
            'stock_before' => 100.0000,
            'stock_after' => 150.5000,
        ]);

        $this->assertIsString($movement->quantity);
        $this->assertIsString($movement->cost_price);
        $this->assertIsString($movement->stock_before);
        $this->assertIsString($movement->stock_after);
    }

    // ============================================================
    // FACTORY STATE TESTS
    // ============================================================

    public function test_incoming_factory_state(): void
    {
        $movement = StockMovement::factory()->incoming()->create([
            'outlet_id' => $this->outlet->id,
            'inventory_item_id' => $this->item->id,
            'created_by' => $this->user->id,
        ]);

        $this->assertEquals(StockMovement::TYPE_IN, $movement->type);
        $this->assertGreaterThan(0, $movement->quantity);
    }

    public function test_outgoing_factory_state(): void
    {
        $movement = StockMovement::factory()->outgoing()->create([
            'outlet_id' => $this->outlet->id,
            'inventory_item_id' => $this->item->id,
            'created_by' => $this->user->id,
        ]);

        $this->assertEquals(StockMovement::TYPE_OUT, $movement->type);
        $this->assertLessThan(0, $movement->quantity);
    }

    public function test_waste_factory_state(): void
    {
        $movement = StockMovement::factory()->waste()->create([
            'outlet_id' => $this->outlet->id,
            'inventory_item_id' => $this->item->id,
            'created_by' => $this->user->id,
        ]);

        $this->assertEquals(StockMovement::TYPE_WASTE, $movement->type);
        $this->assertLessThan(0, $movement->quantity);
    }
}
