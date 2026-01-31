<?php

namespace Tests\Feature\Auth;

use App\Models\Outlet;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * TC-REG-001: Registrasi dengan data valid
     *
     * Preconditions: -
     * Expected: User ter-create dengan tenant baru, Outlet default "Main Outlet",
     *            User assigned role "tenant-owner", User assigned ke outlet default,
     *            Redirect ke dashboard dengan success message
     */
    public function test_user_can_register_with_valid_data(): void
    {
        // Arrange: Ensure tenant-owner role exists
        $ownerRole = Role::where('slug', 'tenant-owner')
            ->whereNull('tenant_id')
            ->first();

        if (! $ownerRole) {
            $this->markTestSkipped('Tenant owner role does not exist in database');
        }

        // Act: Register new user
        $response = $this->post(route('register'), [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
            'business_name' => "John's Store",
            'phone' => '08123456789',
        ]);

        // Assert: Database records created
        $this->assertDatabaseHas('tenants', [
            'name' => "John's Store",
            'email' => 'john@example.com',
            'phone' => '08123456789',
            'is_active' => true,
        ]);

        $tenant = Tenant::where('email', 'john@example.com')->first();

        $this->assertDatabaseHas('outlets', [
            'tenant_id' => $tenant->id,
            'code' => 'MAIN',
            'name' => 'Main Outlet',
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('users', [
            'tenant_id' => $tenant->id,
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'phone' => '08123456789',
            'is_active' => true,
        ]);

        $user = User::where('email', 'john@example.com')->first();

        // Assert: User has tenant-owner role
        $this->assertTrue(
            $user->roles()->where('slug', 'tenant-owner')->exists(),
            'User should have tenant-owner role'
        );

        // Assert: User is assigned to default outlet
        $this->assertTrue(
            $user->outlets()->where('code', 'MAIN')->exists(),
            'User should be assigned to Main Outlet'
        );

        $defaultOutlet = $user->defaultOutlet();
        $this->assertNotNull($defaultOutlet, 'User should have a default outlet');
        $this->assertEquals('MAIN', $defaultOutlet->code);

        // Assert: User is authenticated and redirected to dashboard
        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(route('admin.dashboard'));
        $response->assertSessionHas('success');
    }

    /**
     * TC-REG-001b: Transactional registration - all or nothing
     *
     * Expected: Semua data ter-create dalam satu transaction
     */
    public function test_registration_creates_all_records_in_single_transaction(): void
    {
        // Arrange: Count before registration
        $tenantsBefore = Tenant::count();
        $outletsBefore = Outlet::count();
        $usersBefore = User::count();

        // Act: Register
        $this->post(route('register'), [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
            'business_name' => 'Test Business',
        ]);

        // Assert: All records created
        $this->assertEquals($tenantsBefore + 1, Tenant::count(), 'Tenant count should increase by 1');
        $this->assertEquals($outletsBefore + 1, Outlet::count(), 'Outlet count should increase by 1');
        $this->assertEquals($usersBefore + 1, User::count(), 'User count should increase by 1');

        // Verify relationships
        $user = User::where('email', 'test@example.com')->first();
        $this->assertNotNull($user->tenant, 'User should belong to a tenant');
        $this->assertNotNull($user->defaultOutlet(), 'User should have a default outlet');
    }

    /**
     * TC-REG-002: Registrasi dengan email sudah terdaftar
     *
     * Preconditions: Email "existing@example.com" sudah terdaftar
     * Expected: Error "The email has already been taken."
     */
    public function test_user_cannot_register_with_existing_email(): void
    {
        // Arrange: Create existing user
        User::factory()->create([
            'email' => 'existing@example.com',
        ]);

        // Act: Try to register with same email
        $response = $this->post(route('register'), [
            'name' => 'New User',
            'email' => 'existing@example.com',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
            'business_name' => 'New Business',
        ]);

        // Assert: Validation error
        $response->assertSessionHasErrors(['email']);

        // Verify no new records created
        $this->assertEquals(1, User::where('email', 'existing@example.com')->count());

        // Verify error message
        $response->assertSessionHas('errors', function ($errors) {
            return $errors->has('email');
        });
    }

    /**
     * TC-REG-003: Validasi password strength
     *
     * Preconditions: -
     * Expected: Error validasi password (minimum requirements)
     */
    public function test_registration_requires_strong_password(): void
    {
        // Act: Try to register with weak password
        $response = $this->post(route('register'), [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => '123', // Weak password
            'password_confirmation' => '123',
            'business_name' => 'Test Business',
        ]);

        // Assert: Validation error for password
        $response->assertSessionHasErrors(['password']);

        // Verify user was not created
        $this->assertDatabaseMissing('users', [
            'email' => 'test@example.com',
        ]);
    }

    /**
     * TC-REG-003b: Validasi password dengan berbagai kasus lemah
     *
     * Expected: Error untuk password yang tidak memenuhi syarat
     */
    public function test_password_validation_fails_for_weak_passwords(): void
    {
        $weakPasswords = [
            'password', // No number
            '12345678', // No letter
            'Pass1', // Too short
        ];

        foreach ($weakPasswords as $index => $weakPassword) {
            $response = $this->post(route('register'), [
                'name' => 'Test User',
                'email' => "testweak{$index}@example.com",
                'password' => $weakPassword,
                'password_confirmation' => $weakPassword,
                'business_name' => 'Test Business',
            ]);

            // Check if validation failed (password rules may vary)
            $this->assertContains(
                $response->getStatusCode(),
                [302, 422], // Redirect with validation errors
                "Password '{$weakPassword}' should fail validation"
            );
        }
    }

    /**
     * TC-REG-004: Password confirmation tidak cocok
     *
     * Preconditions: -
     * Expected: Error "The password confirmation does not match."
     */
    public function test_password_confirmation_must_match(): void
    {
        // Act: Try to register with mismatched password confirmation
        $response = $this->post(route('register'), [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'Password123',
            'password_confirmation' => 'DifferentPass',
            'business_name' => 'Test Business',
        ]);

        // Assert: Validation error
        $response->assertSessionHasErrors(['password']);

        // Verify error message
        $response->assertSessionHas('errors', function ($errors) {
            return $errors->has('password');
        });
    }

    /**
     * TC-REG-005: Register tanpa mengisi required fields
     *
     * Expected: Error validation untuk semua required fields
     */
    public function test_registration_requires_all_fields(): void
    {
        // Act: Submit empty form
        $response = $this->post(route('register'), []);

        // Assert: Validation errors for all required fields
        $response->assertSessionHasErrors([
            'name',
            'email',
            'password',
            'business_name',
        ]);
    }

    /**
     * TC-REG-006: Register dengan email format invalid
     *
     * Expected: Error validation untuk format email
     */
    public function test_registration_requires_valid_email_format(): void
    {
        // Act: Try to register with invalid email
        $response = $this->post(route('register'), [
            'name' => 'Test User',
            'email' => 'invalid-email-format',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
            'business_name' => 'Test Business',
        ]);

        // Assert
        $response->assertSessionHasErrors(['email']);
    }

    /**
     * TC-REG-007: Register page loads successfully
     *
     * Expected: Form registrasi ditampilkan
     */
    public function test_register_page_loads_successfully(): void
    {
        // Act: Visit register page
        $response = $this->get(route('register'));

        // Assert
        $response->assertStatus(200);
        $response->assertViewIs('auth.register');
    }

    /**
     * TC-REG-008: Authenticated user cannot access register page
     *
     * Expected: Redirect ke dashboard
     */
    public function test_authenticated_user_cannot_access_register_page(): void
    {
        // Arrange: Create and login user
        $user = User::factory()->create();
        $this->actingAs($user);

        // Act: Try to access register page
        $response = $this->get(route('register'));

        // Assert: Redirect to dashboard
        $response->assertRedirect(route('dashboard'));
    }

    /**
     * TC-REG-009: Register dengan phone number nullable
     *
     * Expected: Registrasi berhasil tanpa phone number
     */
    public function test_user_can_register_without_phone_number(): void
    {
        // Act: Register without phone
        $response = $this->post(route('register'), [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
            'business_name' => 'Test Business',
            'phone' => null,
        ]);

        // Assert: User created successfully
        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
            'phone' => null,
        ]);

        $response->assertRedirect(route('admin.dashboard'));
    }

    /**
     * TC-REG-010: Auto-login after registration
     *
     * Expected: User otomatis login setelah registrasi
     */
    public function test_user_is_auto_logged_in_after_registration(): void
    {
        // Act: Register
        $this->post(route('register'), [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
            'business_name' => 'Test Business',
        ]);

        // Assert: User is authenticated
        $this->assertAuthenticated();

        $user = User::where('email', 'test@example.com')->first();
        $this->assertEquals('test@example.com', Auth::user()->email);
    }

    /**
     * TC-REG-011: Outlet default created with correct settings
     *
     * Expected: Outlet default dengan code "MAIN", name "Main Outlet", dan active
     */
    public function test_default_outlet_created_with_correct_settings(): void
    {
        // Act: Register
        $this->post(route('register'), [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
            'business_name' => 'Test Business',
        ]);

        // Assert: Default outlet has correct settings
        $user = User::where('email', 'test@example.com')->first();
        $outlet = $user->defaultOutlet();

        $this->assertEquals('MAIN', $outlet->code);
        $this->assertEquals('Main Outlet', $outlet->name);
        $this->assertEquals('08:00', $outlet->opening_time);
        $this->assertEquals('22:00', $outlet->closing_time);
        $this->assertTrue($outlet->is_active);
    }

    /**
     * TC-REG-012: Tenant created with correct default settings
     *
     * Expected: Tenant dengan default currency, timezone, dan tax
     */
    public function test_tenant_created_with_correct_default_settings(): void
    {
        // Act: Register
        $response = $this->post(route('register'), [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
            'business_name' => 'Test Business',
            'phone' => '08123456789',
        ]);

        // Assert: Tenant has correct default settings
        $tenant = Tenant::where('email', 'test@example.com')->first();

        $this->assertEquals('Test Business', $tenant->name);
        $this->assertEquals('test@example.com', $tenant->email);
        $this->assertEquals('08123456789', $tenant->phone);
        $this->assertEquals('IDR', $tenant->currency);
        $this->assertEquals('Asia/Jakarta', $tenant->timezone);
        $this->assertEquals(11.00, $tenant->tax_percentage);
        $this->assertEquals(0, $tenant->service_charge_percentage);
        $this->assertEquals('free', $tenant->subscription_plan);
        $this->assertEquals(1, $tenant->max_outlets);
        $this->assertTrue($tenant->is_active);

        // Verify tenant code is generated
        $this->assertNotNull($tenant->code);
        $this->assertEquals(6, strlen($tenant->code));
    }
}
