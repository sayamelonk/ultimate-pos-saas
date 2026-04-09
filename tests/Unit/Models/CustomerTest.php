<?php

namespace Tests\Unit\Models;

use App\Models\Customer;
use App\Models\CustomerPoint;
use App\Models\Tenant;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class CustomerTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tenant = Tenant::factory()->create();
        $this->user = User::factory()->create(['tenant_id' => $this->tenant->id]);
    }

    // ==================== CREATION TESTS ====================

    public function test_can_create_customer(): void
    {
        $customer = Customer::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'phone' => '081234567890',
        ]);

        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'phone' => '081234567890',
        ]);
    }

    public function test_customer_has_required_attributes(): void
    {
        $customer = Customer::factory()->create(['tenant_id' => $this->tenant->id]);

        $this->assertNotNull($customer->id);
        $this->assertNotNull($customer->tenant_id);
        $this->assertNotNull($customer->code);
        $this->assertNotNull($customer->name);
    }

    public function test_customer_can_have_optional_attributes(): void
    {
        $customer = Customer::factory()->create([
            'tenant_id' => $this->tenant->id,
            'address' => '123 Main Street',
            'birth_date' => '1990-05-15',
            'gender' => 'male',
            'notes' => 'VIP customer',
        ]);

        $this->assertEquals('123 Main Street', $customer->address);
        $this->assertEquals('1990-05-15', $customer->birth_date->format('Y-m-d'));
        $this->assertEquals('male', $customer->gender);
        $this->assertEquals('VIP customer', $customer->notes);
    }

    public function test_customer_uses_soft_deletes(): void
    {
        $customer = Customer::factory()->create(['tenant_id' => $this->tenant->id]);

        $customer->delete();

        $this->assertSoftDeleted('customers', ['id' => $customer->id]);
    }

    // ==================== CONSTANT TESTS ====================

    public function test_membership_level_constants(): void
    {
        $this->assertEquals('regular', Customer::LEVEL_REGULAR);
        $this->assertEquals('silver', Customer::LEVEL_SILVER);
        $this->assertEquals('gold', Customer::LEVEL_GOLD);
        $this->assertEquals('platinum', Customer::LEVEL_PLATINUM);
    }

    // ==================== RELATIONSHIP TESTS ====================

    public function test_customer_belongs_to_tenant(): void
    {
        $customer = Customer::factory()->create(['tenant_id' => $this->tenant->id]);

        $this->assertInstanceOf(Tenant::class, $customer->tenant);
        $this->assertEquals($this->tenant->id, $customer->tenant->id);
    }

    public function test_customer_has_many_points(): void
    {
        $customer = Customer::factory()->create(['tenant_id' => $this->tenant->id]);

        $this->assertInstanceOf(HasMany::class, $customer->points());
    }

    public function test_customer_has_many_transactions(): void
    {
        $customer = Customer::factory()->create(['tenant_id' => $this->tenant->id]);

        $this->assertInstanceOf(HasMany::class, $customer->transactions());
    }

    // ==================== ADD POINTS TESTS ====================

    public function test_add_points_increases_total_points(): void
    {
        $customer = Customer::factory()->create([
            'tenant_id' => $this->tenant->id,
            'total_points' => 100,
        ]);

        $customer->addPoints(50, null, $this->user->id, 'Bonus points');
        $customer->refresh();

        $this->assertEquals(150, $customer->total_points);
    }

    public function test_add_points_creates_point_record(): void
    {
        $customer = Customer::factory()->create([
            'tenant_id' => $this->tenant->id,
            'total_points' => 100,
        ]);

        $pointRecord = $customer->addPoints(50, null, $this->user->id, 'Purchase bonus');

        $this->assertInstanceOf(CustomerPoint::class, $pointRecord);
        $this->assertEquals('earned', $pointRecord->type);
        $this->assertEquals(50, $pointRecord->points);
        $this->assertEquals(100, $pointRecord->balance_before);
        $this->assertEquals(150, $pointRecord->balance_after);
        $this->assertEquals('Purchase bonus', $pointRecord->description);
    }

    public function test_add_points_with_transaction(): void
    {
        $customer = Customer::factory()->create(['tenant_id' => $this->tenant->id]);
        $transaction = Transaction::factory()->create(['tenant_id' => $this->tenant->id]);

        $pointRecord = $customer->addPoints(100, $transaction->id, $this->user->id);

        $this->assertEquals($transaction->id, $pointRecord->transaction_id);
    }

    public function test_add_points_from_zero(): void
    {
        $customer = Customer::factory()->create([
            'tenant_id' => $this->tenant->id,
            'total_points' => 0,
        ]);

        $customer->addPoints(200, null, $this->user->id);
        $customer->refresh();

        $this->assertEquals(200, $customer->total_points);
    }

    // ==================== REDEEM POINTS TESTS ====================

    public function test_redeem_points_decreases_total_points(): void
    {
        $customer = Customer::factory()->create([
            'tenant_id' => $this->tenant->id,
            'total_points' => 500,
        ]);

        $customer->redeemPoints(200, null, $this->user->id, 'Discount redemption');
        $customer->refresh();

        $this->assertEquals(300, $customer->total_points);
    }

    public function test_redeem_points_creates_point_record(): void
    {
        $customer = Customer::factory()->create([
            'tenant_id' => $this->tenant->id,
            'total_points' => 500,
        ]);

        $pointRecord = $customer->redeemPoints(200, null, $this->user->id, 'Redemption');

        $this->assertInstanceOf(CustomerPoint::class, $pointRecord);
        $this->assertEquals('redeemed', $pointRecord->type);
        $this->assertEquals(-200, $pointRecord->points);
        $this->assertEquals(500, $pointRecord->balance_before);
        $this->assertEquals(300, $pointRecord->balance_after);
    }

    public function test_redeem_all_points(): void
    {
        $customer = Customer::factory()->create([
            'tenant_id' => $this->tenant->id,
            'total_points' => 100,
        ]);

        $customer->redeemPoints(100, null, $this->user->id);
        $customer->refresh();

        $this->assertEquals(0, $customer->total_points);
    }

    // ==================== ADJUST POINTS TESTS ====================

    public function test_adjust_points_positive(): void
    {
        $customer = Customer::factory()->create([
            'tenant_id' => $this->tenant->id,
            'total_points' => 100,
        ]);

        $customer->adjustPoints(50, $this->user->id, 'Manual adjustment');
        $customer->refresh();

        $this->assertEquals(150, $customer->total_points);
    }

    public function test_adjust_points_negative(): void
    {
        $customer = Customer::factory()->create([
            'tenant_id' => $this->tenant->id,
            'total_points' => 100,
        ]);

        $customer->adjustPoints(-30, $this->user->id, 'Points correction');
        $customer->refresh();

        $this->assertEquals(70, $customer->total_points);
    }

    public function test_adjust_points_creates_point_record(): void
    {
        $customer = Customer::factory()->create([
            'tenant_id' => $this->tenant->id,
            'total_points' => 100,
        ]);

        $pointRecord = $customer->adjustPoints(25, $this->user->id, 'Bonus adjustment');

        $this->assertInstanceOf(CustomerPoint::class, $pointRecord);
        $this->assertEquals('adjustment', $pointRecord->type);
        $this->assertEquals(25, $pointRecord->points);
        $this->assertEquals(100, $pointRecord->balance_before);
        $this->assertEquals(125, $pointRecord->balance_after);
    }

    public function test_adjust_points_without_transaction_id(): void
    {
        $customer = Customer::factory()->create(['tenant_id' => $this->tenant->id]);

        $pointRecord = $customer->adjustPoints(50, $this->user->id);

        $this->assertNull($pointRecord->transaction_id);
    }

    // ==================== GET POINTS VALUE TESTS ====================

    public function test_get_points_value_returns_correct_amount(): void
    {
        $customer = Customer::factory()->create([
            'tenant_id' => $this->tenant->id,
            'total_points' => 100,
        ]);

        // 100 points * 100 = 10000
        $this->assertEquals(10000, $customer->getPointsValue());
    }

    public function test_get_points_value_with_zero_points(): void
    {
        $customer = Customer::factory()->create([
            'tenant_id' => $this->tenant->id,
            'total_points' => 0,
        ]);

        $this->assertEquals(0, $customer->getPointsValue());
    }

    public function test_get_points_value_with_decimal_points(): void
    {
        $customer = Customer::factory()->create([
            'tenant_id' => $this->tenant->id,
            'total_points' => 50.50,
        ]);

        $this->assertEquals(5050, $customer->getPointsValue());
    }

    // ==================== IS MEMBER TESTS ====================

    public function test_is_member_returns_false_for_regular(): void
    {
        $customer = Customer::factory()->create([
            'tenant_id' => $this->tenant->id,
            'membership_level' => Customer::LEVEL_REGULAR,
        ]);

        $this->assertFalse($customer->isMember());
    }

    public function test_is_member_returns_true_for_silver(): void
    {
        $customer = Customer::factory()->silver()->create(['tenant_id' => $this->tenant->id]);

        $this->assertTrue($customer->isMember());
    }

    public function test_is_member_returns_true_for_gold(): void
    {
        $customer = Customer::factory()->gold()->create(['tenant_id' => $this->tenant->id]);

        $this->assertTrue($customer->isMember());
    }

    public function test_is_member_returns_true_for_platinum(): void
    {
        $customer = Customer::factory()->platinum()->create(['tenant_id' => $this->tenant->id]);

        $this->assertTrue($customer->isMember());
    }

    // ==================== IS MEMBERSHIP ACTIVE TESTS ====================

    public function test_is_membership_active_returns_true_when_no_expiry(): void
    {
        $customer = Customer::factory()->memberWithNoExpiry()->create(['tenant_id' => $this->tenant->id]);

        $this->assertTrue($customer->isMembershipActive());
    }

    public function test_is_membership_active_returns_true_when_future_expiry(): void
    {
        $customer = Customer::factory()->create([
            'tenant_id' => $this->tenant->id,
            'membership_level' => Customer::LEVEL_SILVER,
            'membership_expires_at' => now()->addMonths(3),
        ]);

        $this->assertTrue($customer->isMembershipActive());
    }

    public function test_is_membership_active_returns_false_when_expired(): void
    {
        $customer = Customer::factory()->expiredMembership()->create(['tenant_id' => $this->tenant->id]);

        $this->assertFalse($customer->isMembershipActive());
    }

    public function test_is_membership_active_when_expires_today(): void
    {
        $customer = Customer::factory()->create([
            'tenant_id' => $this->tenant->id,
            'membership_level' => Customer::LEVEL_SILVER,
            'membership_expires_at' => now()->startOfDay(),
        ]);

        // Today's date should be past
        $this->assertFalse($customer->isMembershipActive());
    }

    // ==================== GET MEMBERSHIP LEVELS TESTS ====================

    public function test_get_membership_levels_returns_all_levels(): void
    {
        $levels = Customer::getMembershipLevels();

        $this->assertIsArray($levels);
        $this->assertArrayHasKey(Customer::LEVEL_REGULAR, $levels);
        $this->assertArrayHasKey(Customer::LEVEL_SILVER, $levels);
        $this->assertArrayHasKey(Customer::LEVEL_GOLD, $levels);
        $this->assertArrayHasKey(Customer::LEVEL_PLATINUM, $levels);
        $this->assertEquals('Regular', $levels[Customer::LEVEL_REGULAR]);
        $this->assertEquals('Silver', $levels[Customer::LEVEL_SILVER]);
        $this->assertEquals('Gold', $levels[Customer::LEVEL_GOLD]);
        $this->assertEquals('Platinum', $levels[Customer::LEVEL_PLATINUM]);
    }

    // ==================== CASTING TESTS ====================

    public function test_birth_date_is_cast_to_date(): void
    {
        $customer = Customer::factory()->create([
            'tenant_id' => $this->tenant->id,
            'birth_date' => '1990-05-15',
        ]);

        $this->assertInstanceOf(Carbon::class, $customer->birth_date);
    }

    public function test_joined_at_is_cast_to_date(): void
    {
        $customer = Customer::factory()->create(['tenant_id' => $this->tenant->id]);

        $this->assertInstanceOf(Carbon::class, $customer->joined_at);
    }

    public function test_membership_expires_at_is_cast_to_date(): void
    {
        $customer = Customer::factory()->silver()->create(['tenant_id' => $this->tenant->id]);

        $this->assertInstanceOf(Carbon::class, $customer->membership_expires_at);
    }

    public function test_total_points_is_cast_to_decimal(): void
    {
        $customer = Customer::factory()->create([
            'tenant_id' => $this->tenant->id,
            'total_points' => 100.50,
        ]);

        $this->assertEquals(100.50, $customer->total_points);
    }

    public function test_total_spent_is_cast_to_decimal(): void
    {
        $customer = Customer::factory()->create([
            'tenant_id' => $this->tenant->id,
            'total_spent' => 1500000.50,
        ]);

        $this->assertEquals(1500000.50, $customer->total_spent);
    }

    public function test_total_visits_is_cast_to_integer(): void
    {
        $customer = Customer::factory()->create([
            'tenant_id' => $this->tenant->id,
            'total_visits' => 25,
        ]);

        $this->assertIsInt($customer->total_visits);
        $this->assertEquals(25, $customer->total_visits);
    }

    public function test_is_active_is_cast_to_boolean(): void
    {
        $customer = Customer::factory()->create([
            'tenant_id' => $this->tenant->id,
            'is_active' => 1,
        ]);

        $this->assertIsBool($customer->is_active);
        $this->assertTrue($customer->is_active);
    }

    // ==================== FACTORY STATE TESTS ====================

    public function test_factory_inactive_state(): void
    {
        $customer = Customer::factory()->inactive()->create(['tenant_id' => $this->tenant->id]);

        $this->assertFalse($customer->is_active);
    }

    public function test_factory_silver_state(): void
    {
        $customer = Customer::factory()->silver()->create(['tenant_id' => $this->tenant->id]);

        $this->assertEquals(Customer::LEVEL_SILVER, $customer->membership_level);
        $this->assertNotNull($customer->membership_expires_at);
    }

    public function test_factory_gold_state(): void
    {
        $customer = Customer::factory()->gold()->create(['tenant_id' => $this->tenant->id]);

        $this->assertEquals(Customer::LEVEL_GOLD, $customer->membership_level);
        $this->assertNotNull($customer->membership_expires_at);
    }

    public function test_factory_platinum_state(): void
    {
        $customer = Customer::factory()->platinum()->create(['tenant_id' => $this->tenant->id]);

        $this->assertEquals(Customer::LEVEL_PLATINUM, $customer->membership_level);
        $this->assertNotNull($customer->membership_expires_at);
    }

    public function test_factory_with_activity_state(): void
    {
        $customer = Customer::factory()->withActivity()->create(['tenant_id' => $this->tenant->id]);

        $this->assertGreaterThan(0, $customer->total_points);
        $this->assertGreaterThan(0, $customer->total_spent);
        $this->assertGreaterThan(0, $customer->total_visits);
    }

    public function test_factory_with_points_state(): void
    {
        $customer = Customer::factory()->withPoints(500)->create(['tenant_id' => $this->tenant->id]);

        $this->assertEquals(500, $customer->total_points);
    }

    public function test_factory_with_spent_state(): void
    {
        $customer = Customer::factory()->withSpent(5000000)->create(['tenant_id' => $this->tenant->id]);

        $this->assertEquals(5000000, $customer->total_spent);
    }

    public function test_factory_with_visits_state(): void
    {
        $customer = Customer::factory()->withVisits(50)->create(['tenant_id' => $this->tenant->id]);

        $this->assertEquals(50, $customer->total_visits);
    }

    public function test_factory_expired_membership_state(): void
    {
        $customer = Customer::factory()->expiredMembership()->create(['tenant_id' => $this->tenant->id]);

        $this->assertTrue($customer->membership_expires_at->isPast());
    }

    public function test_factory_member_with_no_expiry_state(): void
    {
        $customer = Customer::factory()->memberWithNoExpiry()->create(['tenant_id' => $this->tenant->id]);

        $this->assertEquals(Customer::LEVEL_GOLD, $customer->membership_level);
        $this->assertNull($customer->membership_expires_at);
    }

    public function test_factory_male_state(): void
    {
        $customer = Customer::factory()->male()->create(['tenant_id' => $this->tenant->id]);

        $this->assertEquals('male', $customer->gender);
    }

    public function test_factory_female_state(): void
    {
        $customer = Customer::factory()->female()->create(['tenant_id' => $this->tenant->id]);

        $this->assertEquals('female', $customer->gender);
    }

    // ==================== UUID TRAIT TESTS ====================

    public function test_customer_uses_uuid(): void
    {
        $customer = Customer::factory()->create(['tenant_id' => $this->tenant->id]);

        $this->assertNotNull($customer->id);
        $this->assertIsString($customer->id);
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/',
            $customer->id
        );
    }

    // ==================== TENANT ISOLATION TESTS ====================

    public function test_customers_are_tenant_isolated(): void
    {
        $tenant1 = Tenant::factory()->create();
        $tenant2 = Tenant::factory()->create();

        Customer::factory()->count(3)->create(['tenant_id' => $tenant1->id]);
        Customer::factory()->count(2)->create(['tenant_id' => $tenant2->id]);

        $tenant1Customers = Customer::where('tenant_id', $tenant1->id)->get();
        $tenant2Customers = Customer::where('tenant_id', $tenant2->id)->get();

        $this->assertCount(3, $tenant1Customers);
        $this->assertCount(2, $tenant2Customers);
    }

    // ==================== POINT HISTORY TESTS ====================

    public function test_customer_can_have_multiple_point_records(): void
    {
        $customer = Customer::factory()->create(['tenant_id' => $this->tenant->id]);

        $customer->addPoints(100, null, $this->user->id);
        $customer->addPoints(50, null, $this->user->id);
        $customer->redeemPoints(30, null, $this->user->id);

        $this->assertCount(3, $customer->points);
    }

    public function test_point_balance_tracking(): void
    {
        $customer = Customer::factory()->create([
            'tenant_id' => $this->tenant->id,
            'total_points' => 0,
        ]);

        $point1 = $customer->addPoints(100, null, $this->user->id);
        $this->assertEquals(0, $point1->balance_before);
        $this->assertEquals(100, $point1->balance_after);

        $point2 = $customer->addPoints(50, null, $this->user->id);
        $this->assertEquals(100, $point2->balance_before);
        $this->assertEquals(150, $point2->balance_after);

        $point3 = $customer->redeemPoints(30, null, $this->user->id);
        $this->assertEquals(150, $point3->balance_before);
        $this->assertEquals(120, $point3->balance_after);
    }

    // ==================== COMBINED STATE TESTS ====================

    public function test_combined_factory_states(): void
    {
        $customer = Customer::factory()
            ->gold()
            ->withPoints(1000)
            ->withSpent(5000000)
            ->withVisits(50)
            ->male()
            ->create(['tenant_id' => $this->tenant->id]);

        $this->assertEquals(Customer::LEVEL_GOLD, $customer->membership_level);
        $this->assertEquals(1000, $customer->total_points);
        $this->assertEquals(5000000, $customer->total_spent);
        $this->assertEquals(50, $customer->total_visits);
        $this->assertEquals('male', $customer->gender);
    }

    // ==================== EDGE CASE TESTS ====================

    public function test_customer_with_null_optional_fields(): void
    {
        $customer = Customer::factory()->create([
            'tenant_id' => $this->tenant->id,
            'address' => null,
            'birth_date' => null,
            'gender' => null,
            'notes' => null,
        ]);

        $this->assertNull($customer->address);
        $this->assertNull($customer->birth_date);
        $this->assertNull($customer->gender);
        $this->assertNull($customer->notes);
    }

    public function test_customer_with_very_large_points(): void
    {
        $customer = Customer::factory()->create([
            'tenant_id' => $this->tenant->id,
            'total_points' => 999999999.99,
        ]);

        $this->assertEquals(999999999.99, $customer->total_points);
    }

    public function test_customer_email_uniqueness_per_tenant(): void
    {
        $customer1 = Customer::factory()->create([
            'tenant_id' => $this->tenant->id,
            'email' => 'unique@example.com',
        ]);

        $tenant2 = Tenant::factory()->create();
        $customer2 = Customer::factory()->create([
            'tenant_id' => $tenant2->id,
            'email' => 'unique@example.com', // Same email, different tenant
        ]);

        $this->assertEquals($customer1->email, $customer2->email);
        $this->assertNotEquals($customer1->tenant_id, $customer2->tenant_id);
    }

    public function test_customer_code_is_generated(): void
    {
        $customer = Customer::factory()->create(['tenant_id' => $this->tenant->id]);

        $this->assertNotNull($customer->code);
        $this->assertStringStartsWith('CUST-', $customer->code);
    }
}
