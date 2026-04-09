<?php

namespace Tests\Unit\Models;

use App\Models\Customer;
use App\Models\Discount;
use App\Models\Outlet;
use App\Models\Product;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class DiscountTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tenant = Tenant::factory()->create();
    }

    // ==================== CREATION TESTS ====================

    public function test_can_create_discount(): void
    {
        $discount = Discount::factory()->create([
            'tenant_id' => $this->tenant->id,
            'code' => 'SAVE10',
            'name' => 'Save 10%',
            'type' => Discount::TYPE_PERCENTAGE,
            'value' => 10,
        ]);

        $this->assertDatabaseHas('discounts', [
            'id' => $discount->id,
            'code' => 'SAVE10',
            'name' => 'Save 10%',
            'type' => Discount::TYPE_PERCENTAGE,
        ]);
    }

    public function test_discount_has_required_attributes(): void
    {
        $discount = Discount::factory()->create(['tenant_id' => $this->tenant->id]);

        $this->assertNotNull($discount->id);
        $this->assertNotNull($discount->tenant_id);
        $this->assertNotNull($discount->code);
        $this->assertNotNull($discount->name);
        $this->assertNotNull($discount->type);
        $this->assertNotNull($discount->value);
    }

    public function test_discount_can_have_optional_attributes(): void
    {
        $discount = Discount::factory()->create([
            'tenant_id' => $this->tenant->id,
            'description' => 'Special promo discount',
            'max_discount' => 50000,
            'min_purchase' => 100000,
            'min_qty' => 2,
        ]);

        $this->assertEquals('Special promo discount', $discount->description);
        $this->assertEquals(50000, $discount->max_discount);
        $this->assertEquals(100000, $discount->min_purchase);
        $this->assertEquals(2, $discount->min_qty);
    }

    // ==================== CONSTANT TESTS ====================

    public function test_discount_type_constants(): void
    {
        $this->assertEquals('percentage', Discount::TYPE_PERCENTAGE);
        $this->assertEquals('fixed_amount', Discount::TYPE_FIXED_AMOUNT);
        $this->assertEquals('buy_x_get_y', Discount::TYPE_BUY_X_GET_Y);
    }

    public function test_discount_scope_constants(): void
    {
        $this->assertEquals('order', Discount::SCOPE_ORDER);
        $this->assertEquals('item', Discount::SCOPE_ITEM);
    }

    // ==================== RELATIONSHIP TESTS ====================

    public function test_discount_belongs_to_tenant(): void
    {
        $discount = Discount::factory()->create(['tenant_id' => $this->tenant->id]);

        $this->assertInstanceOf(Tenant::class, $discount->tenant);
        $this->assertEquals($this->tenant->id, $discount->tenant->id);
    }

    public function test_discount_has_many_transaction_discounts(): void
    {
        $discount = Discount::factory()->create(['tenant_id' => $this->tenant->id]);

        $this->assertInstanceOf(HasMany::class, $discount->transactionDiscounts());
    }

    // ==================== IS VALID TESTS ====================

    public function test_is_valid_returns_true_for_active_discount(): void
    {
        $discount = Discount::factory()->create([
            'tenant_id' => $this->tenant->id,
            'is_active' => true,
            'valid_from' => now()->subDays(1),
            'valid_until' => now()->addDays(30),
        ]);

        $this->assertTrue($discount->isValid());
    }

    public function test_is_valid_returns_false_for_inactive_discount(): void
    {
        $discount = Discount::factory()->inactive()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $this->assertFalse($discount->isValid());
    }

    public function test_is_valid_returns_false_for_expired_discount(): void
    {
        $discount = Discount::factory()->expired()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $this->assertFalse($discount->isValid());
    }

    public function test_is_valid_returns_false_for_not_yet_valid_discount(): void
    {
        $discount = Discount::factory()->notYetValid()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $this->assertFalse($discount->isValid());
    }

    public function test_is_valid_returns_false_for_exhausted_usage(): void
    {
        $discount = Discount::factory()->exhausted()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $this->assertFalse($discount->isValid());
    }

    public function test_is_valid_returns_true_for_no_usage_limit(): void
    {
        $discount = Discount::factory()->create([
            'tenant_id' => $this->tenant->id,
            'usage_limit' => null,
            'valid_from' => now()->subDays(1),
            'valid_until' => now()->addDays(30),
        ]);

        $this->assertTrue($discount->isValid());
    }

    public function test_is_valid_returns_true_when_usage_below_limit(): void
    {
        $discount = Discount::factory()->create([
            'tenant_id' => $this->tenant->id,
            'usage_limit' => 100,
            'usage_count' => 50,
        ]);

        $this->assertTrue($discount->isValid());
    }

    public function test_is_valid_with_null_valid_until(): void
    {
        $discount = Discount::factory()->create([
            'tenant_id' => $this->tenant->id,
            'is_active' => true,
            'valid_from' => now()->subDays(1),
            'valid_until' => null,
        ]);

        $this->assertTrue($discount->isValid());
    }

    // ==================== IS APPLICABLE TO OUTLET TESTS ====================

    public function test_is_applicable_to_outlet_returns_true_when_no_restrictions(): void
    {
        $discount = Discount::factory()->create([
            'tenant_id' => $this->tenant->id,
            'applicable_outlets' => null,
        ]);

        $this->assertTrue($discount->isApplicableToOutlet('any-outlet-id'));
    }

    public function test_is_applicable_to_outlet_returns_true_when_empty_array(): void
    {
        $discount = Discount::factory()->create([
            'tenant_id' => $this->tenant->id,
            'applicable_outlets' => [],
        ]);

        $this->assertTrue($discount->isApplicableToOutlet('any-outlet-id'));
    }

    public function test_is_applicable_to_outlet_returns_true_when_outlet_in_list(): void
    {
        $outlet = Outlet::factory()->create(['tenant_id' => $this->tenant->id]);
        $discount = Discount::factory()->create([
            'tenant_id' => $this->tenant->id,
            'applicable_outlets' => [$outlet->id],
        ]);

        $this->assertTrue($discount->isApplicableToOutlet($outlet->id));
    }

    public function test_is_applicable_to_outlet_returns_false_when_outlet_not_in_list(): void
    {
        $outlet1 = Outlet::factory()->create(['tenant_id' => $this->tenant->id]);
        $outlet2 = Outlet::factory()->create(['tenant_id' => $this->tenant->id]);
        $discount = Discount::factory()->create([
            'tenant_id' => $this->tenant->id,
            'applicable_outlets' => [$outlet1->id],
        ]);

        $this->assertFalse($discount->isApplicableToOutlet($outlet2->id));
    }

    public function test_is_applicable_to_outlet_with_multiple_outlets(): void
    {
        $outlet1 = Outlet::factory()->create(['tenant_id' => $this->tenant->id]);
        $outlet2 = Outlet::factory()->create(['tenant_id' => $this->tenant->id]);
        $outlet3 = Outlet::factory()->create(['tenant_id' => $this->tenant->id]);

        $discount = Discount::factory()->create([
            'tenant_id' => $this->tenant->id,
            'applicable_outlets' => [$outlet1->id, $outlet2->id],
        ]);

        $this->assertTrue($discount->isApplicableToOutlet($outlet1->id));
        $this->assertTrue($discount->isApplicableToOutlet($outlet2->id));
        $this->assertFalse($discount->isApplicableToOutlet($outlet3->id));
    }

    // ==================== IS APPLICABLE TO ITEM TESTS ====================

    public function test_is_applicable_to_item_returns_true_when_no_restrictions(): void
    {
        $discount = Discount::factory()->create([
            'tenant_id' => $this->tenant->id,
            'applicable_items' => null,
        ]);

        $this->assertTrue($discount->isApplicableToItem('any-item-id'));
    }

    public function test_is_applicable_to_item_returns_true_when_empty_array(): void
    {
        $discount = Discount::factory()->create([
            'tenant_id' => $this->tenant->id,
            'applicable_items' => [],
        ]);

        $this->assertTrue($discount->isApplicableToItem('any-item-id'));
    }

    public function test_is_applicable_to_item_returns_true_when_item_in_list(): void
    {
        $product = Product::factory()->create(['tenant_id' => $this->tenant->id]);
        $discount = Discount::factory()->create([
            'tenant_id' => $this->tenant->id,
            'applicable_items' => [$product->id],
        ]);

        $this->assertTrue($discount->isApplicableToItem($product->id));
    }

    public function test_is_applicable_to_item_returns_false_when_item_not_in_list(): void
    {
        $product1 = Product::factory()->create(['tenant_id' => $this->tenant->id]);
        $product2 = Product::factory()->create(['tenant_id' => $this->tenant->id]);
        $discount = Discount::factory()->create([
            'tenant_id' => $this->tenant->id,
            'applicable_items' => [$product1->id],
        ]);

        $this->assertFalse($discount->isApplicableToItem($product2->id));
    }

    // ==================== IS APPLICABLE TO MEMBER TESTS ====================

    public function test_is_applicable_to_member_returns_true_when_not_member_only(): void
    {
        $discount = Discount::factory()->create([
            'tenant_id' => $this->tenant->id,
            'member_only' => false,
        ]);

        $this->assertTrue($discount->isApplicableToMember(null));
    }

    public function test_is_applicable_to_member_returns_false_for_member_only_without_customer(): void
    {
        $discount = Discount::factory()->memberOnly()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $this->assertFalse($discount->isApplicableToMember(null));
    }

    public function test_is_applicable_to_member_returns_true_for_member_customer(): void
    {
        $customer = Customer::factory()->silver()->create(['tenant_id' => $this->tenant->id]);
        $discount = Discount::factory()->memberOnly()->create([
            'tenant_id' => $this->tenant->id,
            'membership_levels' => null,
        ]);

        $this->assertTrue($discount->isApplicableToMember($customer));
    }

    public function test_is_applicable_to_member_returns_false_for_regular_customer(): void
    {
        $customer = Customer::factory()->create([
            'tenant_id' => $this->tenant->id,
            'membership_level' => Customer::LEVEL_REGULAR,
        ]);
        $discount = Discount::factory()->memberOnly()->create([
            'tenant_id' => $this->tenant->id,
            'membership_levels' => null,
        ]);

        $this->assertFalse($discount->isApplicableToMember($customer));
    }

    public function test_is_applicable_to_member_with_specific_levels(): void
    {
        $silverCustomer = Customer::factory()->silver()->create(['tenant_id' => $this->tenant->id]);
        $goldCustomer = Customer::factory()->gold()->create(['tenant_id' => $this->tenant->id]);
        $platinumCustomer = Customer::factory()->platinum()->create(['tenant_id' => $this->tenant->id]);

        $discount = Discount::factory()->memberOnly()->create([
            'tenant_id' => $this->tenant->id,
            'membership_levels' => [Customer::LEVEL_GOLD, Customer::LEVEL_PLATINUM],
        ]);

        $this->assertFalse($discount->isApplicableToMember($silverCustomer));
        $this->assertTrue($discount->isApplicableToMember($goldCustomer));
        $this->assertTrue($discount->isApplicableToMember($platinumCustomer));
    }

    // ==================== CALCULATE DISCOUNT TESTS ====================

    public function test_calculate_discount_percentage(): void
    {
        $discount = Discount::factory()->percentage(10)->create([
            'tenant_id' => $this->tenant->id,
            'min_purchase' => null,
            'max_discount' => null,
        ]);

        $this->assertEquals(10000, $discount->calculateDiscount(100000));
    }

    public function test_calculate_discount_fixed_amount(): void
    {
        $discount = Discount::factory()->fixedAmount(15000)->create([
            'tenant_id' => $this->tenant->id,
            'min_purchase' => null,
        ]);

        $this->assertEquals(15000, $discount->calculateDiscount(100000));
    }

    public function test_calculate_discount_returns_zero_below_min_purchase(): void
    {
        $discount = Discount::factory()->percentage(10)->withMinPurchase(100000)->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $this->assertEquals(0, $discount->calculateDiscount(50000));
    }

    public function test_calculate_discount_works_at_min_purchase(): void
    {
        $discount = Discount::factory()->percentage(10)->create([
            'tenant_id' => $this->tenant->id,
            'min_purchase' => 100000,
            'max_discount' => null,
        ]);

        $this->assertEquals(10000, $discount->calculateDiscount(100000));
    }

    public function test_calculate_discount_returns_zero_below_min_qty(): void
    {
        $discount = Discount::factory()->percentage(10)->create([
            'tenant_id' => $this->tenant->id,
            'min_qty' => 3,
            'min_purchase' => null,
        ]);

        $this->assertEquals(0, $discount->calculateDiscount(100000, 2));
    }

    public function test_calculate_discount_works_at_min_qty(): void
    {
        $discount = Discount::factory()->percentage(10)->create([
            'tenant_id' => $this->tenant->id,
            'min_qty' => 3,
            'min_purchase' => null,
            'max_discount' => null,
        ]);

        $this->assertEquals(10000, $discount->calculateDiscount(100000, 3));
    }

    public function test_calculate_discount_respects_max_discount(): void
    {
        $discount = Discount::factory()->percentage(50)->withMaxDiscount(25000)->create([
            'tenant_id' => $this->tenant->id,
            'min_purchase' => null,
        ]);

        // 50% of 100000 = 50000, but max is 25000
        $this->assertEquals(25000, $discount->calculateDiscount(100000));
    }

    public function test_calculate_discount_percentage_without_max(): void
    {
        $discount = Discount::factory()->percentage(50)->create([
            'tenant_id' => $this->tenant->id,
            'min_purchase' => null,
            'max_discount' => null,
        ]);

        $this->assertEquals(50000, $discount->calculateDiscount(100000));
    }

    public function test_calculate_discount_cannot_exceed_subtotal(): void
    {
        $discount = Discount::factory()->fixedAmount(50000)->create([
            'tenant_id' => $this->tenant->id,
            'min_purchase' => null,
        ]);

        // Fixed 50000 but subtotal only 30000
        $this->assertEquals(30000, $discount->calculateDiscount(30000));
    }

    public function test_calculate_discount_100_percent(): void
    {
        $discount = Discount::factory()->percentage(100)->create([
            'tenant_id' => $this->tenant->id,
            'min_purchase' => null,
            'max_discount' => null,
        ]);

        $this->assertEquals(50000, $discount->calculateDiscount(50000));
    }

    // ==================== INCREMENT USAGE TESTS ====================

    public function test_increment_usage_increases_count(): void
    {
        $discount = Discount::factory()->create([
            'tenant_id' => $this->tenant->id,
            'usage_count' => 5,
        ]);

        $discount->incrementUsage();
        $discount->refresh();

        $this->assertEquals(6, $discount->usage_count);
    }

    public function test_increment_usage_multiple_times(): void
    {
        $discount = Discount::factory()->create([
            'tenant_id' => $this->tenant->id,
            'usage_count' => 0,
        ]);

        $discount->incrementUsage();
        $discount->incrementUsage();
        $discount->incrementUsage();
        $discount->refresh();

        $this->assertEquals(3, $discount->usage_count);
    }

    // ==================== STATIC METHODS TESTS ====================

    public function test_get_types_returns_all_types(): void
    {
        $types = Discount::getTypes();

        $this->assertIsArray($types);
        $this->assertArrayHasKey(Discount::TYPE_PERCENTAGE, $types);
        $this->assertArrayHasKey(Discount::TYPE_FIXED_AMOUNT, $types);
        $this->assertArrayHasKey(Discount::TYPE_BUY_X_GET_Y, $types);
        $this->assertEquals('Percentage', $types[Discount::TYPE_PERCENTAGE]);
        $this->assertEquals('Fixed Amount', $types[Discount::TYPE_FIXED_AMOUNT]);
        $this->assertEquals('Buy X Get Y', $types[Discount::TYPE_BUY_X_GET_Y]);
    }

    public function test_get_scopes_returns_all_scopes(): void
    {
        $scopes = Discount::getScopes();

        $this->assertIsArray($scopes);
        $this->assertArrayHasKey(Discount::SCOPE_ORDER, $scopes);
        $this->assertArrayHasKey(Discount::SCOPE_ITEM, $scopes);
        $this->assertEquals('Order Level', $scopes[Discount::SCOPE_ORDER]);
        $this->assertEquals('Item Level', $scopes[Discount::SCOPE_ITEM]);
    }

    // ==================== CASTING TESTS ====================

    public function test_value_is_cast_to_decimal(): void
    {
        $discount = Discount::factory()->create([
            'tenant_id' => $this->tenant->id,
            'value' => 10.50,
        ]);

        $this->assertEquals(10.50, $discount->value);
    }

    public function test_member_only_is_cast_to_boolean(): void
    {
        $discount = Discount::factory()->create([
            'tenant_id' => $this->tenant->id,
            'member_only' => 1,
        ]);

        $this->assertIsBool($discount->member_only);
        $this->assertTrue($discount->member_only);
    }

    public function test_is_active_is_cast_to_boolean(): void
    {
        $discount = Discount::factory()->create([
            'tenant_id' => $this->tenant->id,
            'is_active' => 1,
        ]);

        $this->assertIsBool($discount->is_active);
        $this->assertTrue($discount->is_active);
    }

    public function test_is_auto_apply_is_cast_to_boolean(): void
    {
        $discount = Discount::factory()->autoApply()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $this->assertIsBool($discount->is_auto_apply);
        $this->assertTrue($discount->is_auto_apply);
    }

    public function test_membership_levels_is_cast_to_array(): void
    {
        $discount = Discount::factory()->create([
            'tenant_id' => $this->tenant->id,
            'membership_levels' => [Customer::LEVEL_GOLD, Customer::LEVEL_PLATINUM],
        ]);

        $this->assertIsArray($discount->membership_levels);
        $this->assertContains(Customer::LEVEL_GOLD, $discount->membership_levels);
        $this->assertContains(Customer::LEVEL_PLATINUM, $discount->membership_levels);
    }

    public function test_applicable_outlets_is_cast_to_array(): void
    {
        $outlet = Outlet::factory()->create(['tenant_id' => $this->tenant->id]);
        $discount = Discount::factory()->create([
            'tenant_id' => $this->tenant->id,
            'applicable_outlets' => [$outlet->id],
        ]);

        $this->assertIsArray($discount->applicable_outlets);
    }

    public function test_applicable_items_is_cast_to_array(): void
    {
        $product = Product::factory()->create(['tenant_id' => $this->tenant->id]);
        $discount = Discount::factory()->create([
            'tenant_id' => $this->tenant->id,
            'applicable_items' => [$product->id],
        ]);

        $this->assertIsArray($discount->applicable_items);
    }

    public function test_valid_from_is_cast_to_date(): void
    {
        $discount = Discount::factory()->create(['tenant_id' => $this->tenant->id]);

        $this->assertInstanceOf(Carbon::class, $discount->valid_from);
    }

    public function test_valid_until_is_cast_to_date(): void
    {
        $discount = Discount::factory()->create(['tenant_id' => $this->tenant->id]);

        $this->assertInstanceOf(Carbon::class, $discount->valid_until);
    }

    // ==================== FACTORY STATE TESTS ====================

    public function test_factory_percentage_state(): void
    {
        $discount = Discount::factory()->percentage(15)->create(['tenant_id' => $this->tenant->id]);

        $this->assertEquals(Discount::TYPE_PERCENTAGE, $discount->type);
        $this->assertEquals(15, $discount->value);
    }

    public function test_factory_fixed_amount_state(): void
    {
        $discount = Discount::factory()->fixedAmount(25000)->create(['tenant_id' => $this->tenant->id]);

        $this->assertEquals(Discount::TYPE_FIXED_AMOUNT, $discount->type);
        $this->assertEquals(25000, $discount->value);
    }

    public function test_factory_order_level_state(): void
    {
        $discount = Discount::factory()->orderLevel()->create(['tenant_id' => $this->tenant->id]);

        $this->assertEquals(Discount::SCOPE_ORDER, $discount->scope);
    }

    public function test_factory_item_level_state(): void
    {
        $discount = Discount::factory()->itemLevel()->create(['tenant_id' => $this->tenant->id]);

        $this->assertEquals(Discount::SCOPE_ITEM, $discount->scope);
    }

    public function test_factory_member_only_state(): void
    {
        $discount = Discount::factory()->memberOnly()->create(['tenant_id' => $this->tenant->id]);

        $this->assertTrue($discount->member_only);
    }

    public function test_factory_auto_apply_state(): void
    {
        $discount = Discount::factory()->autoApply()->create(['tenant_id' => $this->tenant->id]);

        $this->assertTrue($discount->is_auto_apply);
    }

    public function test_factory_inactive_state(): void
    {
        $discount = Discount::factory()->inactive()->create(['tenant_id' => $this->tenant->id]);

        $this->assertFalse($discount->is_active);
    }

    public function test_factory_expired_state(): void
    {
        $discount = Discount::factory()->expired()->create(['tenant_id' => $this->tenant->id]);

        $this->assertTrue($discount->valid_until->isPast());
    }

    public function test_factory_not_yet_valid_state(): void
    {
        $discount = Discount::factory()->notYetValid()->create(['tenant_id' => $this->tenant->id]);

        $this->assertTrue($discount->valid_from->isFuture());
    }

    public function test_factory_no_usage_limit(): void
    {
        $discount = Discount::factory()->create([
            'tenant_id' => $this->tenant->id,
            'usage_limit' => null,
        ]);

        $this->assertNull($discount->usage_limit);
    }

    public function test_factory_with_usage_limit_state(): void
    {
        $discount = Discount::factory()->withUsageLimit(50)->create(['tenant_id' => $this->tenant->id]);

        $this->assertEquals(50, $discount->usage_limit);
        $this->assertEquals(0, $discount->usage_count);
    }

    public function test_factory_exhausted_state(): void
    {
        $discount = Discount::factory()->exhausted()->create(['tenant_id' => $this->tenant->id]);

        $this->assertEquals($discount->usage_limit, $discount->usage_count);
    }

    public function test_factory_with_min_purchase_state(): void
    {
        $discount = Discount::factory()->withMinPurchase(200000)->create(['tenant_id' => $this->tenant->id]);

        $this->assertEquals(200000, $discount->min_purchase);
    }

    public function test_factory_with_max_discount_state(): void
    {
        $discount = Discount::factory()->withMaxDiscount(50000)->create(['tenant_id' => $this->tenant->id]);

        $this->assertEquals(50000, $discount->max_discount);
    }

    // ==================== UUID TRAIT TESTS ====================

    public function test_discount_uses_uuid(): void
    {
        $discount = Discount::factory()->create(['tenant_id' => $this->tenant->id]);

        $this->assertNotNull($discount->id);
        $this->assertIsString($discount->id);
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/',
            $discount->id
        );
    }

    // ==================== TENANT ISOLATION TESTS ====================

    public function test_discounts_are_tenant_isolated(): void
    {
        $tenant1 = Tenant::factory()->create();
        $tenant2 = Tenant::factory()->create();

        Discount::factory()->count(3)->create(['tenant_id' => $tenant1->id]);
        Discount::factory()->count(2)->create(['tenant_id' => $tenant2->id]);

        $tenant1Discounts = Discount::where('tenant_id', $tenant1->id)->get();
        $tenant2Discounts = Discount::where('tenant_id', $tenant2->id)->get();

        $this->assertCount(3, $tenant1Discounts);
        $this->assertCount(2, $tenant2Discounts);
    }

    // ==================== COMBINED STATE TESTS ====================

    public function test_combined_factory_states(): void
    {
        $discount = Discount::factory()
            ->percentage(20)
            ->orderLevel()
            ->memberOnly()
            ->withMinPurchase(150000)
            ->withMaxDiscount(30000)
            ->autoApply()
            ->create(['tenant_id' => $this->tenant->id]);

        $this->assertEquals(Discount::TYPE_PERCENTAGE, $discount->type);
        $this->assertEquals(20, $discount->value);
        $this->assertEquals(Discount::SCOPE_ORDER, $discount->scope);
        $this->assertTrue($discount->member_only);
        $this->assertEquals(150000, $discount->min_purchase);
        $this->assertEquals(30000, $discount->max_discount);
        $this->assertTrue($discount->is_auto_apply);
    }

    // ==================== EDGE CASE TESTS ====================

    public function test_calculate_discount_with_zero_subtotal(): void
    {
        $discount = Discount::factory()->percentage(10)->create([
            'tenant_id' => $this->tenant->id,
            'min_purchase' => null,
        ]);

        $this->assertEquals(0, $discount->calculateDiscount(0));
    }

    public function test_is_valid_on_last_day(): void
    {
        $discount = Discount::factory()->create([
            'tenant_id' => $this->tenant->id,
            'is_active' => true,
            'valid_from' => now()->subDays(30),
            'valid_until' => now()->endOfDay(),
        ]);

        $this->assertTrue($discount->isValid());
    }

    public function test_is_valid_on_first_day(): void
    {
        $discount = Discount::factory()->create([
            'tenant_id' => $this->tenant->id,
            'is_active' => true,
            'valid_from' => now()->startOfDay(),
            'valid_until' => now()->addDays(30),
        ]);

        $this->assertTrue($discount->isValid());
    }

    public function test_calculate_discount_with_very_small_percentage(): void
    {
        $discount = Discount::factory()->percentage(0.5)->create([
            'tenant_id' => $this->tenant->id,
            'min_purchase' => null,
            'max_discount' => null,
        ]);

        // 0.5% of 100000 = 500
        $this->assertEquals(500, $discount->calculateDiscount(100000));
    }
}
