<?php

namespace Tests\Unit\Models;

use App\Models\InventoryItem;
use App\Models\Outlet;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Supplier;
use App\Models\Tenant;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurchaseOrderTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;

    protected Outlet $outlet;

    protected Supplier $supplier;

    protected Unit $unit;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create();
        $this->outlet = Outlet::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->supplier = Supplier::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->unit = Unit::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->user = User::factory()->create(['tenant_id' => $this->tenant->id]);
    }

    // ============================================================
    // BASIC CREATION TESTS
    // ============================================================

    public function test_can_create_purchase_order(): void
    {
        $po = PurchaseOrder::factory()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'supplier_id' => $this->supplier->id,
            'created_by' => $this->user->id,
            'po_number' => 'PO-TEST-001',
        ]);

        $this->assertDatabaseHas('purchase_orders', [
            'id' => $po->id,
            'po_number' => 'PO-TEST-001',
        ]);
    }

    public function test_purchase_order_belongs_to_tenant(): void
    {
        $po = PurchaseOrder::factory()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'supplier_id' => $this->supplier->id,
            'created_by' => $this->user->id,
        ]);

        $this->assertInstanceOf(Tenant::class, $po->tenant);
        $this->assertEquals($this->tenant->id, $po->tenant->id);
    }

    public function test_purchase_order_belongs_to_outlet(): void
    {
        $po = PurchaseOrder::factory()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'supplier_id' => $this->supplier->id,
            'created_by' => $this->user->id,
        ]);

        $this->assertInstanceOf(Outlet::class, $po->outlet);
        $this->assertEquals($this->outlet->id, $po->outlet->id);
    }

    public function test_purchase_order_belongs_to_supplier(): void
    {
        $po = PurchaseOrder::factory()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'supplier_id' => $this->supplier->id,
            'created_by' => $this->user->id,
        ]);

        $this->assertInstanceOf(Supplier::class, $po->supplier);
        $this->assertEquals($this->supplier->id, $po->supplier->id);
    }

    public function test_purchase_order_belongs_to_created_by(): void
    {
        $po = PurchaseOrder::factory()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'supplier_id' => $this->supplier->id,
            'created_by' => $this->user->id,
        ]);

        $this->assertInstanceOf(User::class, $po->createdBy);
        $this->assertEquals($this->user->id, $po->createdBy->id);
    }

    // ============================================================
    // STATUS CONSTANTS TESTS
    // ============================================================

    public function test_status_constants(): void
    {
        $this->assertEquals('draft', PurchaseOrder::STATUS_DRAFT);
        $this->assertEquals('submitted', PurchaseOrder::STATUS_SUBMITTED);
        $this->assertEquals('approved', PurchaseOrder::STATUS_APPROVED);
        $this->assertEquals('partial', PurchaseOrder::STATUS_PARTIAL);
        $this->assertEquals('received', PurchaseOrder::STATUS_RECEIVED);
        $this->assertEquals('cancelled', PurchaseOrder::STATUS_CANCELLED);
    }

    // ============================================================
    // STATUS CHECK TESTS
    // ============================================================

    public function test_is_draft(): void
    {
        $po = PurchaseOrder::factory()->draft()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'supplier_id' => $this->supplier->id,
            'created_by' => $this->user->id,
        ]);

        $this->assertTrue($po->isDraft());
    }

    public function test_is_editable_when_draft(): void
    {
        $po = PurchaseOrder::factory()->draft()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'supplier_id' => $this->supplier->id,
            'created_by' => $this->user->id,
        ]);

        $this->assertTrue($po->isEditable());
    }

    public function test_is_editable_when_submitted(): void
    {
        $po = PurchaseOrder::factory()->submitted()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'supplier_id' => $this->supplier->id,
            'created_by' => $this->user->id,
        ]);

        $this->assertTrue($po->isEditable());
    }

    public function test_is_not_editable_when_approved(): void
    {
        $po = PurchaseOrder::factory()->approved()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'supplier_id' => $this->supplier->id,
            'created_by' => $this->user->id,
        ]);

        $this->assertFalse($po->isEditable());
    }

    public function test_can_be_approved_when_submitted(): void
    {
        $po = PurchaseOrder::factory()->submitted()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'supplier_id' => $this->supplier->id,
            'created_by' => $this->user->id,
        ]);

        $this->assertTrue($po->canBeApproved());
    }

    public function test_cannot_be_approved_when_draft(): void
    {
        $po = PurchaseOrder::factory()->draft()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'supplier_id' => $this->supplier->id,
            'created_by' => $this->user->id,
        ]);

        $this->assertFalse($po->canBeApproved());
    }

    public function test_can_be_received_when_approved(): void
    {
        $po = PurchaseOrder::factory()->approved()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'supplier_id' => $this->supplier->id,
            'created_by' => $this->user->id,
        ]);

        $this->assertTrue($po->canBeReceived());
    }

    public function test_can_be_received_when_partial(): void
    {
        $po = PurchaseOrder::factory()->partial()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'supplier_id' => $this->supplier->id,
            'created_by' => $this->user->id,
        ]);

        $this->assertTrue($po->canBeReceived());
    }

    public function test_cannot_be_received_when_draft(): void
    {
        $po = PurchaseOrder::factory()->draft()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'supplier_id' => $this->supplier->id,
            'created_by' => $this->user->id,
        ]);

        $this->assertFalse($po->canBeReceived());
    }

    // ============================================================
    // ITEMS RELATIONSHIP TESTS
    // ============================================================

    public function test_purchase_order_has_many_items(): void
    {
        $po = PurchaseOrder::factory()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'supplier_id' => $this->supplier->id,
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

        PurchaseOrderItem::factory()->create([
            'purchase_order_id' => $po->id,
            'inventory_item_id' => $item1->id,
            'unit_id' => $this->unit->id,
        ]);

        PurchaseOrderItem::factory()->create([
            'purchase_order_id' => $po->id,
            'inventory_item_id' => $item2->id,
            'unit_id' => $this->unit->id,
        ]);

        $this->assertCount(2, $po->items);
    }

    // ============================================================
    // FULLY RECEIVED TESTS
    // ============================================================

    public function test_is_fully_received_returns_true_when_all_items_received(): void
    {
        $po = PurchaseOrder::factory()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'supplier_id' => $this->supplier->id,
            'created_by' => $this->user->id,
        ]);

        $item = InventoryItem::factory()->create([
            'tenant_id' => $this->tenant->id,
            'unit_id' => $this->unit->id,
        ]);

        PurchaseOrderItem::factory()->fullyReceived()->create([
            'purchase_order_id' => $po->id,
            'inventory_item_id' => $item->id,
            'unit_id' => $this->unit->id,
            'quantity' => 50,
            'received_qty' => 50,
        ]);

        $this->assertTrue($po->isFullyReceived());
    }

    public function test_is_fully_received_returns_false_when_partial(): void
    {
        $po = PurchaseOrder::factory()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'supplier_id' => $this->supplier->id,
            'created_by' => $this->user->id,
        ]);

        $item = InventoryItem::factory()->create([
            'tenant_id' => $this->tenant->id,
            'unit_id' => $this->unit->id,
        ]);

        PurchaseOrderItem::factory()->partiallyReceived()->create([
            'purchase_order_id' => $po->id,
            'inventory_item_id' => $item->id,
            'unit_id' => $this->unit->id,
            'quantity' => 50,
            'received_qty' => 25,
        ]);

        $this->assertFalse($po->isFullyReceived());
    }

    public function test_is_fully_received_returns_false_when_not_received(): void
    {
        $po = PurchaseOrder::factory()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'supplier_id' => $this->supplier->id,
            'created_by' => $this->user->id,
        ]);

        $item = InventoryItem::factory()->create([
            'tenant_id' => $this->tenant->id,
            'unit_id' => $this->unit->id,
        ]);

        PurchaseOrderItem::factory()->notReceived()->create([
            'purchase_order_id' => $po->id,
            'inventory_item_id' => $item->id,
            'unit_id' => $this->unit->id,
            'quantity' => 50,
        ]);

        $this->assertFalse($po->isFullyReceived());
    }

    // ============================================================
    // CALCULATE TOTALS TESTS
    // ============================================================

    public function test_calculate_totals(): void
    {
        $po = PurchaseOrder::factory()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'supplier_id' => $this->supplier->id,
            'created_by' => $this->user->id,
            'subtotal' => 0,
            'tax_amount' => 0,
            'discount_amount' => 0,
            'total' => 0,
        ]);

        $item = InventoryItem::factory()->create([
            'tenant_id' => $this->tenant->id,
            'unit_id' => $this->unit->id,
        ]);

        PurchaseOrderItem::factory()->create([
            'purchase_order_id' => $po->id,
            'inventory_item_id' => $item->id,
            'unit_id' => $this->unit->id,
            'total' => 100000,
            'tax_amount' => 11000,
            'discount_amount' => 5000,
        ]);

        PurchaseOrderItem::factory()->create([
            'purchase_order_id' => $po->id,
            'inventory_item_id' => $item->id,
            'unit_id' => $this->unit->id,
            'total' => 200000,
            'tax_amount' => 22000,
            'discount_amount' => 10000,
        ]);

        $po->calculateTotals();

        $this->assertEquals(300000, $po->subtotal);
        $this->assertEquals(33000, $po->tax_amount);
        $this->assertEquals(15000, $po->discount_amount);
        $this->assertEquals(318000, $po->total);
    }

    // ============================================================
    // APPROVAL RELATIONSHIP TESTS
    // ============================================================

    public function test_purchase_order_can_have_approver(): void
    {
        $approver = User::factory()->create(['tenant_id' => $this->tenant->id]);

        $po = PurchaseOrder::factory()->approved()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'supplier_id' => $this->supplier->id,
            'created_by' => $this->user->id,
            'approved_by' => $approver->id,
        ]);

        $this->assertInstanceOf(User::class, $po->approvedBy);
        $this->assertEquals($approver->id, $po->approvedBy->id);
    }

    // ============================================================
    // FACTORY STATE TESTS
    // ============================================================

    public function test_draft_factory_state(): void
    {
        $po = PurchaseOrder::factory()->draft()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'supplier_id' => $this->supplier->id,
            'created_by' => $this->user->id,
        ]);

        $this->assertEquals(PurchaseOrder::STATUS_DRAFT, $po->status);
    }

    public function test_approved_factory_state(): void
    {
        $po = PurchaseOrder::factory()->approved()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'supplier_id' => $this->supplier->id,
            'created_by' => $this->user->id,
        ]);

        $this->assertEquals(PurchaseOrder::STATUS_APPROVED, $po->status);
        $this->assertNotNull($po->approved_by);
        $this->assertNotNull($po->approved_at);
    }

    public function test_partial_factory_state(): void
    {
        $po = PurchaseOrder::factory()->partial()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'supplier_id' => $this->supplier->id,
            'created_by' => $this->user->id,
        ]);

        $this->assertEquals(PurchaseOrder::STATUS_PARTIAL, $po->status);
    }

    public function test_received_factory_state(): void
    {
        $po = PurchaseOrder::factory()->received()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'supplier_id' => $this->supplier->id,
            'created_by' => $this->user->id,
        ]);

        $this->assertEquals(PurchaseOrder::STATUS_RECEIVED, $po->status);
    }

    public function test_cancelled_factory_state(): void
    {
        $po = PurchaseOrder::factory()->cancelled()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'supplier_id' => $this->supplier->id,
            'created_by' => $this->user->id,
        ]);

        $this->assertEquals(PurchaseOrder::STATUS_CANCELLED, $po->status);
    }
}
