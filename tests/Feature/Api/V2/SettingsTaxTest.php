<?php

namespace Tests\Feature\Api\V2;

use App\Models\Outlet;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * TDD Tests for Settings API - Tax Settings
 *
 * These tests ensure the Settings API returns complete tax configuration
 * needed for POS mobile app to calculate taxes correctly.
 */
class SettingsTaxTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private User $user;

    private Outlet $outlet;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create([
            'tax_enabled' => true,
            'tax_percentage' => 11.00,
            'tax_mode' => 'exclusive',
            'service_charge_enabled' => true,
            'service_charge_percentage' => 5.00,
        ]);

        $this->user = User::factory()->create(['tenant_id' => $this->tenant->id]);

        $this->outlet = Outlet::factory()->create([
            'tenant_id' => $this->tenant->id,
            'tax_enabled' => true,
            'tax_percentage' => 10.00,
            'tax_mode' => 'inclusive',
            'service_charge_enabled' => false,
            'service_charge_percentage' => 0,
        ]);
    }

    // ==================== Outlet Settings ====================

    /** @test */
    public function outlet_settings_includes_tax_enabled(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v2/settings/outlet', [
            'X-Outlet-Id' => $this->outlet->id,
        ]);

        $response->assertOk();
        $this->assertArrayHasKey('tax_enabled', $response->json('data'));
        $this->assertTrue($response->json('data.tax_enabled'));
    }

    /** @test */
    public function outlet_settings_includes_tax_mode(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v2/settings/outlet', [
            'X-Outlet-Id' => $this->outlet->id,
        ]);

        $response->assertOk();
        $this->assertArrayHasKey('tax_mode', $response->json('data'));
        $this->assertEquals('inclusive', $response->json('data.tax_mode'));
    }

    /** @test */
    public function outlet_settings_includes_service_charge_enabled(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v2/settings/outlet', [
            'X-Outlet-Id' => $this->outlet->id,
        ]);

        $response->assertOk();
        $this->assertArrayHasKey('service_charge_enabled', $response->json('data'));
        $this->assertFalse($response->json('data.service_charge_enabled'));
    }

    /** @test */
    public function outlet_settings_inherits_tax_mode_from_tenant_when_null(): void
    {
        // Create outlet without tax_mode override
        $outletWithoutOverride = Outlet::factory()->create([
            'tenant_id' => $this->tenant->id,
            'tax_mode' => null,
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v2/settings/outlet', [
            'X-Outlet-Id' => $outletWithoutOverride->id,
        ]);

        $response->assertOk();
        // Should inherit from tenant (exclusive)
        $this->assertEquals('exclusive', $response->json('data.tax_mode'));
    }

    /** @test */
    public function outlet_settings_inherits_tax_enabled_from_tenant_when_null(): void
    {
        // Create outlet without tax_enabled override
        $outletWithoutOverride = Outlet::factory()->create([
            'tenant_id' => $this->tenant->id,
            'tax_enabled' => null,
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v2/settings/outlet', [
            'X-Outlet-Id' => $outletWithoutOverride->id,
        ]);

        $response->assertOk();
        // Should inherit from tenant (true)
        $this->assertTrue($response->json('data.tax_enabled'));
    }

    // ==================== Bundled Settings ====================

    /** @test */
    public function bundled_settings_includes_tax_configuration(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v2/settings', [
            'X-Outlet-Id' => $this->outlet->id,
        ]);

        $response->assertOk();

        $outletSettings = $response->json('data.outlet');

        $this->assertArrayHasKey('tax_enabled', $outletSettings);
        $this->assertArrayHasKey('tax_mode', $outletSettings);
        $this->assertArrayHasKey('tax_percentage', $outletSettings);
        $this->assertArrayHasKey('service_charge_enabled', $outletSettings);
        $this->assertArrayHasKey('service_charge_percentage', $outletSettings);
    }

    // ==================== Tax Disabled Scenarios ====================

    /** @test */
    public function outlet_settings_returns_zero_tax_when_disabled(): void
    {
        $outletTaxDisabled = Outlet::factory()->create([
            'tenant_id' => $this->tenant->id,
            'tax_enabled' => false,
            'tax_percentage' => 11.00, // Even if percentage is set
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v2/settings/outlet', [
            'X-Outlet-Id' => $outletTaxDisabled->id,
        ]);

        $response->assertOk();
        $this->assertFalse($response->json('data.tax_enabled'));
        // Tax percentage should still be returned for reference
        $this->assertEquals(11.00, $response->json('data.tax_percentage'));
    }

    /** @test */
    public function outlet_settings_returns_correct_effective_tax_percentage(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v2/settings/outlet', [
            'X-Outlet-Id' => $this->outlet->id,
        ]);

        $response->assertOk();
        // Outlet has override of 10%
        $this->assertEquals(10.00, $response->json('data.tax_percentage'));
    }

    // ==================== Complete Tax Settings Structure ====================

    /** @test */
    public function outlet_settings_has_complete_tax_structure(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v2/settings/outlet', [
            'X-Outlet-Id' => $this->outlet->id,
        ]);

        $response->assertOk();

        $expectedTaxFields = [
            'tax_enabled',
            'tax_mode',
            'tax_percentage',
            'service_charge_enabled',
            'service_charge_percentage',
        ];

        foreach ($expectedTaxFields as $field) {
            $this->assertArrayHasKey($field, $response->json('data'), "Missing field: {$field}");
        }
    }
}
