<?php

namespace Tests\Unit\Models;

use App\Models\InventoryItem;
use App\Models\Outlet;
use App\Models\StockAdjustment;
use App\Models\StockAdjustmentItem;
use App\Models\Tenant;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockAdjustmentTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;

    protected Outlet $outlet;

    protected Unit $unit;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create();
        $this->outlet = Outlet::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->unit = Unit::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->user = User::factory()->create(['tenant_id' => $this->tenant->id]);
    }

    // ============================================================
    // BASIC CREATION TESTS
    // ============================================================

    public function test_can_create_stock_adjustment(): void
    {
        $adjustment = StockAdjustment::factory()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'created_by' => $this->user->id,
            'adjustment_number' => 'ADJ-TEST-001',
        ]);

        $this->assertDatabaseHas('stock_adjustments', [
            'id' => $adjustment->id,
            'adjustment_number' => 'ADJ-TEST-001',
        ]);
    }

    public function test_stock_adjustment_belongs_to_tenant(): void
    {
        $adjustment = StockAdjustment::factory()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'created_by' => $this->user->id,
        ]);

        $this->assertInstanceOf(Tenant::class, $adjustment->tenant);
        $this->assertEquals($this->tenant->id, $adjustment->tenant->id);
    }

    public function test_stock_adjustment_belongs_to_outlet(): void
    {
        $adjustment = StockAdjustment::factory()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'created_by' => $this->user->id,
        ]);

        $this->assertInstanceOf(Outlet::class, $adjustment->outlet);
        $this->assertEquals($this->outlet->id, $adjustment->outlet->id);
    }

    public function test_stock_adjustment_belongs_to_created_by(): void
    {
        $adjustment = StockAdjustment::factory()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'created_by' => $this->user->id,
        ]);

        $this->assertInstanceOf(User::class, $adjustment->createdBy);
        $this->assertEquals($this->user->id, $adjustment->createdBy->id);
    }

    public function test_stock_adjustment_can_have_approver(): void
    {
        $approver = User::factory()->create(['tenant_id' => $this->tenant->id]);

        $adjustment = StockAdjustment::factory()->approved()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'created_by' => $this->user->id,
            'approved_by' => $approver->id,
        ]);

        $this->assertInstanceOf(User::class, $adjustment->approvedBy);
        $this->assertEquals($approver->id, $adjustment->approvedBy->id);
    }

    // ============================================================
    // STATUS CONSTANTS TESTS
    // ============================================================

    public function test_status_constants(): void
    {
        $this->assertEquals('draft', StockAdjustment::STATUS_DRAFT);
        $this->assertEquals('approved', StockAdjustment::STATUS_APPROVED);
        $this->assertEquals('cancelled', StockAdjustment::STATUS_CANCELLED);
    }

    public function test_type_constants(): void
    {
        $this->assertEquals('stock_take', StockAdjustment::TYPE_STOCK_TAKE);
        $this->assertEquals('correction', StockAdjustment::TYPE_CORRECTION);
        $this->assertEquals('opening_balance', StockAdjustment::TYPE_OPENING_BALANCE);
    }

    // ============================================================
    // STATUS CHECK TESTS
    // ============================================================

    public function test_is_draft_returns_true(): void
    {
        $adjustment = StockAdjustment::factory()->draft()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'created_by' => $this->user->id,
        ]);

        $this->assertTrue($adjustment->isDraft());
        $this->assertFalse($adjustment->isApproved());
    }

    public function test_is_approved_returns_true(): void
    {
        $adjustment = StockAdjustment::factory()->approved()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'created_by' => $this->user->id,
        ]);

        $this->assertTrue($adjustment->isApproved());
        $this->assertFalse($adjustment->isDraft());
    }

    // ============================================================
    // ITEMS RELATIONSHIP TESTS
    // ============================================================

    public function test_stock_adjustment_has_many_items(): void
    {
        $adjustment = StockAdjustment::factory()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'created_by' => $this->user->id,
        ]);

        $item1 = InventoryItem::factory()->create([
            'tenant_id' => $this->tenant->id,
            'unit_id' => $this->unit->id,
        ]);

        $item2 = InventoryItem::factory()->create([
            'tenant_id' => $this->tenant->id,
            'unit_id' => $this->unit->id,
        ]);

        StockAdjustmentItem::factory()->create([
            'stock_adjustment_id' => $adjustment->id,
            'inventory_item_id' => $item1->id,
        ]);

        StockAdjustmentItem::factory()->create([
            'stock_adjustment_id' => $adjustment->id,
            'inventory_item_id' => $item2->id,
        ]);

        $this->assertCount(2, $adjustment->items);
    }

    // ============================================================
    // VALUE CALCULATION TESTS
    // ============================================================

    public function test_get_total_value_difference(): void
    {
        $adjustment = StockAdjustment::factory()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'created_by' => $this->user->id,
        ]);

        $item1 = InventoryItem::factory()->create([
            'tenant_id' => $this->tenant->id,
            'unit_id' => $this->unit->id,
        ]);

        $item2 = InventoryItem::factory()->create([
            'tenant_id' => $this->tenant->id,
            'unit_id' => $this->unit->id,
        ]);

        StockAdjustmentItem::factory()->create([
            'stock_adjustment_id' => $adjustment->id,
            'inventory_item_id' => $item1->id,
            'value_difference' => 50000,
        ]);

        StockAdjustmentItem::factory()->create([
            'stock_adjustment_id' => $adjustment->id,
            'inventory_item_id' => $item2->id,
            'value_difference' => -20000,
        ]);

        $this->assertEquals(30000, $adjustment->getTotalValueDifference());
    }

    public function test_get_positive_adjustments(): void
    {
        $adjustment = StockAdjustment::factory()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'created_by' => $this->user->id,
        ]);

        $item1 = InventoryItem::factory()->create([
            'tenant_id' => $this->tenant->id,
            'unit_id' => $this->unit->id,
        ]);

        $item2 = InventoryItem::factory()->create([
            'tenant_id' => $this->tenant->id,
            'unit_id' => $this->unit->id,
        ]);

        StockAdjustmentItem::factory()->increase(10)->create([
            'stock_adjustment_id' => $adjustment->id,
            'inventory_item_id' => $item1->id,
            'difference' => 15,
        ]);

        StockAdjustmentItem::factory()->decrease(5)->create([
            'stock_adjustment_id' => $adjustment->id,
            'inventory_item_id' => $item2->id,
            'difference' => -5,
        ]);

        $this->assertEquals(15, $adjustment->getPositiveAdjustments());
    }

    public function test_get_negative_adjustments(): void
    {
        $adjustment = StockAdjustment::factory()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'created_by' => $this->user->id,
        ]);

        $item1 = InventoryItem::factory()->create([
            'tenant_id' => $this->tenant->id,
            'unit_id' => $this->unit->id,
        ]);

        $item2 = InventoryItem::factory()->create([
            'tenant_id' => $this->tenant->id,
            'unit_id' => $this->unit->id,
        ]);

        StockAdjustmentItem::factory()->create([
            'stock_adjustment_id' => $adjustment->id,
            'inventory_item_id' => $item1->id,
            'difference' => 10,
        ]);

        StockAdjustmentItem::factory()->create([
            'stock_adjustment_id' => $adjustment->id,
            'inventory_item_id' => $item2->id,
            'difference' => -8,
        ]);

        $this->assertEquals(8, $adjustment->getNegativeAdjustments());
    }

    // ============================================================
    // FACTORY STATE TESTS
    // ============================================================

    public function test_draft_factory_state(): void
    {
        $adjustment = StockAdjustment::factory()->draft()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'created_by' => $this->user->id,
        ]);

        $this->assertEquals(StockAdjustment::STATUS_DRAFT, $adjustment->status);
        $this->assertNull($adjustment->approved_by);
        $this->assertNull($adjustment->approved_at);
    }

    public function test_approved_factory_state(): void
    {
        $adjustment = StockAdjustment::factory()->approved()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'created_by' => $this->user->id,
        ]);

        $this->assertEquals(StockAdjustment::STATUS_APPROVED, $adjustment->status);
        $this->assertNotNull($adjustment->approved_by);
        $this->assertNotNull($adjustment->approved_at);
    }

    public function test_cancelled_factory_state(): void
    {
        $adjustment = StockAdjustment::factory()->cancelled()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'created_by' => $this->user->id,
        ]);

        $this->assertEquals(StockAdjustment::STATUS_CANCELLED, $adjustment->status);
    }

    public function test_stock_take_factory_state(): void
    {
        $adjustment = StockAdjustment::factory()->stockTake()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'created_by' => $this->user->id,
        ]);

        $this->assertEquals(StockAdjustment::TYPE_STOCK_TAKE, $adjustment->type);
    }

    public function test_correction_factory_state(): void
    {
        $adjustment = StockAdjustment::factory()->correction()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'created_by' => $this->user->id,
        ]);

        $this->assertEquals(StockAdjustment::TYPE_CORRECTION, $adjustment->type);
    }

    public function test_opening_balance_factory_state(): void
    {
        $adjustment = StockAdjustment::factory()->openingBalance()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'created_by' => $this->user->id,
        ]);

        $this->assertEquals(StockAdjustment::TYPE_OPENING_BALANCE, $adjustment->type);
    }
}
