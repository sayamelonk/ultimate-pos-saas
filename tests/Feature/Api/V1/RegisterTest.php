<?php

namespace Tests\Feature\Api\V1;

use App\Models\Outlet;
use App\Models\Role;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegisterTest extends TestCase
{
    use RefreshDatabase;

    protected SubscriptionPlan $professionalPlan;

    protected Role $ownerRole;

    protected function setUp(): void
    {
        parent::setUp();

        // Create owner role
        $this->ownerRole = Role::create([
            'name' => 'Tenant Owner',
            'slug' => 'tenant-owner',
            'is_system' => true,
        ]);

        // Create professional plan for trial
        $this->professionalPlan = SubscriptionPlan::create([
            'name' => 'Professional',
            'slug' => 'professional',
            'description' => 'Full features for professionals',
            'price_monthly' => 499000,
            'price_yearly' => 4990000,
            'max_outlets' => 10,
            'max_users' => 50,
            'max_products' => 5000,
            'features' => [
                'pos_core' => true,
                'inventory_advanced' => true,
                'multi_outlet' => true,
                'api_access' => true,
                'kds' => true,
                'waiter_app' => true,
            ],
            'is_active' => true,
            'sort_order' => 3,
        ]);
    }

    /** @test */
    public function user_can_register_with_valid_data(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'business_name' => 'John Restaurant',
            'phone' => '081234567890',
            'device_name' => 'POS Terminal 1',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'user' => [
                        'id',
                        'name',
                        'email',
                        'roles',
                        'outlets',
                        'current_outlet',
                    ],
                    'token',
                    'token_type',
                ],
            ])
            ->assertJson([
                'success' => true,
                'data' => [
                    'user' => [
                        'name' => 'John Doe',
                        'email' => 'john@example.com',
                    ],
                    'token_type' => 'Bearer',
                ],
            ]);

        // Assert user created
        $this->assertDatabaseHas('users', [
            'email' => 'john@example.com',
            'name' => 'John Doe',
        ]);
    }

    /** @test */
    public function register_creates_tenant_and_trial_subscription(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'business_name' => 'Jane Cafe',
            'phone' => '081234567891',
            'device_name' => 'POS Terminal 1',
        ]);

        $response->assertStatus(201);

        // Assert tenant created
        $this->assertDatabaseHas('tenants', [
            'name' => 'Jane Cafe',
        ]);

        $tenant = Tenant::where('name', 'Jane Cafe')->first();
        $this->assertNotNull($tenant);

        // Assert user belongs to tenant
        $user = User::where('email', 'jane@example.com')->first();
        $this->assertEquals($tenant->id, $user->tenant_id);

        // Assert user has owner role
        $this->assertTrue($user->roles->contains('slug', 'tenant-owner'));

        // Assert trial subscription created
        $subscription = Subscription::where('tenant_id', $tenant->id)->first();
        $this->assertNotNull($subscription);
        $this->assertEquals(Subscription::STATUS_TRIAL, $subscription->status);
        $this->assertTrue($subscription->is_trial);
        $this->assertEquals($this->professionalPlan->id, $subscription->subscription_plan_id);

        // Assert trial expires in approximately 14 days (13-14 due to timing)
        $this->assertTrue($subscription->trial_ends_at->isFuture());
        $daysRemaining = (int) now()->diffInDays($subscription->trial_ends_at);
        $this->assertGreaterThanOrEqual(13, $daysRemaining);
        $this->assertLessThanOrEqual(14, $daysRemaining);

        // Assert default outlet created
        $outlet = Outlet::where('tenant_id', $tenant->id)->first();
        $this->assertNotNull($outlet);
        $this->assertEquals('Jane Cafe', $outlet->name);

        // Assert user has access to outlet
        $this->assertTrue($user->outlets->contains($outlet));
    }

    /** @test */
    public function register_validates_email_unique(): void
    {
        // Create existing user
        $tenant = Tenant::factory()->create();
        User::factory()->create([
            'tenant_id' => $tenant->id,
            'email' => 'existing@example.com',
        ]);

        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'New User',
            'email' => 'existing@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'business_name' => 'New Business',
            'phone' => '081234567892',
            'device_name' => 'POS Terminal 1',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    /** @test */
    public function register_validates_required_fields(): void
    {
        $response = $this->postJson('/api/v1/auth/register', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'name',
                'email',
                'password',
                'business_name',
                'device_name',
            ]);
    }

    /** @test */
    public function register_validates_password_confirmation(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'password_confirmation' => 'different_password',
            'business_name' => 'John Restaurant',
            'device_name' => 'POS Terminal 1',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    /** @test */
    public function register_validates_password_minimum_length(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => '12345',
            'password_confirmation' => '12345',
            'business_name' => 'John Restaurant',
            'device_name' => 'POS Terminal 1',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    /** @test */
    public function register_validates_email_format(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'John Doe',
            'email' => 'invalid-email',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'business_name' => 'John Restaurant',
            'device_name' => 'POS Terminal 1',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    /** @test */
    public function registered_user_can_login_immediately(): void
    {
        // Register
        $registerResponse = $this->postJson('/api/v1/auth/register', [
            'name' => 'Login Test User',
            'email' => 'logintest@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'business_name' => 'Login Test Business',
            'phone' => '081234567893',
            'device_name' => 'POS Terminal 1',
        ]);

        $registerResponse->assertStatus(201);

        // Try login with same credentials
        $loginResponse = $this->postJson('/api/v1/auth/login', [
            'email' => 'logintest@example.com',
            'password' => 'password123',
            'device_name' => 'POS Terminal 2',
        ]);

        $loginResponse->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    'user' => [
                        'email' => 'logintest@example.com',
                    ],
                ],
            ]);
    }
}
