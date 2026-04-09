<?php

namespace Tests\Unit\Services;

use App\Models\InventoryItem;
use App\Models\InventoryStock;
use App\Models\Outlet;
use App\Models\StockMovement;
use App\Models\Tenant;
use App\Models\Unit;
use App\Models\User;
use App\Services\Inventory\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockServiceTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;

    protected Outlet $outlet;

    protected Unit $unit;

    protected User $user;

    protected InventoryItem $item;

    protected StockService $stockService;

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

        $this->stockService = new StockService;
    }

    // ============================================================
    // RECEIVE STOCK TESTS
    // ============================================================

    public function test_receive_stock_creates_new_stock_record(): void
    {
        $movement = $this->stockService->receiveStock(
            $this->outlet->id,
            $this->item->id,
            100,
            10000,
            $this->user->id,
            'Initial stock'
        );

        $this->assertInstanceOf(StockMovement::class, $movement);
        $this->assertEquals(StockMovement::TYPE_IN, $movement->type);
        $this->assertEquals(100, $movement->quantity);
        $this->assertEquals(0, $movement->stock_before);
        $this->assertEquals(100, $movement->stock_after);

        $stock = InventoryStock::where('outlet_id', $this->outlet->id)
            ->where('inventory_item_id', $this->item->id)
            ->first();

        $this->assertEquals(100, $stock->quantity);
        $this->assertEquals(10000, $stock->avg_cost);
        $this->assertEquals(10000, $stock->last_cost);
    }

    public function test_receive_stock_updates_existing_stock(): void
    {
        // Create initial stock
        InventoryStock::factory()->create([
            'outlet_id' => $this->outlet->id,
            'inventory_item_id' => $this->item->id,
            'quantity' => 50,
            'avg_cost' => 10000,
            'last_cost' => 10000,
        ]);

        $movement = $this->stockService->receiveStock(
            $this->outlet->id,
            $this->item->id,
            50,
            12000, // Different cost
            $this->user->id,
            'Additional stock'
        );

        $this->assertEquals(50, $movement->stock_before);
        $this->assertEquals(100, $movement->stock_after);

        $stock = InventoryStock::where('outlet_id', $this->outlet->id)
            ->where('inventory_item_id', $this->item->id)
            ->first();

        $this->assertEquals(100, $stock->quantity);
        $this->assertEquals(12000, $stock->last_cost);
        // Average cost: (50*10000 + 50*12000) / 100 = 11000
        $this->assertEquals(11000, $stock->avg_cost);
    }

    public function test_receive_stock_calculates_weighted_average_cost(): void
    {
        InventoryStock::factory()->create([
            'outlet_id' => $this->outlet->id,
            'inventory_item_id' => $this->item->id,
            'quantity' => 100,
            'avg_cost' => 10000,
        ]);

        $this->stockService->receiveStock(
            $this->outlet->id,
            $this->item->id,
            50,
            16000,
            $this->user->id
        );

        $stock = InventoryStock::where('outlet_id', $this->outlet->id)
            ->where('inventory_item_id', $this->item->id)
            ->first();

        // (100*10000 + 50*16000) / 150 = 1000000 + 800000 / 150 = 12000
        $this->assertEquals(12000, $stock->avg_cost);
    }

    public function test_receive_stock_with_reference(): void
    {
        $movement = $this->stockService->receiveStock(
            $this->outlet->id,
            $this->item->id,
            100,
            10000,
            $this->user->id,
            'From PO',
            'App\\Models\\PurchaseOrder',
            'po-123'
        );

        $this->assertEquals('App\\Models\\PurchaseOrder', $movement->reference_type);
        $this->assertEquals('po-123', $movement->reference_id);
    }

    // ============================================================
    // ISSUE STOCK TESTS
    // ============================================================

    public function test_issue_stock_reduces_quantity(): void
    {
        InventoryStock::factory()->create([
            'outlet_id' => $this->outlet->id,
            'inventory_item_id' => $this->item->id,
            'quantity' => 100,
            'reserved_qty' => 0,
            'avg_cost' => 10000,
        ]);

        $movement = $this->stockService->issueStock(
            $this->outlet->id,
            $this->item->id,
            30,
            $this->user->id,
            'Sold'
        );

        $this->assertEquals(StockMovement::TYPE_OUT, $movement->type);
        $this->assertEquals(-30, $movement->quantity);
        $this->assertEquals(100, $movement->stock_before);
        $this->assertEquals(70, $movement->stock_after);

        $stock = InventoryStock::where('outlet_id', $this->outlet->id)
            ->where('inventory_item_id', $this->item->id)
            ->first();

        $this->assertEquals(70, $stock->quantity);
    }

    public function test_issue_stock_throws_exception_when_no_stock_record(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('No stock record found');

        $this->stockService->issueStock(
            $this->outlet->id,
            $this->item->id,
            10,
            $this->user->id
        );
    }

    public function test_issue_stock_throws_exception_when_insufficient_stock(): void
    {
        InventoryStock::factory()->create([
            'outlet_id' => $this->outlet->id,
            'inventory_item_id' => $this->item->id,
            'quantity' => 50,
            'reserved_qty' => 0,
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Insufficient stock');

        $this->stockService->issueStock(
            $this->outlet->id,
            $this->item->id,
            100,
            $this->user->id
        );
    }

    public function test_issue_stock_considers_reserved_quantity(): void
    {
        InventoryStock::factory()->create([
            'outlet_id' => $this->outlet->id,
            'inventory_item_id' => $this->item->id,
            'quantity' => 100,
            'reserved_qty' => 60, // 40 available
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Insufficient stock');

        $this->stockService->issueStock(
            $this->outlet->id,
            $this->item->id,
            50, // More than 40 available
            $this->user->id
        );
    }

    public function test_issue_stock_succeeds_with_available_quantity(): void
    {
        InventoryStock::factory()->create([
            'outlet_id' => $this->outlet->id,
            'inventory_item_id' => $this->item->id,
            'quantity' => 100,
            'reserved_qty' => 60,
            'avg_cost' => 10000,
        ]);

        $movement = $this->stockService->issueStock(
            $this->outlet->id,
            $this->item->id,
            30, // Less than 40 available
            $this->user->id
        );

        $this->assertEquals(-30, $movement->quantity);
        $this->assertEquals(70, $movement->stock_after);
    }

    // ============================================================
    // ADJUST STOCK TESTS
    // ============================================================

    public function test_adjust_stock_increases_quantity(): void
    {
        InventoryStock::factory()->create([
            'outlet_id' => $this->outlet->id,
            'inventory_item_id' => $this->item->id,
            'quantity' => 100,
            'avg_cost' => 10000,
        ]);

        $movement = $this->stockService->adjustStock(
            $this->outlet->id,
            $this->item->id,
            20,
            $this->user->id,
            'Found extra stock'
        );

        $this->assertEquals(StockMovement::TYPE_ADJUSTMENT, $movement->type);
        $this->assertEquals(20, $movement->quantity);
        $this->assertEquals(100, $movement->stock_before);
        $this->assertEquals(120, $movement->stock_after);
    }

    public function test_adjust_stock_decreases_quantity(): void
    {
        InventoryStock::factory()->create([
            'outlet_id' => $this->outlet->id,
            'inventory_item_id' => $this->item->id,
            'quantity' => 100,
            'avg_cost' => 10000,
        ]);

        $movement = $this->stockService->adjustStock(
            $this->outlet->id,
            $this->item->id,
            -30,
            $this->user->id,
            'Damaged items'
        );

        $this->assertEquals(-30, $movement->quantity);
        $this->assertEquals(70, $movement->stock_after);
    }

    public function test_adjust_stock_creates_record_if_not_exists(): void
    {
        $movement = $this->stockService->adjustStock(
            $this->outlet->id,
            $this->item->id,
            50,
            $this->user->id,
            'Opening balance'
        );

        $this->assertEquals(0, $movement->stock_before);
        $this->assertEquals(50, $movement->stock_after);

        $stock = InventoryStock::where('outlet_id', $this->outlet->id)
            ->where('inventory_item_id', $this->item->id)
            ->first();

        $this->assertNotNull($stock);
        $this->assertEquals(50, $stock->quantity);
    }

    public function test_adjust_stock_never_goes_negative(): void
    {
        InventoryStock::factory()->create([
            'outlet_id' => $this->outlet->id,
            'inventory_item_id' => $this->item->id,
            'quantity' => 30,
        ]);

        $movement = $this->stockService->adjustStock(
            $this->outlet->id,
            $this->item->id,
            -50, // More than current quantity
            $this->user->id
        );

        $this->assertEquals(0, $movement->stock_after); // Should be 0, not negative
    }

    // ============================================================
    // RESERVE STOCK TESTS
    // ============================================================

    public function test_reserve_stock_increases_reserved_qty(): void
    {
        InventoryStock::factory()->create([
            'outlet_id' => $this->outlet->id,
            'inventory_item_id' => $this->item->id,
            'quantity' => 100,
            'reserved_qty' => 0,
        ]);

        $this->stockService->reserveStock(
            $this->outlet->id,
            $this->item->id,
            30
        );

        $stock = InventoryStock::where('outlet_id', $this->outlet->id)
            ->where('inventory_item_id', $this->item->id)
            ->first();

        $this->assertEquals(30, $stock->reserved_qty);
    }

    public function test_reserve_stock_throws_exception_when_no_record(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('No stock record found');

        $this->stockService->reserveStock(
            $this->outlet->id,
            $this->item->id,
            10
        );
    }

    public function test_reserve_stock_throws_exception_when_insufficient(): void
    {
        InventoryStock::factory()->create([
            'outlet_id' => $this->outlet->id,
            'inventory_item_id' => $this->item->id,
            'quantity' => 50,
            'reserved_qty' => 30, // 20 available
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Insufficient stock to reserve');

        $this->stockService->reserveStock(
            $this->outlet->id,
            $this->item->id,
            30 // More than 20 available
        );
    }

    // ============================================================
    // RELEASE RESERVATION TESTS
    // ============================================================

    public function test_release_reservation_decreases_reserved_qty(): void
    {
        InventoryStock::factory()->create([
            'outlet_id' => $this->outlet->id,
            'inventory_item_id' => $this->item->id,
            'quantity' => 100,
            'reserved_qty' => 50,
        ]);

        $this->stockService->releaseReservation(
            $this->outlet->id,
            $this->item->id,
            30
        );

        $stock = InventoryStock::where('outlet_id', $this->outlet->id)
            ->where('inventory_item_id', $this->item->id)
            ->first();

        $this->assertEquals(20, $stock->reserved_qty);
    }

    public function test_release_reservation_caps_at_zero(): void
    {
        InventoryStock::factory()->create([
            'outlet_id' => $this->outlet->id,
            'inventory_item_id' => $this->item->id,
            'quantity' => 100,
            'reserved_qty' => 20,
        ]);

        $this->stockService->releaseReservation(
            $this->outlet->id,
            $this->item->id,
            50 // More than reserved
        );

        $stock = InventoryStock::where('outlet_id', $this->outlet->id)
            ->where('inventory_item_id', $this->item->id)
            ->first();

        $this->assertEquals(0, $stock->reserved_qty);
    }

    public function test_release_reservation_does_nothing_when_no_record(): void
    {
        // Should not throw exception
        $this->stockService->releaseReservation(
            $this->outlet->id,
            $this->item->id,
            10
        );

        $this->assertTrue(true); // Just ensure no exception was thrown
    }

    // ============================================================
    // GET AVAILABLE STOCK TESTS
    // ============================================================

    public function test_get_available_stock(): void
    {
        InventoryStock::factory()->create([
            'outlet_id' => $this->outlet->id,
            'inventory_item_id' => $this->item->id,
            'quantity' => 100,
            'reserved_qty' => 30,
        ]);

        $available = $this->stockService->getAvailableStock(
            $this->outlet->id,
            $this->item->id
        );

        $this->assertEquals(70, $available);
    }

    public function test_get_available_stock_returns_zero_when_no_record(): void
    {
        $available = $this->stockService->getAvailableStock(
            $this->outlet->id,
            $this->item->id
        );

        $this->assertEquals(0, $available);
    }

    // ============================================================
    // GET TOTAL STOCK TESTS
    // ============================================================

    public function test_get_total_stock_across_outlets(): void
    {
        $outlet2 = Outlet::factory()->create(['tenant_id' => $this->tenant->id]);

        InventoryStock::factory()->create([
            'outlet_id' => $this->outlet->id,
            'inventory_item_id' => $this->item->id,
            'quantity' => 100,
        ]);

        InventoryStock::factory()->create([
            'outlet_id' => $outlet2->id,
            'inventory_item_id' => $this->item->id,
            'quantity' => 50,
        ]);

        $total = $this->stockService->getTotalStock($this->item->id);

        $this->assertEquals(150, $total);
    }

    public function test_get_total_stock_filtered_by_outlets(): void
    {
        $outlet2 = Outlet::factory()->create(['tenant_id' => $this->tenant->id]);
        $outlet3 = Outlet::factory()->create(['tenant_id' => $this->tenant->id]);

        InventoryStock::factory()->create([
            'outlet_id' => $this->outlet->id,
            'inventory_item_id' => $this->item->id,
            'quantity' => 100,
        ]);

        InventoryStock::factory()->create([
            'outlet_id' => $outlet2->id,
            'inventory_item_id' => $this->item->id,
            'quantity' => 50,
        ]);

        InventoryStock::factory()->create([
            'outlet_id' => $outlet3->id,
            'inventory_item_id' => $this->item->id,
            'quantity' => 75,
        ]);

        $total = $this->stockService->getTotalStock(
            $this->item->id,
            [$this->outlet->id, $outlet2->id]
        );

        $this->assertEquals(150, $total);
    }

    // ============================================================
    // RECORD WASTE TESTS
    // ============================================================

    public function test_record_waste_reduces_stock(): void
    {
        InventoryStock::factory()->create([
            'outlet_id' => $this->outlet->id,
            'inventory_item_id' => $this->item->id,
            'quantity' => 100,
            'avg_cost' => 10000,
        ]);

        $movement = $this->stockService->recordWaste(
            $this->outlet->id,
            $this->item->id,
            20,
            $this->user->id,
            'Expired'
        );

        $this->assertEquals(StockMovement::TYPE_WASTE, $movement->type);
        $this->assertEquals(-20, $movement->quantity);
        $this->assertEquals(80, $movement->stock_after);

        $stock = InventoryStock::where('outlet_id', $this->outlet->id)
            ->where('inventory_item_id', $this->item->id)
            ->first();

        $this->assertEquals(80, $stock->quantity);
    }

    public function test_record_waste_throws_exception_when_no_record(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('No stock record found');

        $this->stockService->recordWaste(
            $this->outlet->id,
            $this->item->id,
            10,
            $this->user->id,
            'Expired'
        );
    }

    public function test_record_waste_never_goes_negative(): void
    {
        InventoryStock::factory()->create([
            'outlet_id' => $this->outlet->id,
            'inventory_item_id' => $this->item->id,
            'quantity' => 30,
            'avg_cost' => 10000,
        ]);

        $movement = $this->stockService->recordWaste(
            $this->outlet->id,
            $this->item->id,
            50, // More than current
            $this->user->id
        );

        $this->assertEquals(0, $movement->stock_after);

        $stock = InventoryStock::where('outlet_id', $this->outlet->id)
            ->where('inventory_item_id', $this->item->id)
            ->first();

        $this->assertEquals(0, $stock->quantity);
    }
}
