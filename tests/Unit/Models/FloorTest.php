<?php

namespace Tests\Unit\Models;

use App\Models\Floor;
use App\Models\Outlet;
use App\Models\Table;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FloorTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;

    protected Outlet $outlet;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tenant = Tenant::factory()->create();
        $this->outlet = Outlet::factory()->create(['tenant_id' => $this->tenant->id]);
    }

    // ==================== CREATION TESTS ====================

    public function test_can_create_floor(): void
    {
        $floor = Floor::factory()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'name' => 'Main Floor',
        ]);

        $this->assertDatabaseHas('floors', [
            'id' => $floor->id,
            'name' => 'Main Floor',
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
        ]);
    }

    public function test_floor_has_required_attributes(): void
    {
        $floor = Floor::factory()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
        ]);

        $this->assertNotNull($floor->id);
        $this->assertNotNull($floor->tenant_id);
        $this->assertNotNull($floor->outlet_id);
        $this->assertNotNull($floor->name);
    }

    public function test_floor_can_have_optional_attributes(): void
    {
        $floor = Floor::factory()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'description' => 'Ground level dining area',
            'sort_order' => 5,
        ]);

        $this->assertEquals('Ground level dining area', $floor->description);
        $this->assertEquals(5, $floor->sort_order);
    }

    // ==================== RELATIONSHIP TESTS ====================

    public function test_floor_belongs_to_tenant(): void
    {
        $floor = Floor::factory()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
        ]);

        $this->assertInstanceOf(Tenant::class, $floor->tenant);
        $this->assertEquals($this->tenant->id, $floor->tenant->id);
    }

    public function test_floor_belongs_to_outlet(): void
    {
        $floor = Floor::factory()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
        ]);

        $this->assertInstanceOf(Outlet::class, $floor->outlet);
        $this->assertEquals($this->outlet->id, $floor->outlet->id);
    }

    public function test_floor_has_many_tables(): void
    {
        $floor = Floor::factory()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
        ]);

        Table::factory()->count(3)->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'floor_id' => $floor->id,
        ]);

        $this->assertCount(3, $floor->tables);
    }

    public function test_floor_tables_ordered_by_number(): void
    {
        $floor = Floor::factory()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
        ]);

        Table::factory()->withNumber(3)->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'floor_id' => $floor->id,
        ]);
        Table::factory()->withNumber(1)->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'floor_id' => $floor->id,
        ]);
        Table::factory()->withNumber(2)->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'floor_id' => $floor->id,
        ]);

        $tables = $floor->tables;
        $this->assertEquals(1, $tables[0]->number);
        $this->assertEquals(2, $tables[1]->number);
        $this->assertEquals(3, $tables[2]->number);
    }

    public function test_floor_has_many_active_tables(): void
    {
        $floor = Floor::factory()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
        ]);

        Table::factory()->count(2)->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'floor_id' => $floor->id,
            'is_active' => true,
        ]);
        Table::factory()->inactive()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'floor_id' => $floor->id,
        ]);

        $this->assertCount(2, $floor->activeTables);
    }

    // ==================== ATTRIBUTE TESTS ====================

    public function test_available_tables_count_attribute(): void
    {
        $floor = Floor::factory()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
        ]);

        Table::factory()->count(3)->available()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'floor_id' => $floor->id,
        ]);
        Table::factory()->count(2)->occupied()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'floor_id' => $floor->id,
        ]);

        $this->assertEquals(3, $floor->availableTablesCount);
    }

    public function test_occupied_tables_count_attribute(): void
    {
        $floor = Floor::factory()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
        ]);

        Table::factory()->count(2)->available()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'floor_id' => $floor->id,
        ]);
        Table::factory()->count(4)->occupied()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'floor_id' => $floor->id,
        ]);

        $this->assertEquals(4, $floor->occupiedTablesCount);
    }

    public function test_table_counts_return_zero_when_no_tables(): void
    {
        $floor = Floor::factory()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
        ]);

        $this->assertEquals(0, $floor->availableTablesCount);
        $this->assertEquals(0, $floor->occupiedTablesCount);
    }

    // ==================== SCOPE TESTS ====================

    public function test_scope_active(): void
    {
        Floor::factory()->count(2)->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'is_active' => true,
        ]);
        Floor::factory()->inactive()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
        ]);

        $activeFloors = Floor::active()->get();

        $this->assertCount(2, $activeFloors);
    }

    public function test_scope_for_outlet(): void
    {
        $outlet2 = Outlet::factory()->create(['tenant_id' => $this->tenant->id]);

        Floor::factory()->count(3)->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
        ]);
        Floor::factory()->count(2)->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $outlet2->id,
        ]);

        $outletFloors = Floor::forOutlet($this->outlet->id)->get();

        $this->assertCount(3, $outletFloors);
    }

    public function test_scope_ordered(): void
    {
        Floor::factory()->withSortOrder(3)->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'name' => 'Floor C',
        ]);
        Floor::factory()->withSortOrder(1)->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'name' => 'Floor A',
        ]);
        Floor::factory()->withSortOrder(2)->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'name' => 'Floor B',
        ]);

        $orderedFloors = Floor::forOutlet($this->outlet->id)->ordered()->get();

        $this->assertEquals('Floor A', $orderedFloors[0]->name);
        $this->assertEquals('Floor B', $orderedFloors[1]->name);
        $this->assertEquals('Floor C', $orderedFloors[2]->name);
    }

    public function test_scope_ordered_by_name_when_same_sort_order(): void
    {
        Floor::factory()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'name' => 'Zebra Floor',
            'sort_order' => 1,
        ]);
        Floor::factory()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'name' => 'Alpha Floor',
            'sort_order' => 1,
        ]);

        $orderedFloors = Floor::forOutlet($this->outlet->id)->ordered()->get();

        $this->assertEquals('Alpha Floor', $orderedFloors[0]->name);
        $this->assertEquals('Zebra Floor', $orderedFloors[1]->name);
    }

    // ==================== CASTING TESTS ====================

    public function test_sort_order_is_cast_to_integer(): void
    {
        $floor = Floor::factory()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'sort_order' => 5,
        ]);

        $this->assertIsInt($floor->sort_order);
        $this->assertEquals(5, $floor->sort_order);
    }

    public function test_is_active_is_cast_to_boolean(): void
    {
        $floor = Floor::factory()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'is_active' => 1,
        ]);

        $this->assertIsBool($floor->is_active);
        $this->assertTrue($floor->is_active);
    }

    // ==================== FACTORY STATE TESTS ====================

    public function test_factory_inactive_state(): void
    {
        $floor = Floor::factory()->inactive()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
        ]);

        $this->assertFalse($floor->is_active);
    }

    public function test_factory_with_sort_order_state(): void
    {
        $floor = Floor::factory()->withSortOrder(10)->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
        ]);

        $this->assertEquals(10, $floor->sort_order);
    }

    public function test_factory_main_floor_state(): void
    {
        $floor = Floor::factory()->mainFloor()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
        ]);

        $this->assertEquals('Main Floor', $floor->name);
        $this->assertEquals(1, $floor->sort_order);
    }

    public function test_factory_vip_floor_state(): void
    {
        $floor = Floor::factory()->vipFloor()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
        ]);

        $this->assertEquals('VIP Floor', $floor->name);
        $this->assertEquals(2, $floor->sort_order);
    }

    public function test_factory_rooftop_state(): void
    {
        $floor = Floor::factory()->rooftop()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
        ]);

        $this->assertEquals('Rooftop', $floor->name);
        $this->assertEquals(3, $floor->sort_order);
    }

    // ==================== UUID TRAIT TESTS ====================

    public function test_floor_uses_uuid(): void
    {
        $floor = Floor::factory()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
        ]);

        $this->assertNotNull($floor->id);
        $this->assertIsString($floor->id);
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/',
            $floor->id
        );
    }

    // ==================== TENANT & OUTLET ISOLATION TESTS ====================

    public function test_floors_are_tenant_isolated(): void
    {
        $tenant2 = Tenant::factory()->create();
        $outlet2 = Outlet::factory()->create(['tenant_id' => $tenant2->id]);

        Floor::factory()->count(3)->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
        ]);
        Floor::factory()->count(2)->create([
            'tenant_id' => $tenant2->id,
            'outlet_id' => $outlet2->id,
        ]);

        $tenant1Floors = Floor::where('tenant_id', $this->tenant->id)->get();
        $tenant2Floors = Floor::where('tenant_id', $tenant2->id)->get();

        $this->assertCount(3, $tenant1Floors);
        $this->assertCount(2, $tenant2Floors);
    }

    public function test_floors_are_outlet_isolated(): void
    {
        $outlet2 = Outlet::factory()->create(['tenant_id' => $this->tenant->id]);

        Floor::factory()->count(4)->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
        ]);
        Floor::factory()->count(2)->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $outlet2->id,
        ]);

        $outlet1Floors = Floor::forOutlet($this->outlet->id)->get();
        $outlet2Floors = Floor::forOutlet($outlet2->id)->get();

        $this->assertCount(4, $outlet1Floors);
        $this->assertCount(2, $outlet2Floors);
    }

    // ==================== COMBINED SCOPE TESTS ====================

    public function test_combined_scopes(): void
    {
        $outlet2 = Outlet::factory()->create(['tenant_id' => $this->tenant->id]);

        Floor::factory()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'is_active' => true,
            'sort_order' => 2,
        ]);
        Floor::factory()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'is_active' => true,
            'sort_order' => 1,
        ]);
        Floor::factory()->inactive()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
        ]);
        Floor::factory()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $outlet2->id,
            'is_active' => true,
        ]);

        $floors = Floor::forOutlet($this->outlet->id)->active()->ordered()->get();

        $this->assertCount(2, $floors);
        $this->assertEquals(1, $floors[0]->sort_order);
        $this->assertEquals(2, $floors[1]->sort_order);
    }

    // ==================== EDGE CASE TESTS ====================

    public function test_floor_with_null_description(): void
    {
        $floor = Floor::factory()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'description' => null,
        ]);

        $this->assertNull($floor->description);
    }

    public function test_floor_with_zero_sort_order(): void
    {
        $floor = Floor::factory()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'sort_order' => 0,
        ]);

        $this->assertEquals(0, $floor->sort_order);
    }
}
