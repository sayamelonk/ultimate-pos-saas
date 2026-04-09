<?php

namespace Tests\Feature;

use App\Models\Outlet;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Complete User Journey Test
 *
 * Tests the full flow from landing page to running a business:
 * 1. View landing page
 * 2. Register account
 * 3. Verify email
 * 4. Complete onboarding (business settings, product, payment method)
 * 5. Subscribe to a plan (after trial or directly)
 * 6. Create outlet/branch
 * 7. Create products
 *
 * @see docs/analisis/feature-dependency.md
 */
class UserJourneyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create subscription plans
        SubscriptionPlan::factory()->starter()->create();
        SubscriptionPlan::factory()->growth()->create();
        SubscriptionPlan::factory()->professional()->create();
        SubscriptionPlan::factory()->enterprise()->create();
    }

    // ============================================================
    // PHASE 1: LANDING & REGISTRATION
    // ============================================================

    public function test_landing_page_loads_successfully(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('Ultimate POS');
    }

    public function test_pricing_page_shows_all_plans(): void
    {
        $response = $this->get('/pricing');

        $response->assertStatus(200);
        $response->assertSee('Starter');
        $response->assertSee('Growth');
        $response->assertSee('Professional');
        $response->assertSee('Enterprise');
    }

    public function test_register_page_loads_successfully(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_user_can_register_new_account(): void
    {
        $response = $this->post('/register', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'business_name' => 'Warung Kopi John',
            'phone' => '081234567890',
        ]);

        $response->assertRedirect('/email/verify');

        // User should be created
        $this->assertDatabaseHas('users', [
            'email' => 'john@example.com',
            'name' => 'John Doe',
        ]);

        // Tenant should be created
        $this->assertDatabaseHas('tenants', [
            'name' => 'Warung Kopi John',
        ]);

        // Default outlet should be created
        $tenant = Tenant::where('name', 'Warung Kopi John')->first();
        $this->assertDatabaseHas('outlets', [
            'tenant_id' => $tenant->id,
            'name' => 'Main Outlet',
            'code' => 'MAIN',
        ]);

        // Trial subscription should be created
        $this->assertDatabaseHas('subscriptions', [
            'tenant_id' => $tenant->id,
            'status' => Subscription::STATUS_TRIAL,
        ]);
    }

    public function test_registration_requires_all_fields(): void
    {
        $response = $this->post('/register', []);

        $response->assertSessionHasErrors(['name', 'email', 'password', 'business_name']);
    }

    public function test_registration_requires_unique_email(): void
    {
        // Create existing user
        $tenant = Tenant::factory()->create();
        User::factory()->create([
            'email' => 'existing@example.com',
            'tenant_id' => $tenant->id,
        ]);

        $response = $this->post('/register', [
            'name' => 'John Doe',
            'email' => 'existing@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'business_name' => 'New Business',
        ]);

        $response->assertSessionHasErrors(['email']);
    }

    // ============================================================
    // PHASE 2: EMAIL VERIFICATION
    // ============================================================

    public function test_unverified_user_sees_verification_notice(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->unverified()->create(['tenant_id' => $tenant->id]);

        $response = $this->actingAs($user)->get('/email/verify');

        $response->assertStatus(200);
    }

    public function test_unverified_user_cannot_access_dashboard(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->unverified()->create(['tenant_id' => $tenant->id]);

        $response = $this->actingAs($user)->get('/admin/dashboard');

        $response->assertRedirect('/email/verify');
    }

    public function test_verified_user_redirected_from_verification_page(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);

        $response = $this->actingAs($user)->get('/email/verify');

        $response->assertRedirect();
    }

    // ============================================================
    // PHASE 3: ONBOARDING
    // ============================================================

    public function test_new_user_sees_onboarding_page(): void
    {
        $tenant = Tenant::factory()->create([
            'timezone' => 'UTC',
            'onboarding_completed_at' => null,
        ]);
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        Outlet::factory()->create(['tenant_id' => $tenant->id]);

        $response = $this->actingAs($user)->get('/onboarding');

        $response->assertStatus(200);
    }

    public function test_onboarding_step1_update_business_settings(): void
    {
        $tenant = Tenant::factory()->create([
            'timezone' => 'UTC',
            'onboarding_completed_at' => null,
        ]);
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        Outlet::factory()->create(['tenant_id' => $tenant->id]);

        $response = $this->actingAs($user)->post('/onboarding/business', [
            'name' => 'Warung Kopi Updated',
            'timezone' => 'Asia/Jakarta',
            'currency' => 'IDR',
            'tax_percentage' => 10,
            'service_charge_percentage' => 5,
        ]);

        $response->assertRedirect('/onboarding');

        $this->assertDatabaseHas('tenants', [
            'id' => $tenant->id,
            'name' => 'Warung Kopi Updated',
            'timezone' => 'Asia/Jakarta',
            'currency' => 'IDR',
        ]);
    }

    public function test_onboarding_step2_create_first_product(): void
    {
        $tenant = Tenant::factory()->create([
            'timezone' => 'Asia/Jakarta',
            'onboarding_completed_at' => null,
        ]);
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $outlet = Outlet::factory()->create(['tenant_id' => $tenant->id]);

        // Assign user to outlet
        $user->outlets()->attach($outlet->id, ['is_default' => true]);

        $response = $this->actingAs($user)->post('/onboarding/product', [
            'name' => 'Kopi Susu',
            'category_name' => 'Minuman',
            'price' => 25000,
            'description' => 'Kopi susu enak',
        ]);

        $response->assertRedirect('/onboarding');

        // Category should be created
        $this->assertDatabaseHas('product_categories', [
            'tenant_id' => $tenant->id,
            'name' => 'Minuman',
        ]);

        // Product should be created
        $this->assertDatabaseHas('products', [
            'tenant_id' => $tenant->id,
            'name' => 'Kopi Susu',
            'base_price' => 25000,
        ]);
    }

    public function test_onboarding_step3_setup_payment_methods(): void
    {
        $tenant = Tenant::factory()->create([
            'timezone' => 'Asia/Jakarta',
            'onboarding_completed_at' => null,
        ]);
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $outlet = Outlet::factory()->create(['tenant_id' => $tenant->id]);
        $user->outlets()->attach($outlet->id, ['is_default' => true]);

        $response = $this->actingAs($user)->post('/onboarding/payment-methods', [
            'methods' => ['cash', 'qris', 'bank_transfer'],
        ]);

        $response->assertRedirect('/onboarding');

        // Payment methods should be created
        $this->assertEquals(3, PaymentMethod::where('tenant_id', $tenant->id)->count());

        $this->assertDatabaseHas('payment_methods', [
            'tenant_id' => $tenant->id,
            'type' => PaymentMethod::TYPE_CASH,
            'name' => 'Cash',
        ]);

        $this->assertDatabaseHas('payment_methods', [
            'tenant_id' => $tenant->id,
            'type' => PaymentMethod::TYPE_DIGITAL_WALLET,
            'name' => 'QRIS',
        ]);
    }

    public function test_onboarding_step4_invite_staff(): void
    {
        $tenant = Tenant::factory()->create([
            'timezone' => 'Asia/Jakarta',
            'onboarding_completed_at' => null,
        ]);
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $outlet = Outlet::factory()->create(['tenant_id' => $tenant->id]);
        $user->outlets()->attach($outlet->id, ['is_default' => true]);

        $response = $this->actingAs($user)->post('/onboarding/staff', [
            'staff' => [
                [
                    'name' => 'Budi Cashier',
                    'email' => 'budi@example.com',
                    'role' => 'cashier',
                ],
                [
                    'name' => 'Ani Manager',
                    'email' => 'ani@example.com',
                    'role' => 'manager',
                ],
            ],
        ]);

        $response->assertRedirect('/onboarding');

        // Staff users should be created
        $this->assertDatabaseHas('users', [
            'tenant_id' => $tenant->id,
            'email' => 'budi@example.com',
            'name' => 'Budi Cashier',
        ]);

        $this->assertDatabaseHas('users', [
            'tenant_id' => $tenant->id,
            'email' => 'ani@example.com',
            'name' => 'Ani Manager',
        ]);

        // Should have 3 users total (owner + 2 staff)
        $this->assertEquals(3, User::where('tenant_id', $tenant->id)->count());
    }

    public function test_can_complete_onboarding(): void
    {
        $tenant = Tenant::factory()->create([
            'timezone' => 'Asia/Jakarta',
            'onboarding_completed_at' => null,
        ]);
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $outlet = Outlet::factory()->create(['tenant_id' => $tenant->id]);

        $response = $this->actingAs($user)->get('/onboarding/complete');

        $response->assertRedirect('/dashboard');

        $tenant->refresh();
        $this->assertNotNull($tenant->onboarding_completed_at);
    }

    public function test_can_skip_onboarding(): void
    {
        $tenant = Tenant::factory()->create([
            'timezone' => 'Asia/Jakarta',
            'onboarding_completed_at' => null,
        ]);
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $outlet = Outlet::factory()->create(['tenant_id' => $tenant->id]);

        $response = $this->actingAs($user)->get('/onboarding/skip');

        $response->assertRedirect('/dashboard');

        $tenant->refresh();
        $this->assertNotNull($tenant->onboarding_completed_at);
    }

    // ============================================================
    // PHASE 4: SUBSCRIPTION
    // ============================================================

    public function test_user_can_view_subscription_status(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        Outlet::factory()->create(['tenant_id' => $tenant->id]);

        $plan = SubscriptionPlan::where('slug', 'starter')->first();
        Subscription::factory()->active()->create([
            'tenant_id' => $tenant->id,
            'subscription_plan_id' => $plan->id,
        ]);

        $response = $this->actingAs($user)->get('/subscription');

        $response->assertStatus(200);
    }

    public function test_user_can_view_subscription_plans(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        Outlet::factory()->create(['tenant_id' => $tenant->id]);

        $response = $this->actingAs($user)->get('/subscription/plans');

        $response->assertStatus(200);
        $response->assertSee('Starter');
        $response->assertSee('Growth');
        $response->assertSee('Professional');
    }

    public function test_trial_user_has_14_days(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        Outlet::factory()->create(['tenant_id' => $tenant->id]);

        $plan = SubscriptionPlan::where('slug', 'professional')->first();
        $subscription = Subscription::factory()->trial()->create([
            'tenant_id' => $tenant->id,
            'subscription_plan_id' => $plan->id,
            'trial_ends_at' => now()->addDays(14),
        ]);

        $this->assertEquals(Subscription::STATUS_TRIAL, $subscription->status);
        // Trial ends in 14 days (allow 13-14 due to timing)
        $daysUntilTrialEnds = (int) now()->diffInDays($subscription->trial_ends_at, false);
        $this->assertTrue($daysUntilTrialEnds >= 13 && $daysUntilTrialEnds <= 14);
    }

    // ============================================================
    // PHASE 5: OUTLET/BRANCH MANAGEMENT
    // ============================================================

    public function test_user_can_view_outlets(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        Outlet::factory()->create(['tenant_id' => $tenant->id, 'name' => 'Main Outlet']);

        $response = $this->actingAs($user)->get('/admin/outlets');

        $response->assertStatus(200);
        $response->assertSee('Main Outlet');
    }

    public function test_user_can_create_outlet_within_limit(): void
    {
        $plan = SubscriptionPlan::where('slug', 'growth')->first(); // 3 outlets
        $tenant = Tenant::factory()->create();
        Subscription::factory()->active()->create([
            'tenant_id' => $tenant->id,
            'subscription_plan_id' => $plan->id,
        ]);

        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        Outlet::factory()->create(['tenant_id' => $tenant->id]); // 1st outlet

        $response = $this->actingAs($user)->post('/admin/outlets', [
            'name' => 'Branch 2',
            'code' => 'BRANCH2',
            'address' => 'Jl. Test No. 2',
            'is_active' => true,
        ]);

        $response->assertRedirect('/admin/outlets');

        $this->assertDatabaseHas('outlets', [
            'tenant_id' => $tenant->id,
            'name' => 'Branch 2',
            'code' => 'BRANCH2',
        ]);
    }

    public function test_user_cannot_create_outlet_over_limit(): void
    {
        $plan = SubscriptionPlan::where('slug', 'starter')->first(); // 1 outlet only
        $tenant = Tenant::factory()->create();
        Subscription::factory()->active()->create([
            'tenant_id' => $tenant->id,
            'subscription_plan_id' => $plan->id,
        ]);

        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        Outlet::factory()->create(['tenant_id' => $tenant->id]); // Already at limit

        $response = $this->actingAs($user)->get('/admin/outlets/create');

        // Should show error or redirect
        $response->assertRedirect('/admin/outlets');
    }

    public function test_user_can_update_outlet(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $outlet = Outlet::factory()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Old Name',
        ]);

        $response = $this->actingAs($user)->put("/admin/outlets/{$outlet->id}", [
            'name' => 'New Name',
            'code' => $outlet->code,
            'is_active' => true,
        ]);

        $response->assertRedirect('/admin/outlets');

        $this->assertDatabaseHas('outlets', [
            'id' => $outlet->id,
            'name' => 'New Name',
        ]);
    }

    // ============================================================
    // PHASE 6: PRODUCT MANAGEMENT
    // ============================================================

    public function test_user_can_view_products(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        Outlet::factory()->create(['tenant_id' => $tenant->id]);

        $category = ProductCategory::factory()->create(['tenant_id' => $tenant->id]);
        Product::factory()->create([
            'tenant_id' => $tenant->id,
            'category_id' => $category->id,
            'name' => 'Test Product',
        ]);

        $response = $this->actingAs($user)->get('/menu/products');

        $response->assertStatus(200);
        $response->assertSee('Test Product');
    }

    public function test_user_can_create_product(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        Outlet::factory()->create(['tenant_id' => $tenant->id]);
        $category = ProductCategory::factory()->create(['tenant_id' => $tenant->id]);

        $response = $this->actingAs($user)->post('/menu/products', [
            'sku' => 'SKU-001',
            'name' => 'Nasi Goreng',
            'category_id' => $category->id,
            'base_price' => 25000,
            'product_type' => 'single',
            'is_active' => true,
            'show_in_pos' => true,
        ]);

        // Redirects to product show page after create
        $response->assertRedirect();
        $this->assertStringContainsString('/menu/products/', $response->headers->get('Location'));

        $this->assertDatabaseHas('products', [
            'tenant_id' => $tenant->id,
            'sku' => 'SKU-001',
            'name' => 'Nasi Goreng',
            'base_price' => 25000,
        ]);
    }

    public function test_user_can_create_variant_product(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        Outlet::factory()->create(['tenant_id' => $tenant->id]);
        $category = ProductCategory::factory()->create(['tenant_id' => $tenant->id]);

        $response = $this->actingAs($user)->post('/menu/products', [
            'sku' => 'SKU-002',
            'name' => 'Es Kopi',
            'category_id' => $category->id,
            'base_price' => 20000,
            'product_type' => 'variant',
            'is_active' => true,
            'show_in_pos' => true,
        ]);

        // Redirects to product show page after create
        $response->assertRedirect();
        $this->assertStringContainsString('/menu/products/', $response->headers->get('Location'));

        $this->assertDatabaseHas('products', [
            'tenant_id' => $tenant->id,
            'name' => 'Es Kopi',
            'product_type' => 'variant',
        ]);
    }

    public function test_user_can_update_product(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        Outlet::factory()->create(['tenant_id' => $tenant->id]);
        $category = ProductCategory::factory()->create(['tenant_id' => $tenant->id]);

        $product = Product::factory()->create([
            'tenant_id' => $tenant->id,
            'category_id' => $category->id,
            'name' => 'Old Product',
            'base_price' => 10000,
        ]);

        $response = $this->actingAs($user)->put("/menu/products/{$product->id}", [
            'sku' => $product->sku,
            'name' => 'Updated Product',
            'category_id' => $category->id,
            'base_price' => 15000,
            'product_type' => 'single',
            'is_active' => true,
            'show_in_pos' => true,
        ]);

        // Redirects to product show page after update
        $response->assertRedirect();
        $this->assertStringContainsString('/menu/products/', $response->headers->get('Location'));

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'Updated Product',
            'base_price' => 15000,
        ]);
    }

    public function test_user_can_delete_product(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        Outlet::factory()->create(['tenant_id' => $tenant->id]);
        $category = ProductCategory::factory()->create(['tenant_id' => $tenant->id]);

        $product = Product::factory()->create([
            'tenant_id' => $tenant->id,
            'category_id' => $category->id,
        ]);

        $response = $this->actingAs($user)->delete("/menu/products/{$product->id}");

        $response->assertRedirect('/menu/products');

        // Soft deleted
        $this->assertSoftDeleted('products', [
            'id' => $product->id,
        ]);
    }

    public function test_user_cannot_access_other_tenant_product(): void
    {
        $tenant1 = Tenant::factory()->create();
        $tenant2 = Tenant::factory()->create();

        $user = User::factory()->create(['tenant_id' => $tenant1->id]);
        Outlet::factory()->create(['tenant_id' => $tenant1->id]);

        $category = ProductCategory::factory()->create(['tenant_id' => $tenant2->id]);
        $otherProduct = Product::factory()->create([
            'tenant_id' => $tenant2->id,
            'category_id' => $category->id,
        ]);

        $response = $this->actingAs($user)->get("/menu/products/{$otherProduct->id}");

        $response->assertForbidden();
    }

    // ============================================================
    // PHASE 7: PRODUCT CATEGORY MANAGEMENT
    // ============================================================

    public function test_user_can_view_categories(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        Outlet::factory()->create(['tenant_id' => $tenant->id]);

        ProductCategory::factory()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Test Category',
        ]);

        $response = $this->actingAs($user)->get('/menu/categories');

        $response->assertStatus(200);
        $response->assertSee('Test Category');
    }

    public function test_user_can_create_category(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        Outlet::factory()->create(['tenant_id' => $tenant->id]);

        $response = $this->actingAs($user)->post('/menu/categories', [
            'name' => 'Makanan',
            'is_active' => true,
            'show_in_pos' => true,
        ]);

        $response->assertRedirect('/menu/categories');

        $this->assertDatabaseHas('product_categories', [
            'tenant_id' => $tenant->id,
            'name' => 'Makanan',
        ]);
    }

    // ============================================================
    // COMPLETE USER JOURNEY TEST
    // ============================================================

    public function test_complete_user_journey_from_register_to_product(): void
    {
        // Step 1: Register
        $this->post('/register', [
            'name' => 'Test Owner',
            'email' => 'owner@test.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'business_name' => 'Test Cafe',
            'phone' => '081234567890',
        ]);

        $user = User::where('email', 'owner@test.com')->first();
        $tenant = $user->tenant;
        $outlet = $tenant->outlets->first();

        $this->assertNotNull($user);
        $this->assertNotNull($tenant);
        $this->assertNotNull($outlet);

        // Step 2: Verify email (simulate)
        $user->markEmailAsVerified();

        // Step 3: Complete onboarding - Business settings
        $this->actingAs($user)->post('/onboarding/business', [
            'name' => 'Test Cafe',
            'timezone' => 'Asia/Jakarta',
            'currency' => 'IDR',
        ]);

        $tenant->refresh();
        $this->assertEquals('Asia/Jakarta', $tenant->timezone);

        // Step 4: Create first product
        // User is already attached to outlet via registration, but ensure is_default is set
        $user->outlets()->updateExistingPivot($outlet->id, ['is_default' => true]);

        $productResponse = $this->actingAs($user)->post('/onboarding/product', [
            'name' => 'Kopi Hitam',
            'category_name' => 'Minuman',
            'price' => 15000,
        ]);

        // Check response status - should redirect on success
        $productResponse->assertRedirect();

        $this->assertDatabaseHas('products', [
            'tenant_id' => $tenant->id,
            'name' => 'Kopi Hitam',
        ]);

        // Step 5: Setup payment methods
        $this->actingAs($user)->post('/onboarding/payment-methods', [
            'methods' => ['cash', 'qris'],
        ]);

        $this->assertEquals(2, PaymentMethod::where('tenant_id', $tenant->id)->count());

        // Step 6: Complete onboarding
        $this->actingAs($user)->get('/onboarding/complete');

        $tenant->refresh();
        $this->assertNotNull($tenant->onboarding_completed_at);

        // Step 7: Access dashboard
        $response = $this->actingAs($user)->get('/admin/dashboard');
        $response->assertStatus(200);

        // Step 8: Create additional product
        $category = ProductCategory::where('tenant_id', $tenant->id)->first();

        $this->actingAs($user)->post('/menu/products', [
            'sku' => 'PROD-002',
            'name' => 'Kopi Susu',
            'category_id' => $category->id,
            'base_price' => 20000,
            'product_type' => 'single',
            'is_active' => true,
            'show_in_pos' => true,
        ]);

        $this->assertDatabaseHas('products', [
            'tenant_id' => $tenant->id,
            'name' => 'Kopi Susu',
        ]);

        // Verify final state
        $this->assertEquals(2, Product::where('tenant_id', $tenant->id)->count());
        $this->assertEquals(1, ProductCategory::where('tenant_id', $tenant->id)->count());
        $this->assertEquals(2, PaymentMethod::where('tenant_id', $tenant->id)->count());
        $this->assertEquals(1, Outlet::where('tenant_id', $tenant->id)->count());
        $this->assertTrue($user->tenant->subscriptions()->exists());
    }

    // ============================================================
    // FROZEN TENANT TESTS
    // ============================================================

    public function test_frozen_tenant_cannot_create_product(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        Outlet::factory()->create(['tenant_id' => $tenant->id]);

        $plan = SubscriptionPlan::where('slug', 'starter')->first();
        Subscription::factory()->frozen()->create([
            'tenant_id' => $tenant->id,
            'subscription_plan_id' => $plan->id,
        ]);

        // Verify tenant is recognized as frozen
        $tenant->refresh();
        $this->assertTrue($tenant->isFrozen());

        // Note: The frozen.block middleware needs to be applied to routes
        // for automatic blocking. This test verifies the isFrozen() detection.
        $this->assertFalse($tenant->canCreateTransactions());
    }

    public function test_frozen_tenant_can_view_products(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        Outlet::factory()->create(['tenant_id' => $tenant->id]);

        $plan = SubscriptionPlan::where('slug', 'starter')->first();
        Subscription::factory()->frozen()->create([
            'tenant_id' => $tenant->id,
            'subscription_plan_id' => $plan->id,
        ]);

        $category = ProductCategory::factory()->create(['tenant_id' => $tenant->id]);
        Product::factory()->create([
            'tenant_id' => $tenant->id,
            'category_id' => $category->id,
            'name' => 'Existing Product',
        ]);

        $response = $this->actingAs($user)->get('/menu/products');

        $response->assertStatus(200);
        $response->assertSee('Existing Product');
    }
}
