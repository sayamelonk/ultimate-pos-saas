<?php

namespace Tests\Unit\Models;

use App\Models\InventoryItem;
use App\Models\Outlet;
use App\Models\StockTransfer;
use App\Models\StockTransferItem;
use App\Models\Tenant;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockTransferTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;

    protected Outlet $fromOutlet;

    protected Outlet $toOutlet;

    protected Unit $unit;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create();
        $this->fromOutlet = Outlet::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->toOutlet = Outlet::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->unit = Unit::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->user = User::factory()->create(['tenant_id' => $this->tenant->id]);
    }

    // ============================================================
    // BASIC CREATION TESTS
    // ============================================================

    public function test_can_create_stock_transfer(): void
    {
        $transfer = StockTransfer::factory()->create([
            'tenant_id' => $this->tenant->id,
            'from_outlet_id' => $this->fromOutlet->id,
            'to_outlet_id' => $this->toOutlet->id,
            'created_by' => $this->user->id,
            'transfer_number' => 'TRF-TEST-001',
        ]);

        $this->assertDatabaseHas('stock_transfers', [
            'id' => $transfer->id,
            'transfer_number' => 'TRF-TEST-001',
        ]);
    }

    public function test_stock_transfer_belongs_to_tenant(): void
    {
        $transfer = StockTransfer::factory()->create([
            'tenant_id' => $this->tenant->id,
            'from_outlet_id' => $this->fromOutlet->id,
            'to_outlet_id' => $this->toOutlet->id,
            'created_by' => $this->user->id,
        ]);

        $this->assertInstanceOf(Tenant::class, $transfer->tenant);
        $this->assertEquals($this->tenant->id, $transfer->tenant->id);
    }

    public function test_stock_transfer_belongs_to_from_outlet(): void
    {
        $transfer = StockTransfer::factory()->create([
            'tenant_id' => $this->tenant->id,
            'from_outlet_id' => $this->fromOutlet->id,
            'to_outlet_id' => $this->toOutlet->id,
            'created_by' => $this->user->id,
        ]);

        $this->assertInstanceOf(Outlet::class, $transfer->fromOutlet);
        $this->assertEquals($this->fromOutlet->id, $transfer->fromOutlet->id);
    }

    public function test_stock_transfer_belongs_to_to_outlet(): void
    {
        $transfer = StockTransfer::factory()->create([
            'tenant_id' => $this->tenant->id,
            'from_outlet_id' => $this->fromOutlet->id,
            'to_outlet_id' => $this->toOutlet->id,
            'created_by' => $this->user->id,
        ]);

        $this->assertInstanceOf(Outlet::class, $transfer->toOutlet);
        $this->assertEquals($this->toOutlet->id, $transfer->toOutlet->id);
    }

    public function test_stock_transfer_belongs_to_created_by(): void
    {
        $transfer = StockTransfer::factory()->create([
            'tenant_id' => $this->tenant->id,
            'from_outlet_id' => $this->fromOutlet->id,
            'to_outlet_id' => $this->toOutlet->id,
            'created_by' => $this->user->id,
        ]);

        $this->assertInstanceOf(User::class, $transfer->createdBy);
        $this->assertEquals($this->user->id, $transfer->createdBy->id);
    }

    // ============================================================
    // STATUS CONSTANTS TESTS
    // ============================================================

    public function test_status_constants(): void
    {
        $this->assertEquals('draft', StockTransfer::STATUS_DRAFT);
        $this->assertEquals('pending', StockTransfer::STATUS_PENDING);
        $this->assertEquals('in_transit', StockTransfer::STATUS_IN_TRANSIT);
        $this->assertEquals('received', StockTransfer::STATUS_RECEIVED);
        $this->assertEquals('cancelled', StockTransfer::STATUS_CANCELLED);
    }

    // ============================================================
    // STATUS CHECK TESTS
    // ============================================================

    public function test_is_draft(): void
    {
        $transfer = StockTransfer::factory()->draft()->create([
            'tenant_id' => $this->tenant->id,
            'from_outlet_id' => $this->fromOutlet->id,
            'to_outlet_id' => $this->toOutlet->id,
            'created_by' => $this->user->id,
        ]);

        $this->assertTrue($transfer->isDraft());
        $this->assertFalse($transfer->isPending());
        $this->assertFalse($transfer->isInTransit());
        $this->assertFalse($transfer->isReceived());
    }

    public function test_is_pending(): void
    {
        $transfer = StockTransfer::factory()->pending()->create([
            'tenant_id' => $this->tenant->id,
            'from_outlet_id' => $this->fromOutlet->id,
            'to_outlet_id' => $this->toOutlet->id,
            'created_by' => $this->user->id,
        ]);

        $this->assertTrue($transfer->isPending());
        $this->assertFalse($transfer->isDraft());
    }

    public function test_is_in_transit(): void
    {
        $transfer = StockTransfer::factory()->inTransit()->create([
            'tenant_id' => $this->tenant->id,
            'from_outlet_id' => $this->fromOutlet->id,
            'to_outlet_id' => $this->toOutlet->id,
            'created_by' => $this->user->id,
        ]);

        $this->assertTrue($transfer->isInTransit());
        $this->assertFalse($transfer->isReceived());
    }

    public function test_is_received(): void
    {
        $transfer = StockTransfer::factory()->received()->create([
            'tenant_id' => $this->tenant->id,
            'from_outlet_id' => $this->fromOutlet->id,
            'to_outlet_id' => $this->toOutlet->id,
            'created_by' => $this->user->id,
        ]);

        $this->assertTrue($transfer->isReceived());
    }

    // ============================================================
    // CAN BE TESTS
    // ============================================================

    public function test_can_be_edited_when_draft(): void
    {
        $transfer = StockTransfer::factory()->draft()->create([
            'tenant_id' => $this->tenant->id,
            'from_outlet_id' => $this->fromOutlet->id,
            'to_outlet_id' => $this->toOutlet->id,
            'created_by' => $this->user->id,
        ]);

        $this->assertTrue($transfer->canBeEdited());
    }

    public function test_can_be_edited_when_pending(): void
    {
        $transfer = StockTransfer::factory()->pending()->create([
            'tenant_id' => $this->tenant->id,
            'from_outlet_id' => $this->fromOutlet->id,
            'to_outlet_id' => $this->toOutlet->id,
            'created_by' => $this->user->id,
        ]);

        $this->assertTrue($transfer->canBeEdited());
    }

    public function test_cannot_be_edited_when_in_transit(): void
    {
        $transfer = StockTransfer::factory()->inTransit()->create([
            'tenant_id' => $this->tenant->id,
            'from_outlet_id' => $this->fromOutlet->id,
            'to_outlet_id' => $this->toOutlet->id,
            'created_by' => $this->user->id,
        ]);

        $this->assertFalse($transfer->canBeEdited());
    }

    public function test_can_be_approved_when_pending(): void
    {
        $transfer = StockTransfer::factory()->pending()->create([
            'tenant_id' => $this->tenant->id,
            'from_outlet_id' => $this->fromOutlet->id,
            'to_outlet_id' => $this->toOutlet->id,
            'created_by' => $this->user->id,
        ]);

        $this->assertTrue($transfer->canBeApproved());
    }

    public function test_cannot_be_approved_when_draft(): void
    {
        $transfer = StockTransfer::factory()->draft()->create([
            'tenant_id' => $this->tenant->id,
            'from_outlet_id' => $this->fromOutlet->id,
            'to_outlet_id' => $this->toOutlet->id,
            'created_by' => $this->user->id,
        ]);

        $this->assertFalse($transfer->canBeApproved());
    }

    public function test_can_be_received_when_in_transit(): void
    {
        $transfer = StockTransfer::factory()->inTransit()->create([
            'tenant_id' => $this->tenant->id,
            'from_outlet_id' => $this->fromOutlet->id,
            'to_outlet_id' => $this->toOutlet->id,
            'created_by' => $this->user->id,
        ]);

        $this->assertTrue($transfer->canBeReceived());
    }

    public function test_cannot_be_received_when_pending(): void
    {
        $transfer = StockTransfer::factory()->pending()->create([
            'tenant_id' => $this->tenant->id,
            'from_outlet_id' => $this->fromOutlet->id,
            'to_outlet_id' => $this->toOutlet->id,
            'created_by' => $this->user->id,
        ]);

        $this->assertFalse($transfer->canBeReceived());
    }

    // ============================================================
    // ITEMS RELATIONSHIP TESTS
    // ============================================================

    public function test_stock_transfer_has_many_items(): void
    {
        $transfer = StockTransfer::factory()->create([
            'tenant_id' => $this->tenant->id,
            'from_outlet_id' => $this->fromOutlet->id,
            'to_outlet_id' => $this->toOutlet->id,
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

        StockTransferItem::factory()->create([
            'stock_transfer_id' => $transfer->id,
            'inventory_item_id' => $item1->id,
            'unit_id' => $this->unit->id,
        ]);

        StockTransferItem::factory()->create([
            'stock_transfer_id' => $transfer->id,
            'inventory_item_id' => $item2->id,
            'unit_id' => $this->unit->id,
        ]);

        $this->assertCount(2, $transfer->items);
    }

    // ============================================================
    // TOTAL VALUE TESTS
    // ============================================================

    public function test_get_total_value(): void
    {
        $transfer = StockTransfer::factory()->create([
            'tenant_id' => $this->tenant->id,
            'from_outlet_id' => $this->fromOutlet->id,
            'to_outlet_id' => $this->toOutlet->id,
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

        StockTransferItem::factory()->create([
            'stock_transfer_id' => $transfer->id,
            'inventory_item_id' => $item1->id,
            'unit_id' => $this->unit->id,
            'quantity' => 10,
            'cost_price' => 10000,
        ]);

        StockTransferItem::factory()->create([
            'stock_transfer_id' => $transfer->id,
            'inventory_item_id' => $item2->id,
            'unit_id' => $this->unit->id,
            'quantity' => 5,
            'cost_price' => 20000,
        ]);

        $this->assertEquals(200000, $transfer->getTotalValue());
    }

    // ============================================================
    // APPROVAL/RECEIVE RELATIONSHIP TESTS
    // ============================================================

    public function test_stock_transfer_can_have_approver(): void
    {
        $approver = User::factory()->create(['tenant_id' => $this->tenant->id]);

        $transfer = StockTransfer::factory()->inTransit()->create([
            'tenant_id' => $this->tenant->id,
            'from_outlet_id' => $this->fromOutlet->id,
            'to_outlet_id' => $this->toOutlet->id,
            'created_by' => $this->user->id,
            'approved_by' => $approver->id,
        ]);

        $this->assertInstanceOf(User::class, $transfer->approvedBy);
        $this->assertEquals($approver->id, $transfer->approvedBy->id);
    }

    public function test_stock_transfer_can_have_receiver(): void
    {
        $receiver = User::factory()->create(['tenant_id' => $this->tenant->id]);

        $transfer = StockTransfer::factory()->received()->create([
            'tenant_id' => $this->tenant->id,
            'from_outlet_id' => $this->fromOutlet->id,
            'to_outlet_id' => $this->toOutlet->id,
            'created_by' => $this->user->id,
            'received_by' => $receiver->id,
        ]);

        $this->assertInstanceOf(User::class, $transfer->receivedBy);
        $this->assertEquals($receiver->id, $transfer->receivedBy->id);
    }

    // ============================================================
    // FACTORY STATE TESTS
    // ============================================================

    public function test_draft_factory_state(): void
    {
        $transfer = StockTransfer::factory()->draft()->create([
            'tenant_id' => $this->tenant->id,
            'from_outlet_id' => $this->fromOutlet->id,
            'to_outlet_id' => $this->toOutlet->id,
            'created_by' => $this->user->id,
        ]);

        $this->assertEquals(StockTransfer::STATUS_DRAFT, $transfer->status);
    }

    public function test_in_transit_factory_state(): void
    {
        $transfer = StockTransfer::factory()->inTransit()->create([
            'tenant_id' => $this->tenant->id,
            'from_outlet_id' => $this->fromOutlet->id,
            'to_outlet_id' => $this->toOutlet->id,
            'created_by' => $this->user->id,
        ]);

        $this->assertEquals(StockTransfer::STATUS_IN_TRANSIT, $transfer->status);
        $this->assertNotNull($transfer->approved_by);
        $this->assertNotNull($transfer->approved_at);
    }

    public function test_received_factory_state(): void
    {
        $transfer = StockTransfer::factory()->received()->create([
            'tenant_id' => $this->tenant->id,
            'from_outlet_id' => $this->fromOutlet->id,
            'to_outlet_id' => $this->toOutlet->id,
            'created_by' => $this->user->id,
        ]);

        $this->assertEquals(StockTransfer::STATUS_RECEIVED, $transfer->status);
        $this->assertNotNull($transfer->received_by);
        $this->assertNotNull($transfer->received_at);
    }

    public function test_cancelled_factory_state(): void
    {
        $transfer = StockTransfer::factory()->cancelled()->create([
            'tenant_id' => $this->tenant->id,
            'from_outlet_id' => $this->fromOutlet->id,
            'to_outlet_id' => $this->toOutlet->id,
            'created_by' => $this->user->id,
        ]);

        $this->assertEquals(StockTransfer::STATUS_CANCELLED, $transfer->status);
    }
}
