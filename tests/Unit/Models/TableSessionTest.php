<?php

namespace Tests\Unit\Models;

use App\Models\Floor;
use App\Models\Outlet;
use App\Models\Table;
use App\Models\TableSession;
use App\Models\Tenant;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class TableSessionTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;

    protected Outlet $outlet;

    protected Floor $floor;

    protected Table $table;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tenant = Tenant::factory()->create();
        $this->outlet = Outlet::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->floor = Floor::factory()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
        ]);
        $this->table = Table::factory()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'floor_id' => $this->floor->id,
        ]);
        $this->user = User::factory()->create(['tenant_id' => $this->tenant->id]);
    }

    // ==================== CREATION TESTS ====================

    public function test_can_create_table_session(): void
    {
        $session = TableSession::factory()->create([
            'tenant_id' => $this->tenant->id,
            'table_id' => $this->table->id,
            'opened_by' => $this->user->id,
        ]);

        $this->assertDatabaseHas('table_sessions', [
            'id' => $session->id,
            'tenant_id' => $this->tenant->id,
            'table_id' => $this->table->id,
            'opened_by' => $this->user->id,
        ]);
    }

    public function test_table_session_has_required_attributes(): void
    {
        $session = TableSession::factory()->create([
            'tenant_id' => $this->tenant->id,
            'table_id' => $this->table->id,
            'opened_by' => $this->user->id,
        ]);

        $this->assertNotNull($session->id);
        $this->assertNotNull($session->tenant_id);
        $this->assertNotNull($session->table_id);
        $this->assertNotNull($session->opened_by);
        $this->assertNotNull($session->opened_at);
        $this->assertNotNull($session->status);
    }

    public function test_table_session_can_have_optional_attributes(): void
    {
        $session = TableSession::factory()->create([
            'tenant_id' => $this->tenant->id,
            'table_id' => $this->table->id,
            'opened_by' => $this->user->id,
            'guest_count' => 5,
            'notes' => 'Birthday celebration',
        ]);

        $this->assertEquals(5, $session->guest_count);
        $this->assertEquals('Birthday celebration', $session->notes);
    }

    // ==================== CONSTANT TESTS ====================

    public function test_status_constants(): void
    {
        $this->assertEquals('active', TableSession::STATUS_ACTIVE);
        $this->assertEquals('closed', TableSession::STATUS_CLOSED);
    }

    // ==================== RELATIONSHIP TESTS ====================

    public function test_table_session_belongs_to_tenant(): void
    {
        $session = TableSession::factory()->create([
            'tenant_id' => $this->tenant->id,
            'table_id' => $this->table->id,
            'opened_by' => $this->user->id,
        ]);

        $this->assertInstanceOf(Tenant::class, $session->tenant);
        $this->assertEquals($this->tenant->id, $session->tenant->id);
    }

    public function test_table_session_belongs_to_table(): void
    {
        $session = TableSession::factory()->create([
            'tenant_id' => $this->tenant->id,
            'table_id' => $this->table->id,
            'opened_by' => $this->user->id,
        ]);

        $this->assertInstanceOf(Table::class, $session->table);
        $this->assertEquals($this->table->id, $session->table->id);
    }

    public function test_table_session_belongs_to_opened_by_user(): void
    {
        $session = TableSession::factory()->create([
            'tenant_id' => $this->tenant->id,
            'table_id' => $this->table->id,
            'opened_by' => $this->user->id,
        ]);

        $this->assertInstanceOf(User::class, $session->openedByUser);
        $this->assertEquals($this->user->id, $session->openedByUser->id);
    }

    public function test_table_session_belongs_to_closed_by_user(): void
    {
        $closedBy = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $session = TableSession::factory()->closed()->create([
            'tenant_id' => $this->tenant->id,
            'table_id' => $this->table->id,
            'opened_by' => $this->user->id,
            'closed_by' => $closedBy->id,
        ]);

        $this->assertInstanceOf(User::class, $session->closedByUser);
        $this->assertEquals($closedBy->id, $session->closedByUser->id);
    }

    public function test_table_session_can_have_null_closed_by_user(): void
    {
        $session = TableSession::factory()->active()->create([
            'tenant_id' => $this->tenant->id,
            'table_id' => $this->table->id,
            'opened_by' => $this->user->id,
        ]);

        $this->assertNull($session->closedByUser);
    }

    public function test_table_session_has_many_transactions(): void
    {
        $session = TableSession::factory()->create([
            'tenant_id' => $this->tenant->id,
            'table_id' => $this->table->id,
            'opened_by' => $this->user->id,
        ]);

        $this->assertInstanceOf(HasMany::class, $session->transactions());
    }

    // ==================== STATUS CHECK METHODS TESTS ====================

    public function test_is_active_returns_true(): void
    {
        $session = TableSession::factory()->active()->create([
            'tenant_id' => $this->tenant->id,
            'table_id' => $this->table->id,
            'opened_by' => $this->user->id,
        ]);

        $this->assertTrue($session->isActive());
        $this->assertFalse($session->isClosed());
    }

    public function test_is_closed_returns_true(): void
    {
        $session = TableSession::factory()->closed()->create([
            'tenant_id' => $this->tenant->id,
            'table_id' => $this->table->id,
            'opened_by' => $this->user->id,
        ]);

        $this->assertFalse($session->isActive());
        $this->assertTrue($session->isClosed());
    }

    // ==================== DURATION ATTRIBUTE TESTS ====================

    public function test_duration_minutes_for_active_session(): void
    {
        $session = TableSession::factory()->openedMinutesAgo(30)->create([
            'tenant_id' => $this->tenant->id,
            'table_id' => $this->table->id,
            'opened_by' => $this->user->id,
        ]);

        $this->assertEquals(30, $session->durationMinutes);
    }

    public function test_duration_minutes_for_closed_session(): void
    {
        $session = TableSession::factory()->closedAfterMinutes(45)->create([
            'tenant_id' => $this->tenant->id,
            'table_id' => $this->table->id,
            'opened_by' => $this->user->id,
        ]);

        $this->assertEquals(45, $session->durationMinutes);
    }

    public function test_duration_formatted_minutes_only(): void
    {
        $session = TableSession::factory()->openedMinutesAgo(45)->create([
            'tenant_id' => $this->tenant->id,
            'table_id' => $this->table->id,
            'opened_by' => $this->user->id,
        ]);

        $this->assertEquals('45 menit', $session->durationFormatted);
    }

    public function test_duration_formatted_hours_and_minutes(): void
    {
        $session = TableSession::factory()->openedMinutesAgo(90)->create([
            'tenant_id' => $this->tenant->id,
            'table_id' => $this->table->id,
            'opened_by' => $this->user->id,
        ]);

        $this->assertEquals('1 jam 30 menit', $session->durationFormatted);
    }

    public function test_duration_formatted_multiple_hours(): void
    {
        $session = TableSession::factory()->openedHoursAgo(3)->create([
            'tenant_id' => $this->tenant->id,
            'table_id' => $this->table->id,
            'opened_by' => $this->user->id,
        ]);

        $this->assertEquals('3 jam 0 menit', $session->durationFormatted);
    }

    // ==================== TOTAL AMOUNT ATTRIBUTE TESTS ====================

    public function test_total_amount_with_no_transactions(): void
    {
        $session = TableSession::factory()->create([
            'tenant_id' => $this->tenant->id,
            'table_id' => $this->table->id,
            'opened_by' => $this->user->id,
        ]);

        $this->assertEquals(0, $session->totalAmount);
    }

    public function test_total_amount_with_transactions(): void
    {
        $session = TableSession::factory()->create([
            'tenant_id' => $this->tenant->id,
            'table_id' => $this->table->id,
            'opened_by' => $this->user->id,
        ]);

        Transaction::factory()->create([
            'tenant_id' => $this->tenant->id,
            'table_session_id' => $session->id,
            'grand_total' => 50000,
        ]);
        Transaction::factory()->create([
            'tenant_id' => $this->tenant->id,
            'table_session_id' => $session->id,
            'grand_total' => 75000,
        ]);

        $this->assertEquals(125000, $session->totalAmount);
    }

    // ==================== CLOSE METHOD TESTS ====================

    public function test_close_updates_status(): void
    {
        $session = TableSession::factory()->active()->create([
            'tenant_id' => $this->tenant->id,
            'table_id' => $this->table->id,
            'opened_by' => $this->user->id,
        ]);

        $session->close($this->user->id);
        $session->refresh();

        $this->assertEquals(TableSession::STATUS_CLOSED, $session->status);
        $this->assertTrue($session->isClosed());
    }

    public function test_close_sets_closed_at(): void
    {
        $session = TableSession::factory()->active()->create([
            'tenant_id' => $this->tenant->id,
            'table_id' => $this->table->id,
            'opened_by' => $this->user->id,
        ]);

        $session->close($this->user->id);
        $session->refresh();

        $this->assertNotNull($session->closed_at);
    }

    public function test_close_sets_closed_by(): void
    {
        $session = TableSession::factory()->active()->create([
            'tenant_id' => $this->tenant->id,
            'table_id' => $this->table->id,
            'opened_by' => $this->user->id,
        ]);

        $session->close($this->user->id);
        $session->refresh();

        $this->assertEquals($this->user->id, $session->closed_by);
    }

    public function test_close_marks_table_as_dirty(): void
    {
        $table = Table::factory()->occupied()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'floor_id' => $this->floor->id,
        ]);

        $session = TableSession::factory()->active()->create([
            'tenant_id' => $this->tenant->id,
            'table_id' => $table->id,
            'opened_by' => $this->user->id,
        ]);

        $session->close($this->user->id);
        $table->refresh();

        $this->assertTrue($table->hasStatusDirty());
    }

    public function test_close_without_closed_by(): void
    {
        $session = TableSession::factory()->active()->create([
            'tenant_id' => $this->tenant->id,
            'table_id' => $this->table->id,
            'opened_by' => $this->user->id,
        ]);

        $session->load('table');
        $session->close();
        $session->refresh();

        $this->assertTrue($session->isClosed());
        $this->assertNull($session->closed_by);
    }

    // ==================== OPEN TABLE STATIC METHOD TESTS ====================

    public function test_open_table_creates_session(): void
    {
        $table = Table::factory()->available()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'floor_id' => $this->floor->id,
        ]);

        $session = TableSession::openTable($table, 4, $this->user->id);

        $this->assertInstanceOf(TableSession::class, $session);
        $this->assertEquals($table->id, $session->table_id);
        $this->assertEquals($this->tenant->id, $session->tenant_id);
        $this->assertEquals(4, $session->guest_count);
        $this->assertEquals($this->user->id, $session->opened_by);
        $this->assertTrue($session->isActive());
    }

    public function test_open_table_marks_table_as_occupied(): void
    {
        $table = Table::factory()->available()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'floor_id' => $this->floor->id,
        ]);

        TableSession::openTable($table, 2, $this->user->id);
        $table->refresh();

        $this->assertTrue($table->isOccupied());
    }

    public function test_open_table_sets_opened_at(): void
    {
        $table = Table::factory()->available()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'floor_id' => $this->floor->id,
        ]);

        $session = TableSession::openTable($table, 3);

        $this->assertNotNull($session->opened_at);
    }

    public function test_open_table_without_opened_by(): void
    {
        $table = Table::factory()->available()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'floor_id' => $this->floor->id,
        ]);

        $session = TableSession::openTable($table, 2);

        $this->assertNull($session->opened_by);
    }

    public function test_open_table_default_guest_count(): void
    {
        $table = Table::factory()->available()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'floor_id' => $this->floor->id,
        ]);

        $session = TableSession::openTable($table);

        $this->assertEquals(1, $session->guest_count);
    }

    // ==================== SCOPE TESTS ====================

    public function test_scope_active(): void
    {
        TableSession::factory()->count(2)->active()->create([
            'tenant_id' => $this->tenant->id,
            'table_id' => $this->table->id,
            'opened_by' => $this->user->id,
        ]);
        TableSession::factory()->closed()->create([
            'tenant_id' => $this->tenant->id,
            'table_id' => $this->table->id,
            'opened_by' => $this->user->id,
        ]);

        $activeSessions = TableSession::active()->get();

        $this->assertCount(2, $activeSessions);
    }

    public function test_scope_closed(): void
    {
        TableSession::factory()->count(3)->closed()->create([
            'tenant_id' => $this->tenant->id,
            'table_id' => $this->table->id,
            'opened_by' => $this->user->id,
        ]);
        TableSession::factory()->active()->create([
            'tenant_id' => $this->tenant->id,
            'table_id' => $this->table->id,
            'opened_by' => $this->user->id,
        ]);

        $closedSessions = TableSession::closed()->get();

        $this->assertCount(3, $closedSessions);
    }

    public function test_scope_for_table(): void
    {
        $table2 = Table::factory()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'floor_id' => $this->floor->id,
        ]);

        TableSession::factory()->count(4)->create([
            'tenant_id' => $this->tenant->id,
            'table_id' => $this->table->id,
            'opened_by' => $this->user->id,
        ]);
        TableSession::factory()->count(2)->create([
            'tenant_id' => $this->tenant->id,
            'table_id' => $table2->id,
            'opened_by' => $this->user->id,
        ]);

        $table1Sessions = TableSession::forTable($this->table->id)->get();

        $this->assertCount(4, $table1Sessions);
    }

    // ==================== CASTING TESTS ====================

    public function test_opened_at_is_cast_to_datetime(): void
    {
        $session = TableSession::factory()->create([
            'tenant_id' => $this->tenant->id,
            'table_id' => $this->table->id,
            'opened_by' => $this->user->id,
        ]);

        $this->assertInstanceOf(Carbon::class, $session->opened_at);
    }

    public function test_closed_at_is_cast_to_datetime(): void
    {
        $session = TableSession::factory()->closed()->create([
            'tenant_id' => $this->tenant->id,
            'table_id' => $this->table->id,
            'opened_by' => $this->user->id,
        ]);

        $this->assertInstanceOf(Carbon::class, $session->closed_at);
    }

    public function test_guest_count_is_cast_to_integer(): void
    {
        $session = TableSession::factory()->create([
            'tenant_id' => $this->tenant->id,
            'table_id' => $this->table->id,
            'opened_by' => $this->user->id,
            'guest_count' => 6,
        ]);

        $this->assertIsInt($session->guest_count);
        $this->assertEquals(6, $session->guest_count);
    }

    // ==================== FACTORY STATE TESTS ====================

    public function test_factory_active_state(): void
    {
        $session = TableSession::factory()->active()->create([
            'tenant_id' => $this->tenant->id,
            'table_id' => $this->table->id,
            'opened_by' => $this->user->id,
        ]);

        $this->assertEquals(TableSession::STATUS_ACTIVE, $session->status);
        $this->assertNull($session->closed_at);
        $this->assertNull($session->closed_by);
    }

    public function test_factory_closed_state(): void
    {
        $session = TableSession::factory()->closed()->create([
            'tenant_id' => $this->tenant->id,
            'table_id' => $this->table->id,
            'opened_by' => $this->user->id,
        ]);

        $this->assertEquals(TableSession::STATUS_CLOSED, $session->status);
        $this->assertNotNull($session->closed_at);
        $this->assertNotNull($session->closed_by);
    }

    public function test_factory_with_guests_state(): void
    {
        $session = TableSession::factory()->withGuests(8)->create([
            'tenant_id' => $this->tenant->id,
            'table_id' => $this->table->id,
            'opened_by' => $this->user->id,
        ]);

        $this->assertEquals(8, $session->guest_count);
    }

    public function test_factory_opened_hours_ago_state(): void
    {
        $session = TableSession::factory()->openedHoursAgo(2)->create([
            'tenant_id' => $this->tenant->id,
            'table_id' => $this->table->id,
            'opened_by' => $this->user->id,
        ]);

        $this->assertTrue($session->opened_at->diffInHours(now()) >= 2);
    }

    public function test_factory_opened_minutes_ago_state(): void
    {
        $session = TableSession::factory()->openedMinutesAgo(30)->create([
            'tenant_id' => $this->tenant->id,
            'table_id' => $this->table->id,
            'opened_by' => $this->user->id,
        ]);

        $this->assertTrue($session->opened_at->diffInMinutes(now()) >= 30);
    }

    public function test_factory_with_notes_state(): void
    {
        $session = TableSession::factory()->withNotes('VIP guests')->create([
            'tenant_id' => $this->tenant->id,
            'table_id' => $this->table->id,
            'opened_by' => $this->user->id,
        ]);

        $this->assertEquals('VIP guests', $session->notes);
    }

    public function test_factory_closed_after_minutes_state(): void
    {
        $session = TableSession::factory()->closedAfterMinutes(60)->create([
            'tenant_id' => $this->tenant->id,
            'table_id' => $this->table->id,
            'opened_by' => $this->user->id,
        ]);

        $this->assertTrue($session->isClosed());
        $this->assertEquals(60, $session->durationMinutes);
    }

    // ==================== UUID TRAIT TESTS ====================

    public function test_table_session_uses_uuid(): void
    {
        $session = TableSession::factory()->create([
            'tenant_id' => $this->tenant->id,
            'table_id' => $this->table->id,
            'opened_by' => $this->user->id,
        ]);

        $this->assertNotNull($session->id);
        $this->assertIsString($session->id);
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/',
            $session->id
        );
    }

    // ==================== SESSION LIFECYCLE TESTS ====================

    public function test_complete_session_lifecycle(): void
    {
        // 1. Open table
        $table = Table::factory()->available()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'floor_id' => $this->floor->id,
        ]);

        $session = TableSession::openTable($table, 4, $this->user->id);
        $table->refresh();

        $this->assertTrue($session->isActive());
        $this->assertTrue($table->isOccupied());

        // 2. Close session - load the table relationship
        $session->close($this->user->id);
        $session->refresh();
        $table->refresh();

        $this->assertTrue($session->isClosed());
        $this->assertTrue($table->hasStatusDirty());
        $this->assertNotNull($session->closed_at);
        $this->assertEquals($this->user->id, $session->closed_by);
    }

    public function test_multiple_sessions_on_same_table(): void
    {
        // First session
        $session1 = TableSession::factory()->closedAfterMinutes(60)->create([
            'tenant_id' => $this->tenant->id,
            'table_id' => $this->table->id,
            'opened_by' => $this->user->id,
        ]);

        // Second session
        $session2 = TableSession::factory()->closedAfterMinutes(45)->create([
            'tenant_id' => $this->tenant->id,
            'table_id' => $this->table->id,
            'opened_by' => $this->user->id,
        ]);

        // Third session (active)
        $session3 = TableSession::factory()->active()->create([
            'tenant_id' => $this->tenant->id,
            'table_id' => $this->table->id,
            'opened_by' => $this->user->id,
        ]);

        $tableSessions = $this->table->sessions;
        $this->assertCount(3, $tableSessions);

        $activeSessions = TableSession::forTable($this->table->id)->active()->get();
        $this->assertCount(1, $activeSessions);
    }

    // ==================== COMBINED STATE TESTS ====================

    public function test_combined_factory_states(): void
    {
        $session = TableSession::factory()
            ->withGuests(6)
            ->withNotes('Anniversary dinner')
            ->openedMinutesAgo(30)
            ->active()
            ->create([
                'tenant_id' => $this->tenant->id,
                'table_id' => $this->table->id,
                'opened_by' => $this->user->id,
            ]);

        $this->assertEquals(6, $session->guest_count);
        $this->assertEquals('Anniversary dinner', $session->notes);
        $this->assertTrue($session->isActive());
        $this->assertEquals(30, $session->durationMinutes);
    }

    // ==================== EDGE CASE TESTS ====================

    public function test_session_with_zero_duration(): void
    {
        $session = TableSession::factory()->create([
            'tenant_id' => $this->tenant->id,
            'table_id' => $this->table->id,
            'opened_by' => $this->user->id,
            'opened_at' => now(),
        ]);

        $this->assertEquals(0, $session->durationMinutes);
        $this->assertEquals('0 menit', $session->durationFormatted);
    }

    public function test_session_with_null_notes(): void
    {
        $session = TableSession::factory()->create([
            'tenant_id' => $this->tenant->id,
            'table_id' => $this->table->id,
            'opened_by' => $this->user->id,
            'notes' => null,
        ]);

        $this->assertNull($session->notes);
    }

    public function test_session_with_one_guest(): void
    {
        $session = TableSession::factory()->withGuests(1)->create([
            'tenant_id' => $this->tenant->id,
            'table_id' => $this->table->id,
            'opened_by' => $this->user->id,
        ]);

        $this->assertEquals(1, $session->guest_count);
    }
}
