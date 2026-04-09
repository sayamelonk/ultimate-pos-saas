<?php

namespace Tests\Unit\Models;

use App\Models\Floor;
use App\Models\Outlet;
use App\Models\Table;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TableTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;

    protected Outlet $outlet;

    protected Floor $floor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tenant = Tenant::factory()->create();
        $this->outlet = Outlet::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->floor = Floor::factory()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
        ]);
    }

    // ==================== CREATION TESTS ====================

    public function test_can_create_table(): void
    {
        $table = Table::factory()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'floor_id' => $this->floor->id,
            'number' => 1,
            'name' => 'Meja 1',
        ]);

        $this->assertDatabaseHas('tables', [
            'id' => $table->id,
            'number' => 1,
            'name' => 'Meja 1',
        ]);
    }

    public function test_table_has_required_attributes(): void
    {
        $table = Table::factory()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'floor_id' => $this->floor->id,
        ]);

        $this->assertNotNull($table->id);
        $this->assertNotNull($table->tenant_id);
        $this->assertNotNull($table->outlet_id);
        $this->assertNotNull($table->floor_id);
        $this->assertNotNull($table->number);
    }

    public function test_table_can_have_all_attributes(): void
    {
        $table = Table::factory()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'floor_id' => $this->floor->id,
            'number' => 5,
            'name' => 'VIP Table',
            'capacity' => 8,
            'position_x' => 100,
            'position_y' => 200,
            'width' => 120,
            'height' => 80,
            'shape' => Table::SHAPE_RECTANGLE,
            'status' => Table::STATUS_AVAILABLE,
        ]);

        $this->assertEquals(5, $table->number);
        $this->assertEquals('VIP Table', $table->name);
        $this->assertEquals(8, $table->capacity);
        $this->assertEquals(100, $table->position_x);
        $this->assertEquals(200, $table->position_y);
        $this->assertEquals(120, $table->width);
        $this->assertEquals(80, $table->height);
        $this->assertEquals(Table::SHAPE_RECTANGLE, $table->shape);
    }

    // ==================== CONSTANT TESTS ====================

    public function test_status_constants(): void
    {
        $this->assertEquals('available', Table::STATUS_AVAILABLE);
        $this->assertEquals('occupied', Table::STATUS_OCCUPIED);
        $this->assertEquals('reserved', Table::STATUS_RESERVED);
        $this->assertEquals('dirty', Table::STATUS_DIRTY);
    }

    public function test_shape_constants(): void
    {
        $this->assertEquals('rectangle', Table::SHAPE_RECTANGLE);
        $this->assertEquals('circle', Table::SHAPE_CIRCLE);
        $this->assertEquals('square', Table::SHAPE_SQUARE);
    }

    // ==================== RELATIONSHIP TESTS ====================

    public function test_table_belongs_to_tenant(): void
    {
        $table = Table::factory()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'floor_id' => $this->floor->id,
        ]);

        $this->assertInstanceOf(Tenant::class, $table->tenant);
        $this->assertEquals($this->tenant->id, $table->tenant->id);
    }

    public function test_table_belongs_to_outlet(): void
    {
        $table = Table::factory()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'floor_id' => $this->floor->id,
        ]);

        $this->assertInstanceOf(Outlet::class, $table->outlet);
        $this->assertEquals($this->outlet->id, $table->outlet->id);
    }

    public function test_table_belongs_to_floor(): void
    {
        $table = Table::factory()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'floor_id' => $this->floor->id,
        ]);

        $this->assertInstanceOf(Floor::class, $table->floor);
        $this->assertEquals($this->floor->id, $table->floor->id);
    }

    public function test_table_has_many_sessions(): void
    {
        $table = Table::factory()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'floor_id' => $this->floor->id,
        ]);

        $this->assertInstanceOf(HasMany::class, $table->sessions());
    }

    public function test_table_has_one_current_session(): void
    {
        $table = Table::factory()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'floor_id' => $this->floor->id,
        ]);

        $this->assertInstanceOf(HasOne::class, $table->currentSession());
    }

    public function test_table_has_many_transactions(): void
    {
        $table = Table::factory()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'floor_id' => $this->floor->id,
        ]);

        $this->assertInstanceOf(HasMany::class, $table->transactions());
    }

    // ==================== DISPLAY NAME ATTRIBUTE TESTS ====================

    public function test_display_name_returns_name_when_set(): void
    {
        $table = Table::factory()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'floor_id' => $this->floor->id,
            'name' => 'VIP Corner',
            'number' => 5,
        ]);

        $this->assertEquals('VIP Corner', $table->displayName);
    }

    public function test_display_name_returns_default_when_name_null(): void
    {
        $table = Table::factory()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'floor_id' => $this->floor->id,
            'name' => null,
            'number' => 7,
        ]);

        $this->assertEquals('Meja 7', $table->displayName);
    }

    // ==================== STATUS CHECK METHODS TESTS ====================

    public function test_is_available_returns_true(): void
    {
        $table = Table::factory()->available()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'floor_id' => $this->floor->id,
        ]);

        $this->assertTrue($table->isAvailable());
        $this->assertFalse($table->isOccupied());
        $this->assertFalse($table->isReserved());
        $this->assertFalse($table->hasStatusDirty());
    }

    public function test_is_occupied_returns_true(): void
    {
        $table = Table::factory()->occupied()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'floor_id' => $this->floor->id,
        ]);

        $this->assertFalse($table->isAvailable());
        $this->assertTrue($table->isOccupied());
        $this->assertFalse($table->isReserved());
        $this->assertFalse($table->hasStatusDirty());
    }

    public function test_is_reserved_returns_true(): void
    {
        $table = Table::factory()->reserved()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'floor_id' => $this->floor->id,
        ]);

        $this->assertFalse($table->isAvailable());
        $this->assertFalse($table->isOccupied());
        $this->assertTrue($table->isReserved());
        $this->assertFalse($table->hasStatusDirty());
    }

    public function test_has_status_dirty_returns_true(): void
    {
        $table = Table::factory()->dirty()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'floor_id' => $this->floor->id,
        ]);

        $this->assertFalse($table->isAvailable());
        $this->assertFalse($table->isOccupied());
        $this->assertFalse($table->isReserved());
        $this->assertTrue($table->hasStatusDirty());
    }

    // ==================== MARK STATUS METHODS TESTS ====================

    public function test_mark_as_available(): void
    {
        $table = Table::factory()->occupied()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'floor_id' => $this->floor->id,
        ]);

        $table->markAsAvailable();
        $table->refresh();

        $this->assertEquals(Table::STATUS_AVAILABLE, $table->status);
        $this->assertTrue($table->isAvailable());
    }

    public function test_mark_as_occupied(): void
    {
        $table = Table::factory()->available()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'floor_id' => $this->floor->id,
        ]);

        $table->markAsOccupied();
        $table->refresh();

        $this->assertEquals(Table::STATUS_OCCUPIED, $table->status);
        $this->assertTrue($table->isOccupied());
    }

    public function test_mark_as_reserved(): void
    {
        $table = Table::factory()->available()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'floor_id' => $this->floor->id,
        ]);

        $table->markAsReserved();
        $table->refresh();

        $this->assertEquals(Table::STATUS_RESERVED, $table->status);
        $this->assertTrue($table->isReserved());
    }

    public function test_mark_as_dirty(): void
    {
        $table = Table::factory()->occupied()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'floor_id' => $this->floor->id,
        ]);

        $table->markAsDirty();
        $table->refresh();

        $this->assertEquals(Table::STATUS_DIRTY, $table->status);
        $this->assertTrue($table->hasStatusDirty());
    }

    // ==================== SCOPE TESTS ====================

    public function test_scope_active(): void
    {
        Table::factory()->count(2)->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'floor_id' => $this->floor->id,
            'is_active' => true,
        ]);
        Table::factory()->inactive()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'floor_id' => $this->floor->id,
        ]);

        $activeTables = Table::active()->get();

        $this->assertCount(2, $activeTables);
    }

    public function test_scope_available(): void
    {
        Table::factory()->count(3)->available()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'floor_id' => $this->floor->id,
        ]);
        Table::factory()->occupied()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'floor_id' => $this->floor->id,
        ]);

        $availableTables = Table::available()->get();

        $this->assertCount(3, $availableTables);
    }

    public function test_scope_occupied(): void
    {
        Table::factory()->count(2)->occupied()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'floor_id' => $this->floor->id,
        ]);
        Table::factory()->available()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'floor_id' => $this->floor->id,
        ]);

        $occupiedTables = Table::occupied()->get();

        $this->assertCount(2, $occupiedTables);
    }

    public function test_scope_for_outlet(): void
    {
        $outlet2 = Outlet::factory()->create(['tenant_id' => $this->tenant->id]);
        $floor2 = Floor::factory()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $outlet2->id,
        ]);

        Table::factory()->count(4)->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'floor_id' => $this->floor->id,
        ]);
        Table::factory()->count(2)->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $outlet2->id,
            'floor_id' => $floor2->id,
        ]);

        $outlet1Tables = Table::forOutlet($this->outlet->id)->get();

        $this->assertCount(4, $outlet1Tables);
    }

    public function test_scope_for_floor(): void
    {
        $floor2 = Floor::factory()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
        ]);

        Table::factory()->count(5)->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'floor_id' => $this->floor->id,
        ]);
        Table::factory()->count(3)->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'floor_id' => $floor2->id,
        ]);

        $floor1Tables = Table::forFloor($this->floor->id)->get();

        $this->assertCount(5, $floor1Tables);
    }

    // ==================== STATIC METHODS TESTS ====================

    public function test_get_statuses_returns_all_statuses(): void
    {
        $statuses = Table::getStatuses();

        $this->assertIsArray($statuses);
        $this->assertArrayHasKey(Table::STATUS_AVAILABLE, $statuses);
        $this->assertArrayHasKey(Table::STATUS_OCCUPIED, $statuses);
        $this->assertArrayHasKey(Table::STATUS_RESERVED, $statuses);
        $this->assertArrayHasKey(Table::STATUS_DIRTY, $statuses);
        $this->assertEquals('Available', $statuses[Table::STATUS_AVAILABLE]);
        $this->assertEquals('Occupied', $statuses[Table::STATUS_OCCUPIED]);
        $this->assertEquals('Reserved', $statuses[Table::STATUS_RESERVED]);
        $this->assertEquals('Dirty', $statuses[Table::STATUS_DIRTY]);
    }

    public function test_get_shapes_returns_all_shapes(): void
    {
        $shapes = Table::getShapes();

        $this->assertIsArray($shapes);
        $this->assertArrayHasKey(Table::SHAPE_RECTANGLE, $shapes);
        $this->assertArrayHasKey(Table::SHAPE_CIRCLE, $shapes);
        $this->assertArrayHasKey(Table::SHAPE_SQUARE, $shapes);
        $this->assertEquals('Rectangle', $shapes[Table::SHAPE_RECTANGLE]);
        $this->assertEquals('Circle', $shapes[Table::SHAPE_CIRCLE]);
        $this->assertEquals('Square', $shapes[Table::SHAPE_SQUARE]);
    }

    // ==================== CASTING TESTS ====================

    public function test_capacity_is_cast_to_integer(): void
    {
        $table = Table::factory()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'floor_id' => $this->floor->id,
            'capacity' => 6,
        ]);

        $this->assertIsInt($table->capacity);
        $this->assertEquals(6, $table->capacity);
    }

    public function test_position_x_is_cast_to_integer(): void
    {
        $table = Table::factory()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'floor_id' => $this->floor->id,
            'position_x' => 150,
        ]);

        $this->assertIsInt($table->position_x);
    }

    public function test_position_y_is_cast_to_integer(): void
    {
        $table = Table::factory()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'floor_id' => $this->floor->id,
            'position_y' => 250,
        ]);

        $this->assertIsInt($table->position_y);
    }

    public function test_width_is_cast_to_integer(): void
    {
        $table = Table::factory()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'floor_id' => $this->floor->id,
            'width' => 100,
        ]);

        $this->assertIsInt($table->width);
    }

    public function test_height_is_cast_to_integer(): void
    {
        $table = Table::factory()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'floor_id' => $this->floor->id,
            'height' => 80,
        ]);

        $this->assertIsInt($table->height);
    }

    public function test_is_active_is_cast_to_boolean(): void
    {
        $table = Table::factory()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'floor_id' => $this->floor->id,
            'is_active' => 1,
        ]);

        $this->assertIsBool($table->is_active);
        $this->assertTrue($table->is_active);
    }

    // ==================== FACTORY STATE TESTS ====================

    public function test_factory_occupied_state(): void
    {
        $table = Table::factory()->occupied()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'floor_id' => $this->floor->id,
        ]);

        $this->assertEquals(Table::STATUS_OCCUPIED, $table->status);
    }

    public function test_factory_reserved_state(): void
    {
        $table = Table::factory()->reserved()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'floor_id' => $this->floor->id,
        ]);

        $this->assertEquals(Table::STATUS_RESERVED, $table->status);
    }

    public function test_factory_dirty_state(): void
    {
        $table = Table::factory()->dirty()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'floor_id' => $this->floor->id,
        ]);

        $this->assertEquals(Table::STATUS_DIRTY, $table->status);
    }

    public function test_factory_inactive_state(): void
    {
        $table = Table::factory()->inactive()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'floor_id' => $this->floor->id,
        ]);

        $this->assertFalse($table->is_active);
    }

    public function test_factory_available_state(): void
    {
        $table = Table::factory()->available()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'floor_id' => $this->floor->id,
        ]);

        $this->assertEquals(Table::STATUS_AVAILABLE, $table->status);
    }

    public function test_factory_rectangle_state(): void
    {
        $table = Table::factory()->rectangle()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'floor_id' => $this->floor->id,
        ]);

        $this->assertEquals(Table::SHAPE_RECTANGLE, $table->shape);
        $this->assertEquals(120, $table->width);
        $this->assertEquals(80, $table->height);
    }

    public function test_factory_circle_state(): void
    {
        $table = Table::factory()->circle()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'floor_id' => $this->floor->id,
        ]);

        $this->assertEquals(Table::SHAPE_CIRCLE, $table->shape);
        $this->assertEquals(80, $table->width);
        $this->assertEquals(80, $table->height);
    }

    public function test_factory_square_state(): void
    {
        $table = Table::factory()->square()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'floor_id' => $this->floor->id,
        ]);

        $this->assertEquals(Table::SHAPE_SQUARE, $table->shape);
        $this->assertEquals(80, $table->width);
        $this->assertEquals(80, $table->height);
    }

    public function test_factory_with_capacity_state(): void
    {
        $table = Table::factory()->withCapacity(12)->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'floor_id' => $this->floor->id,
        ]);

        $this->assertEquals(12, $table->capacity);
    }

    public function test_factory_with_number_state(): void
    {
        $table = Table::factory()->withNumber(25)->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'floor_id' => $this->floor->id,
        ]);

        $this->assertEquals(25, $table->number);
        $this->assertEquals('Meja 25', $table->name);
    }

    public function test_factory_with_position_state(): void
    {
        $table = Table::factory()->withPosition(300, 400)->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'floor_id' => $this->floor->id,
        ]);

        $this->assertEquals(300, $table->position_x);
        $this->assertEquals(400, $table->position_y);
    }

    // ==================== UUID TRAIT TESTS ====================

    public function test_table_uses_uuid(): void
    {
        $table = Table::factory()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'floor_id' => $this->floor->id,
        ]);

        $this->assertNotNull($table->id);
        $this->assertIsString($table->id);
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/',
            $table->id
        );
    }

    // ==================== TENANT & OUTLET ISOLATION TESTS ====================

    public function test_tables_are_tenant_isolated(): void
    {
        $tenant2 = Tenant::factory()->create();
        $outlet2 = Outlet::factory()->create(['tenant_id' => $tenant2->id]);
        $floor2 = Floor::factory()->create([
            'tenant_id' => $tenant2->id,
            'outlet_id' => $outlet2->id,
        ]);

        Table::factory()->count(3)->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'floor_id' => $this->floor->id,
        ]);
        Table::factory()->count(2)->create([
            'tenant_id' => $tenant2->id,
            'outlet_id' => $outlet2->id,
            'floor_id' => $floor2->id,
        ]);

        $tenant1Tables = Table::where('tenant_id', $this->tenant->id)->get();
        $tenant2Tables = Table::where('tenant_id', $tenant2->id)->get();

        $this->assertCount(3, $tenant1Tables);
        $this->assertCount(2, $tenant2Tables);
    }

    // ==================== COMBINED STATE TESTS ====================

    public function test_combined_factory_states(): void
    {
        $table = Table::factory()
            ->rectangle()
            ->withCapacity(6)
            ->withNumber(10)
            ->withPosition(200, 300)
            ->available()
            ->create([
                'tenant_id' => $this->tenant->id,
                'outlet_id' => $this->outlet->id,
                'floor_id' => $this->floor->id,
            ]);

        $this->assertEquals(Table::SHAPE_RECTANGLE, $table->shape);
        $this->assertEquals(6, $table->capacity);
        $this->assertEquals(10, $table->number);
        $this->assertEquals(200, $table->position_x);
        $this->assertEquals(300, $table->position_y);
        $this->assertEquals(Table::STATUS_AVAILABLE, $table->status);
    }

    // ==================== EDGE CASE TESTS ====================

    public function test_table_with_null_name(): void
    {
        $table = Table::factory()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'floor_id' => $this->floor->id,
            'name' => null,
            'number' => 99,
        ]);

        $this->assertNull($table->name);
        $this->assertEquals('Meja 99', $table->displayName);
    }

    public function test_table_status_transitions(): void
    {
        $table = Table::factory()->available()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'floor_id' => $this->floor->id,
        ]);

        // Available -> Reserved
        $table->markAsReserved();
        $this->assertTrue($table->isReserved());

        // Reserved -> Occupied
        $table->markAsOccupied();
        $this->assertTrue($table->isOccupied());

        // Occupied -> Dirty
        $table->markAsDirty();
        $this->assertTrue($table->hasStatusDirty());

        // Dirty -> Available
        $table->markAsAvailable();
        $this->assertTrue($table->isAvailable());
    }

    public function test_table_with_zero_position(): void
    {
        $table = Table::factory()->withPosition(0, 0)->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'floor_id' => $this->floor->id,
        ]);

        $this->assertEquals(0, $table->position_x);
        $this->assertEquals(0, $table->position_y);
    }
}
