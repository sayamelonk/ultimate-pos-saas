<?php

namespace Tests\Unit\Models;

use App\Models\Customer;
use App\Models\CustomerPoint;
use App\Models\Tenant;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerPointTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;

    protected User $user;

    protected Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tenant = Tenant::factory()->create();
        $this->user = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->customer = Customer::factory()->create(['tenant_id' => $this->tenant->id]);
    }

    // ==================== CREATION TESTS ====================

    public function test_can_create_customer_point(): void
    {
        $customerPoint = CustomerPoint::factory()->create([
            'customer_id' => $this->customer->id,
            'created_by' => $this->user->id,
            'type' => CustomerPoint::TYPE_EARNED,
            'points' => 100,
        ]);

        $this->assertDatabaseHas('customer_points', [
            'id' => $customerPoint->id,
            'customer_id' => $this->customer->id,
            'type' => CustomerPoint::TYPE_EARNED,
        ]);
    }

    public function test_customer_point_has_required_attributes(): void
    {
        $customerPoint = CustomerPoint::factory()->create([
            'customer_id' => $this->customer->id,
            'created_by' => $this->user->id,
        ]);

        $this->assertNotNull($customerPoint->id);
        $this->assertNotNull($customerPoint->customer_id);
        $this->assertNotNull($customerPoint->type);
        $this->assertNotNull($customerPoint->points);
        $this->assertNotNull($customerPoint->created_by);
    }

    public function test_customer_point_can_have_optional_attributes(): void
    {
        $transaction = Transaction::factory()->create(['tenant_id' => $this->tenant->id]);
        $customerPoint = CustomerPoint::factory()->create([
            'customer_id' => $this->customer->id,
            'created_by' => $this->user->id,
            'transaction_id' => $transaction->id,
            'description' => 'Bonus purchase points',
        ]);

        $this->assertEquals($transaction->id, $customerPoint->transaction_id);
        $this->assertEquals('Bonus purchase points', $customerPoint->description);
    }

    // ==================== CONSTANT TESTS ====================

    public function test_point_type_constants(): void
    {
        $this->assertEquals('earned', CustomerPoint::TYPE_EARNED);
        $this->assertEquals('redeemed', CustomerPoint::TYPE_REDEEMED);
        $this->assertEquals('expired', CustomerPoint::TYPE_EXPIRED);
        $this->assertEquals('adjustment', CustomerPoint::TYPE_ADJUSTMENT);
    }

    // ==================== RELATIONSHIP TESTS ====================

    public function test_customer_point_belongs_to_customer(): void
    {
        $customerPoint = CustomerPoint::factory()->create([
            'customer_id' => $this->customer->id,
            'created_by' => $this->user->id,
        ]);

        $this->assertInstanceOf(Customer::class, $customerPoint->customer);
        $this->assertEquals($this->customer->id, $customerPoint->customer->id);
    }

    public function test_customer_point_belongs_to_transaction(): void
    {
        $transaction = Transaction::factory()->create(['tenant_id' => $this->tenant->id]);
        $customerPoint = CustomerPoint::factory()->create([
            'customer_id' => $this->customer->id,
            'created_by' => $this->user->id,
            'transaction_id' => $transaction->id,
        ]);

        $this->assertInstanceOf(Transaction::class, $customerPoint->transaction);
        $this->assertEquals($transaction->id, $customerPoint->transaction->id);
    }

    public function test_customer_point_can_have_null_transaction(): void
    {
        $customerPoint = CustomerPoint::factory()->create([
            'customer_id' => $this->customer->id,
            'created_by' => $this->user->id,
            'transaction_id' => null,
        ]);

        $this->assertNull($customerPoint->transaction);
    }

    public function test_customer_point_belongs_to_created_by_user(): void
    {
        $customerPoint = CustomerPoint::factory()->create([
            'customer_id' => $this->customer->id,
            'created_by' => $this->user->id,
        ]);

        $this->assertInstanceOf(User::class, $customerPoint->createdByUser);
        $this->assertEquals($this->user->id, $customerPoint->createdByUser->id);
    }

    // ==================== GET TYPES TESTS ====================

    public function test_get_types_returns_all_types(): void
    {
        $types = CustomerPoint::getTypes();

        $this->assertIsArray($types);
        $this->assertArrayHasKey(CustomerPoint::TYPE_EARNED, $types);
        $this->assertArrayHasKey(CustomerPoint::TYPE_REDEEMED, $types);
        $this->assertArrayHasKey(CustomerPoint::TYPE_EXPIRED, $types);
        $this->assertArrayHasKey(CustomerPoint::TYPE_ADJUSTMENT, $types);
        $this->assertEquals('Earned', $types[CustomerPoint::TYPE_EARNED]);
        $this->assertEquals('Redeemed', $types[CustomerPoint::TYPE_REDEEMED]);
        $this->assertEquals('Expired', $types[CustomerPoint::TYPE_EXPIRED]);
        $this->assertEquals('Adjustment', $types[CustomerPoint::TYPE_ADJUSTMENT]);
    }

    // ==================== CASTING TESTS ====================

    public function test_points_is_cast_to_decimal(): void
    {
        $customerPoint = CustomerPoint::factory()->create([
            'customer_id' => $this->customer->id,
            'created_by' => $this->user->id,
            'points' => 100.50,
        ]);

        $this->assertEquals(100.50, $customerPoint->points);
    }

    public function test_balance_before_is_cast_to_decimal(): void
    {
        $customerPoint = CustomerPoint::factory()->create([
            'customer_id' => $this->customer->id,
            'created_by' => $this->user->id,
            'balance_before' => 500.25,
        ]);

        $this->assertEquals(500.25, $customerPoint->balance_before);
    }

    public function test_balance_after_is_cast_to_decimal(): void
    {
        $customerPoint = CustomerPoint::factory()->create([
            'customer_id' => $this->customer->id,
            'created_by' => $this->user->id,
            'balance_after' => 600.75,
        ]);

        $this->assertEquals(600.75, $customerPoint->balance_after);
    }

    // ==================== FACTORY STATE TESTS ====================

    public function test_factory_earned_state(): void
    {
        $customerPoint = CustomerPoint::factory()->earned(150)->create([
            'customer_id' => $this->customer->id,
            'created_by' => $this->user->id,
        ]);

        $this->assertEquals(CustomerPoint::TYPE_EARNED, $customerPoint->type);
        $this->assertEquals(150, $customerPoint->points);
    }

    public function test_factory_redeemed_state(): void
    {
        $customerPoint = CustomerPoint::factory()->redeemed(75)->create([
            'customer_id' => $this->customer->id,
            'created_by' => $this->user->id,
        ]);

        $this->assertEquals(CustomerPoint::TYPE_REDEEMED, $customerPoint->type);
        $this->assertEquals(-75, $customerPoint->points);
    }

    public function test_factory_expired_state(): void
    {
        $customerPoint = CustomerPoint::factory()->expired(30)->create([
            'customer_id' => $this->customer->id,
            'created_by' => $this->user->id,
        ]);

        $this->assertEquals(CustomerPoint::TYPE_EXPIRED, $customerPoint->type);
        $this->assertEquals(-30, $customerPoint->points);
        $this->assertEquals('Points expired', $customerPoint->description);
    }

    public function test_factory_adjustment_state(): void
    {
        $customerPoint = CustomerPoint::factory()->adjustment(20)->create([
            'customer_id' => $this->customer->id,
            'created_by' => $this->user->id,
        ]);

        $this->assertEquals(CustomerPoint::TYPE_ADJUSTMENT, $customerPoint->type);
        $this->assertEquals(20, $customerPoint->points);
        $this->assertEquals('Manual adjustment', $customerPoint->description);
    }

    public function test_factory_with_transaction_state(): void
    {
        $customerPoint = CustomerPoint::factory()->withTransaction()->create([
            'customer_id' => $this->customer->id,
            'created_by' => $this->user->id,
        ]);

        $this->assertNotNull($customerPoint->transaction_id);
    }

    public function test_factory_with_description_state(): void
    {
        $customerPoint = CustomerPoint::factory()->withDescription('Birthday bonus')->create([
            'customer_id' => $this->customer->id,
            'created_by' => $this->user->id,
        ]);

        $this->assertEquals('Birthday bonus', $customerPoint->description);
    }

    public function test_factory_with_balance_state(): void
    {
        $customerPoint = CustomerPoint::factory()->withBalance(100, 200)->create([
            'customer_id' => $this->customer->id,
            'created_by' => $this->user->id,
        ]);

        $this->assertEquals(100, $customerPoint->balance_before);
        $this->assertEquals(200, $customerPoint->balance_after);
    }

    // ==================== UUID TRAIT TESTS ====================

    public function test_customer_point_uses_uuid(): void
    {
        $customerPoint = CustomerPoint::factory()->create([
            'customer_id' => $this->customer->id,
            'created_by' => $this->user->id,
        ]);

        $this->assertNotNull($customerPoint->id);
        $this->assertIsString($customerPoint->id);
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/',
            $customerPoint->id
        );
    }

    // ==================== POINT HISTORY TESTS ====================

    public function test_customer_can_have_multiple_point_records(): void
    {
        CustomerPoint::factory()->earned(100)->create([
            'customer_id' => $this->customer->id,
            'created_by' => $this->user->id,
        ]);
        CustomerPoint::factory()->earned(50)->create([
            'customer_id' => $this->customer->id,
            'created_by' => $this->user->id,
        ]);
        CustomerPoint::factory()->redeemed(30)->create([
            'customer_id' => $this->customer->id,
            'created_by' => $this->user->id,
        ]);

        $this->assertCount(3, $this->customer->points);
    }

    public function test_point_records_are_ordered_by_created_at(): void
    {
        $point1 = CustomerPoint::factory()->earned(100)->create([
            'customer_id' => $this->customer->id,
            'created_by' => $this->user->id,
            'created_at' => now()->subHours(2),
        ]);
        $point2 = CustomerPoint::factory()->earned(50)->create([
            'customer_id' => $this->customer->id,
            'created_by' => $this->user->id,
            'created_at' => now()->subHours(1),
        ]);
        $point3 = CustomerPoint::factory()->redeemed(30)->create([
            'customer_id' => $this->customer->id,
            'created_by' => $this->user->id,
            'created_at' => now(),
        ]);

        $points = $this->customer->points()->orderBy('created_at', 'desc')->get();

        $this->assertEquals($point3->id, $points[0]->id);
        $this->assertEquals($point2->id, $points[1]->id);
        $this->assertEquals($point1->id, $points[2]->id);
    }

    // ==================== BALANCE TRACKING TESTS ====================

    public function test_balance_before_and_after_tracking(): void
    {
        $customerPoint = CustomerPoint::factory()->create([
            'customer_id' => $this->customer->id,
            'created_by' => $this->user->id,
            'balance_before' => 100,
            'balance_after' => 200,
            'points' => 100,
        ]);

        $this->assertEquals(100, $customerPoint->balance_before);
        $this->assertEquals(200, $customerPoint->balance_after);
        $this->assertEquals(100, $customerPoint->points);
    }

    public function test_negative_points_for_redemption(): void
    {
        $customerPoint = CustomerPoint::factory()->create([
            'customer_id' => $this->customer->id,
            'created_by' => $this->user->id,
            'type' => CustomerPoint::TYPE_REDEEMED,
            'balance_before' => 200,
            'balance_after' => 150,
            'points' => -50,
        ]);

        $this->assertEquals(-50, $customerPoint->points);
        $this->assertEquals(200, $customerPoint->balance_before);
        $this->assertEquals(150, $customerPoint->balance_after);
    }

    // ==================== POINT TYPE TESTS ====================

    public function test_earned_points_are_positive(): void
    {
        $customerPoint = CustomerPoint::factory()->earned(100)->create([
            'customer_id' => $this->customer->id,
            'created_by' => $this->user->id,
        ]);

        $this->assertGreaterThan(0, $customerPoint->points);
    }

    public function test_redeemed_points_are_negative(): void
    {
        $customerPoint = CustomerPoint::factory()->redeemed(50)->create([
            'customer_id' => $this->customer->id,
            'created_by' => $this->user->id,
        ]);

        $this->assertLessThan(0, $customerPoint->points);
    }

    public function test_expired_points_are_negative(): void
    {
        $customerPoint = CustomerPoint::factory()->expired(25)->create([
            'customer_id' => $this->customer->id,
            'created_by' => $this->user->id,
        ]);

        $this->assertLessThan(0, $customerPoint->points);
    }

    public function test_adjustment_points_can_be_positive_or_negative(): void
    {
        $positiveAdjustment = CustomerPoint::factory()->adjustment(50)->create([
            'customer_id' => $this->customer->id,
            'created_by' => $this->user->id,
        ]);

        $negativeAdjustment = CustomerPoint::factory()->adjustment(-30)->create([
            'customer_id' => $this->customer->id,
            'created_by' => $this->user->id,
        ]);

        $this->assertGreaterThan(0, $positiveAdjustment->points);
        $this->assertLessThan(0, $negativeAdjustment->points);
    }

    // ==================== COMBINED STATE TESTS ====================

    public function test_combined_factory_states(): void
    {
        $customerPoint = CustomerPoint::factory()
            ->earned(200)
            ->withDescription('Special promotion')
            ->withBalance(0, 200)
            ->create([
                'customer_id' => $this->customer->id,
                'created_by' => $this->user->id,
            ]);

        $this->assertEquals(CustomerPoint::TYPE_EARNED, $customerPoint->type);
        $this->assertEquals(200, $customerPoint->points);
        $this->assertEquals('Special promotion', $customerPoint->description);
        $this->assertEquals(0, $customerPoint->balance_before);
        $this->assertEquals(200, $customerPoint->balance_after);
    }

    // ==================== EDGE CASE TESTS ====================

    public function test_zero_points(): void
    {
        $customerPoint = CustomerPoint::factory()->create([
            'customer_id' => $this->customer->id,
            'created_by' => $this->user->id,
            'type' => CustomerPoint::TYPE_ADJUSTMENT,
            'points' => 0,
            'balance_before' => 100,
            'balance_after' => 100,
        ]);

        $this->assertEquals(0, $customerPoint->points);
    }

    public function test_very_large_points(): void
    {
        $customerPoint = CustomerPoint::factory()->create([
            'customer_id' => $this->customer->id,
            'created_by' => $this->user->id,
            'points' => 999999999.99,
            'balance_before' => 0,
            'balance_after' => 999999999.99,
        ]);

        $this->assertEquals(999999999.99, $customerPoint->points);
    }

    public function test_decimal_points(): void
    {
        $customerPoint = CustomerPoint::factory()->create([
            'customer_id' => $this->customer->id,
            'created_by' => $this->user->id,
            'points' => 123.45,
            'balance_before' => 100.50,
            'balance_after' => 223.95,
        ]);

        $this->assertEquals(123.45, $customerPoint->points);
        $this->assertEquals(100.50, $customerPoint->balance_before);
        $this->assertEquals(223.95, $customerPoint->balance_after);
    }
}
