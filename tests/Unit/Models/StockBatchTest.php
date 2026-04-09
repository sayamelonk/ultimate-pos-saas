<?php

namespace Tests\Unit\Models;

use App\Models\InventoryItem;
use App\Models\Outlet;
use App\Models\StockBatch;
use App\Models\Tenant;
use App\Models\Unit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockBatchTest extends TestCase
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
        $this->item = InventoryItem::factory()->trackBatches()->create([
            'tenant_id' => $this->tenant->id,
            'unit_id' => $this->unit->id,
        ]);
    }

    // ============================================================
    // BASIC CREATION TESTS
    // ============================================================

    public function test_can_create_stock_batch(): void
    {
        $batch = StockBatch::factory()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'inventory_item_id' => $this->item->id,
            'batch_number' => 'BTH-TEST-001',
        ]);

        $this->assertDatabaseHas('stock_batches', [
            'id' => $batch->id,
            'batch_number' => 'BTH-TEST-001',
        ]);
    }

    public function test_stock_batch_belongs_to_tenant(): void
    {
        $batch = StockBatch::factory()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'inventory_item_id' => $this->item->id,
        ]);

        $this->assertInstanceOf(Tenant::class, $batch->tenant);
        $this->assertEquals($this->tenant->id, $batch->tenant->id);
    }

    public function test_stock_batch_belongs_to_outlet(): void
    {
        $batch = StockBatch::factory()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'inventory_item_id' => $this->item->id,
        ]);

        $this->assertInstanceOf(Outlet::class, $batch->outlet);
        $this->assertEquals($this->outlet->id, $batch->outlet->id);
    }

    public function test_stock_batch_belongs_to_inventory_item(): void
    {
        $batch = StockBatch::factory()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'inventory_item_id' => $this->item->id,
        ]);

        $this->assertInstanceOf(InventoryItem::class, $batch->inventoryItem);
        $this->assertEquals($this->item->id, $batch->inventoryItem->id);
    }

    // ============================================================
    // STATUS CONSTANTS TESTS
    // ============================================================

    public function test_status_constants(): void
    {
        $this->assertEquals('active', StockBatch::STATUS_ACTIVE);
        $this->assertEquals('depleted', StockBatch::STATUS_DEPLETED);
        $this->assertEquals('expired', StockBatch::STATUS_EXPIRED);
        $this->assertEquals('disposed', StockBatch::STATUS_DISPOSED);
    }

    // ============================================================
    // EXPIRY TESTS
    // ============================================================

    public function test_is_expired_returns_true_when_past_expiry_date(): void
    {
        $batch = StockBatch::factory()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'inventory_item_id' => $this->item->id,
            'expiry_date' => now()->subDays(1),
        ]);

        $this->assertTrue($batch->isExpired());
    }

    public function test_is_expired_returns_false_when_future_expiry_date(): void
    {
        $batch = StockBatch::factory()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'inventory_item_id' => $this->item->id,
            'expiry_date' => now()->addDays(30),
        ]);

        $this->assertFalse($batch->isExpired());
    }

    public function test_is_expired_returns_false_when_no_expiry_date(): void
    {
        $batch = StockBatch::factory()->noExpiry()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'inventory_item_id' => $this->item->id,
        ]);

        $this->assertFalse($batch->isExpired());
    }

    public function test_is_expiring_soon_returns_true_within_range(): void
    {
        $batch = StockBatch::factory()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'inventory_item_id' => $this->item->id,
            'expiry_date' => now()->addDays(15),
        ]);

        $this->assertTrue($batch->isExpiringSoon(30)); // Within 30 days
    }

    public function test_is_expiring_soon_returns_false_outside_range(): void
    {
        $batch = StockBatch::factory()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'inventory_item_id' => $this->item->id,
            'expiry_date' => now()->addDays(60),
        ]);

        $this->assertFalse($batch->isExpiringSoon(30)); // Not within 30 days
    }

    public function test_is_expiring_soon_returns_false_when_no_expiry(): void
    {
        $batch = StockBatch::factory()->noExpiry()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'inventory_item_id' => $this->item->id,
        ]);

        $this->assertFalse($batch->isExpiringSoon(30));
    }

    public function test_days_until_expiry(): void
    {
        $batch = StockBatch::factory()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'inventory_item_id' => $this->item->id,
            'expiry_date' => now()->addDays(15),
        ]);

        // Allow for small timing differences
        $days = $batch->daysUntilExpiry();
        $this->assertGreaterThanOrEqual(14, $days);
        $this->assertLessThanOrEqual(15, $days);
    }

    public function test_days_until_expiry_returns_negative_when_expired(): void
    {
        $batch = StockBatch::factory()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'inventory_item_id' => $this->item->id,
            'expiry_date' => now()->subDays(5),
        ]);

        $this->assertEquals(-5, $batch->daysUntilExpiry());
    }

    public function test_days_until_expiry_returns_null_when_no_expiry(): void
    {
        $batch = StockBatch::factory()->noExpiry()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'inventory_item_id' => $this->item->id,
        ]);

        $this->assertNull($batch->daysUntilExpiry());
    }

    // ============================================================
    // EXPIRY STATUS TESTS
    // ============================================================

    public function test_get_expiry_status_returns_expired(): void
    {
        $batch = StockBatch::factory()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'inventory_item_id' => $this->item->id,
            'expiry_date' => now()->subDays(1),
        ]);

        $this->assertEquals('expired', $batch->getExpiryStatus());
    }

    public function test_get_expiry_status_returns_critical(): void
    {
        $batch = StockBatch::factory()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'inventory_item_id' => $this->item->id,
            'expiry_date' => now()->addDays(5),
        ]);

        $this->assertEquals('critical', $batch->getExpiryStatus());
    }

    public function test_get_expiry_status_returns_warning(): void
    {
        $batch = StockBatch::factory()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'inventory_item_id' => $this->item->id,
            'expiry_date' => now()->addDays(20),
        ]);

        $this->assertEquals('warning', $batch->getExpiryStatus());
    }

    public function test_get_expiry_status_returns_ok(): void
    {
        $batch = StockBatch::factory()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'inventory_item_id' => $this->item->id,
            'expiry_date' => now()->addDays(60),
        ]);

        $this->assertEquals('ok', $batch->getExpiryStatus());
    }

    public function test_get_expiry_status_returns_no_expiry(): void
    {
        $batch = StockBatch::factory()->noExpiry()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'inventory_item_id' => $this->item->id,
        ]);

        $this->assertEquals('no_expiry', $batch->getExpiryStatus());
    }

    // ============================================================
    // QUANTITY TESTS
    // ============================================================

    public function test_get_available_quantity(): void
    {
        $batch = StockBatch::factory()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'inventory_item_id' => $this->item->id,
            'current_quantity' => 100,
            'reserved_quantity' => 20,
        ]);

        $this->assertEquals(80, $batch->getAvailableQuantity());
    }

    public function test_get_available_quantity_never_negative(): void
    {
        $batch = StockBatch::factory()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'inventory_item_id' => $this->item->id,
            'current_quantity' => 10,
            'reserved_quantity' => 20,
        ]);

        $this->assertEquals(0, $batch->getAvailableQuantity());
    }

    public function test_is_depleted(): void
    {
        $batch = StockBatch::factory()->depleted()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'inventory_item_id' => $this->item->id,
        ]);

        $this->assertTrue($batch->isDepleted());
    }

    public function test_can_deduct_returns_true_when_sufficient(): void
    {
        $batch = StockBatch::factory()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'inventory_item_id' => $this->item->id,
            'current_quantity' => 100,
            'reserved_quantity' => 0,
        ]);

        $this->assertTrue($batch->canDeduct(50));
    }

    public function test_can_deduct_returns_false_when_insufficient(): void
    {
        $batch = StockBatch::factory()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'inventory_item_id' => $this->item->id,
            'current_quantity' => 30,
            'reserved_quantity' => 0,
        ]);

        $this->assertFalse($batch->canDeduct(50));
    }

    public function test_deduct_reduces_quantity(): void
    {
        $batch = StockBatch::factory()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'inventory_item_id' => $this->item->id,
            'current_quantity' => 100,
            'reserved_quantity' => 0,
            'status' => StockBatch::STATUS_ACTIVE,
        ]);

        $result = $batch->deduct(30);

        $this->assertTrue($result);
        $this->assertEquals(70, $batch->fresh()->current_quantity);
    }

    public function test_deduct_marks_depleted_when_empty(): void
    {
        $batch = StockBatch::factory()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'inventory_item_id' => $this->item->id,
            'current_quantity' => 50,
            'reserved_quantity' => 0,
            'status' => StockBatch::STATUS_ACTIVE,
        ]);

        $batch->deduct(50);

        $this->assertEquals(StockBatch::STATUS_DEPLETED, $batch->fresh()->status);
    }

    public function test_deduct_returns_false_when_insufficient(): void
    {
        $batch = StockBatch::factory()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'inventory_item_id' => $this->item->id,
            'current_quantity' => 30,
            'reserved_quantity' => 0,
        ]);

        $result = $batch->deduct(50);

        $this->assertFalse($result);
        $this->assertEquals(30, $batch->fresh()->current_quantity); // Unchanged
    }

    // ============================================================
    // RESERVATION TESTS
    // ============================================================

    public function test_reserve_increases_reserved_quantity(): void
    {
        $batch = StockBatch::factory()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'inventory_item_id' => $this->item->id,
            'current_quantity' => 100,
            'reserved_quantity' => 0,
        ]);

        $result = $batch->reserve(30);

        $this->assertTrue($result);
        $this->assertEquals(30, $batch->fresh()->reserved_quantity);
    }

    public function test_reserve_returns_false_when_insufficient(): void
    {
        $batch = StockBatch::factory()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'inventory_item_id' => $this->item->id,
            'current_quantity' => 50,
            'reserved_quantity' => 30,
        ]);

        $result = $batch->reserve(30); // Only 20 available

        $this->assertFalse($result);
        $this->assertEquals(30, $batch->fresh()->reserved_quantity); // Unchanged
    }

    public function test_release_reservation(): void
    {
        $batch = StockBatch::factory()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'inventory_item_id' => $this->item->id,
            'current_quantity' => 100,
            'reserved_quantity' => 30,
        ]);

        $result = $batch->releaseReservation(20);

        $this->assertTrue($result);
        $this->assertEquals(10, $batch->fresh()->reserved_quantity);
    }

    public function test_release_reservation_never_goes_negative(): void
    {
        $batch = StockBatch::factory()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'inventory_item_id' => $this->item->id,
            'current_quantity' => 100,
            'reserved_quantity' => 10,
        ]);

        $batch->releaseReservation(50); // More than reserved

        $this->assertEquals(0, $batch->fresh()->reserved_quantity);
    }

    // ============================================================
    // STATUS CHANGE TESTS
    // ============================================================

    public function test_mark_as_expired(): void
    {
        $batch = StockBatch::factory()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'inventory_item_id' => $this->item->id,
            'status' => StockBatch::STATUS_ACTIVE,
        ]);

        $batch->markAsExpired();

        $this->assertEquals(StockBatch::STATUS_EXPIRED, $batch->fresh()->status);
    }

    public function test_mark_as_disposed(): void
    {
        $batch = StockBatch::factory()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'inventory_item_id' => $this->item->id,
            'status' => StockBatch::STATUS_EXPIRED,
        ]);

        $batch->markAsDisposed();

        $this->assertEquals(StockBatch::STATUS_DISPOSED, $batch->fresh()->status);
    }

    // ============================================================
    // SCOPE TESTS
    // ============================================================

    public function test_scope_active(): void
    {
        StockBatch::factory()->active()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'inventory_item_id' => $this->item->id,
        ]);

        StockBatch::factory()->depleted()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'inventory_item_id' => $this->item->id,
        ]);

        $activeBatches = StockBatch::active()->get();

        $this->assertCount(1, $activeBatches);
    }

    public function test_scope_with_stock(): void
    {
        StockBatch::factory()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'inventory_item_id' => $this->item->id,
            'current_quantity' => 50,
        ]);

        StockBatch::factory()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'inventory_item_id' => $this->item->id,
            'current_quantity' => 0,
        ]);

        $batchesWithStock = StockBatch::withStock()->get();

        $this->assertCount(1, $batchesWithStock);
    }

    public function test_scope_for_outlet(): void
    {
        $outlet2 = Outlet::factory()->create(['tenant_id' => $this->tenant->id]);

        StockBatch::factory()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'inventory_item_id' => $this->item->id,
        ]);

        StockBatch::factory()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $outlet2->id,
            'inventory_item_id' => $this->item->id,
        ]);

        $batches = StockBatch::forOutlet($this->outlet->id)->get();

        $this->assertCount(1, $batches);
    }

    public function test_scope_for_item(): void
    {
        $item2 = InventoryItem::factory()->create([
            'tenant_id' => $this->tenant->id,
            'unit_id' => $this->unit->id,
        ]);

        StockBatch::factory()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'inventory_item_id' => $this->item->id,
        ]);

        StockBatch::factory()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'inventory_item_id' => $item2->id,
        ]);

        $batches = StockBatch::forItem($this->item->id)->get();

        $this->assertCount(1, $batches);
    }

    // ============================================================
    // STATIC HELPERS TESTS
    // ============================================================

    public function test_generate_batch_number(): void
    {
        $batchNumber = StockBatch::generateBatchNumber($this->outlet->id);

        $this->assertStringStartsWith('BTH-', $batchNumber);
        $this->assertStringContainsString(now()->format('Ymd'), $batchNumber);
    }

    public function test_get_available_batches_for_item(): void
    {
        // Create active batch with stock
        StockBatch::factory()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'inventory_item_id' => $this->item->id,
            'status' => StockBatch::STATUS_ACTIVE,
            'current_quantity' => 50,
            'expiry_date' => now()->addDays(30),
        ]);

        // Create depleted batch
        StockBatch::factory()->depleted()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'inventory_item_id' => $this->item->id,
        ]);

        $batches = StockBatch::getAvailableBatchesForItem($this->outlet->id, $this->item->id);

        $this->assertCount(1, $batches);
    }
}
