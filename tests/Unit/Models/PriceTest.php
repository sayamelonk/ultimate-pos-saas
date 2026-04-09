<?php

namespace Tests\Unit\Models;

use App\Models\InventoryItem;
use App\Models\Outlet;
use App\Models\Price;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PriceTest extends TestCase
{
    use RefreshDatabase;

    // ==================== CREATION TESTS ====================

    public function test_can_create_price(): void
    {
        $tenant = Tenant::factory()->create();
        $inventoryItem = InventoryItem::factory()->create(['tenant_id' => $tenant->id]);
        $outlet = Outlet::factory()->create(['tenant_id' => $tenant->id]);

        $price = Price::factory()->create([
            'tenant_id' => $tenant->id,
            'inventory_item_id' => $inventoryItem->id,
            'outlet_id' => $outlet->id,
            'selling_price' => 25000,
        ]);

        $this->assertDatabaseHas('prices', [
            'id' => $price->id,
            'tenant_id' => $tenant->id,
            'inventory_item_id' => $inventoryItem->id,
            'outlet_id' => $outlet->id,
        ]);
    }

    public function test_price_has_required_attributes(): void
    {
        $price = Price::factory()->create();

        $this->assertNotNull($price->id);
        $this->assertNotNull($price->tenant_id);
        $this->assertNotNull($price->inventory_item_id);
        $this->assertNotNull($price->outlet_id);
        $this->assertNotNull($price->selling_price);
    }

    // ==================== RELATIONSHIP TESTS ====================

    public function test_price_belongs_to_tenant(): void
    {
        $tenant = Tenant::factory()->create();
        $price = Price::factory()->create(['tenant_id' => $tenant->id]);

        $this->assertInstanceOf(Tenant::class, $price->tenant);
        $this->assertEquals($tenant->id, $price->tenant->id);
    }

    public function test_price_belongs_to_inventory_item(): void
    {
        $inventoryItem = InventoryItem::factory()->create();
        $price = Price::factory()->create(['inventory_item_id' => $inventoryItem->id]);

        $this->assertInstanceOf(InventoryItem::class, $price->inventoryItem);
        $this->assertEquals($inventoryItem->id, $price->inventoryItem->id);
    }

    public function test_price_belongs_to_outlet(): void
    {
        $outlet = Outlet::factory()->create();
        $price = Price::factory()->create(['outlet_id' => $outlet->id]);

        $this->assertInstanceOf(Outlet::class, $price->outlet);
        $this->assertEquals($outlet->id, $price->outlet->id);
    }

    // ==================== GET PRICE FOR OUTLET TESTS ====================

    public function test_get_price_for_outlet_returns_selling_price(): void
    {
        $tenant = Tenant::factory()->create();
        $inventoryItem = InventoryItem::factory()->create(['tenant_id' => $tenant->id]);
        $outlet = Outlet::factory()->create(['tenant_id' => $tenant->id]);

        Price::factory()->create([
            'tenant_id' => $tenant->id,
            'inventory_item_id' => $inventoryItem->id,
            'outlet_id' => $outlet->id,
            'selling_price' => 25000,
            'is_active' => true,
        ]);

        $result = Price::getPriceForOutlet($inventoryItem->id, $outlet->id);
        $this->assertEquals(25000.0, $result);
    }

    public function test_get_price_for_outlet_returns_member_price_when_is_member(): void
    {
        $tenant = Tenant::factory()->create();
        $inventoryItem = InventoryItem::factory()->create(['tenant_id' => $tenant->id]);
        $outlet = Outlet::factory()->create(['tenant_id' => $tenant->id]);

        Price::factory()->create([
            'tenant_id' => $tenant->id,
            'inventory_item_id' => $inventoryItem->id,
            'outlet_id' => $outlet->id,
            'selling_price' => 25000,
            'member_price' => 22500,
            'is_active' => true,
        ]);

        $result = Price::getPriceForOutlet($inventoryItem->id, $outlet->id, true);
        $this->assertEquals(22500.0, $result);
    }

    public function test_get_price_for_outlet_returns_selling_price_when_member_price_is_null(): void
    {
        $tenant = Tenant::factory()->create();
        $inventoryItem = InventoryItem::factory()->create(['tenant_id' => $tenant->id]);
        $outlet = Outlet::factory()->create(['tenant_id' => $tenant->id]);

        Price::factory()->noMemberPrice()->create([
            'tenant_id' => $tenant->id,
            'inventory_item_id' => $inventoryItem->id,
            'outlet_id' => $outlet->id,
            'selling_price' => 25000,
            'is_active' => true,
        ]);

        $result = Price::getPriceForOutlet($inventoryItem->id, $outlet->id, true);
        $this->assertEquals(25000.0, $result);
    }

    public function test_get_price_for_outlet_returns_null_when_no_price_found(): void
    {
        $tenant = Tenant::factory()->create();
        $inventoryItem = InventoryItem::factory()->create(['tenant_id' => $tenant->id]);
        $outlet = Outlet::factory()->create(['tenant_id' => $tenant->id]);

        $result = Price::getPriceForOutlet($inventoryItem->id, $outlet->id);
        $this->assertNull($result);
    }

    public function test_get_price_for_outlet_returns_null_when_price_is_inactive(): void
    {
        $tenant = Tenant::factory()->create();
        $inventoryItem = InventoryItem::factory()->create(['tenant_id' => $tenant->id]);
        $outlet = Outlet::factory()->create(['tenant_id' => $tenant->id]);

        Price::factory()->inactive()->create([
            'tenant_id' => $tenant->id,
            'inventory_item_id' => $inventoryItem->id,
            'outlet_id' => $outlet->id,
            'selling_price' => 25000,
        ]);

        $result = Price::getPriceForOutlet($inventoryItem->id, $outlet->id);
        $this->assertNull($result);
    }

    public function test_get_price_for_outlet_only_returns_price_for_matching_outlet(): void
    {
        $tenant = Tenant::factory()->create();
        $inventoryItem = InventoryItem::factory()->create(['tenant_id' => $tenant->id]);
        $outlet1 = Outlet::factory()->create(['tenant_id' => $tenant->id]);
        $outlet2 = Outlet::factory()->create(['tenant_id' => $tenant->id]);

        Price::factory()->create([
            'tenant_id' => $tenant->id,
            'inventory_item_id' => $inventoryItem->id,
            'outlet_id' => $outlet1->id,
            'selling_price' => 25000,
            'is_active' => true,
        ]);

        $result = Price::getPriceForOutlet($inventoryItem->id, $outlet2->id);
        $this->assertNull($result);
    }

    // ==================== DIFFERENT PRICES PER OUTLET TESTS ====================

    public function test_same_item_can_have_different_prices_per_outlet(): void
    {
        $tenant = Tenant::factory()->create();
        $inventoryItem = InventoryItem::factory()->create(['tenant_id' => $tenant->id]);
        $outlet1 = Outlet::factory()->create(['tenant_id' => $tenant->id]);
        $outlet2 = Outlet::factory()->create(['tenant_id' => $tenant->id]);

        Price::factory()->create([
            'tenant_id' => $tenant->id,
            'inventory_item_id' => $inventoryItem->id,
            'outlet_id' => $outlet1->id,
            'selling_price' => 25000,
            'is_active' => true,
        ]);

        Price::factory()->create([
            'tenant_id' => $tenant->id,
            'inventory_item_id' => $inventoryItem->id,
            'outlet_id' => $outlet2->id,
            'selling_price' => 27000,
            'is_active' => true,
        ]);

        $price1 = Price::getPriceForOutlet($inventoryItem->id, $outlet1->id);
        $price2 = Price::getPriceForOutlet($inventoryItem->id, $outlet2->id);

        $this->assertEquals(25000.0, $price1);
        $this->assertEquals(27000.0, $price2);
    }

    // ==================== MEMBER PRICE TESTS ====================

    public function test_price_can_have_member_price(): void
    {
        $price = Price::factory()->withMemberPrice(22500)->create(['selling_price' => 25000]);
        $this->assertEquals('22500.00', $price->member_price);
    }

    public function test_price_can_have_no_member_price(): void
    {
        $price = Price::factory()->noMemberPrice()->create();
        $this->assertNull($price->member_price);
    }

    // ==================== MIN SELLING PRICE TESTS ====================

    public function test_price_can_have_min_selling_price(): void
    {
        $price = Price::factory()->withMinSellingPrice(18000)->create(['selling_price' => 25000]);
        $this->assertEquals('18000.00', $price->min_selling_price);
    }

    public function test_price_can_have_no_min_selling_price(): void
    {
        $price = Price::factory()->create(['min_selling_price' => null]);
        $this->assertNull($price->min_selling_price);
    }

    // ==================== CASTING TESTS ====================

    public function test_selling_price_is_cast_to_decimal(): void
    {
        $price = Price::factory()->create(['selling_price' => 25000.50]);
        $this->assertEquals('25000.50', $price->selling_price);
    }

    public function test_member_price_is_cast_to_decimal(): void
    {
        $price = Price::factory()->create(['member_price' => 22500.25]);
        $this->assertEquals('22500.25', $price->member_price);
    }

    public function test_min_selling_price_is_cast_to_decimal(): void
    {
        $price = Price::factory()->create(['min_selling_price' => 18000.75]);
        $this->assertEquals('18000.75', $price->min_selling_price);
    }

    public function test_is_active_is_cast_to_boolean(): void
    {
        $price = Price::factory()->create(['is_active' => 1]);
        $this->assertIsBool($price->is_active);
        $this->assertTrue($price->is_active);
    }

    // ==================== FACTORY STATE TESTS ====================

    public function test_factory_inactive_state(): void
    {
        $price = Price::factory()->inactive()->create();
        $this->assertFalse($price->is_active);
    }

    public function test_factory_with_selling_price_state(): void
    {
        $price = Price::factory()->withSellingPrice(35000)->create();
        $this->assertEquals('35000.00', $price->selling_price);
    }

    public function test_factory_with_member_price_state(): void
    {
        $price = Price::factory()->withMemberPrice(30000)->create();
        $this->assertEquals('30000.00', $price->member_price);
    }

    public function test_factory_with_min_selling_price_state(): void
    {
        $price = Price::factory()->withMinSellingPrice(20000)->create();
        $this->assertEquals('20000.00', $price->min_selling_price);
    }

    public function test_factory_no_member_price_state(): void
    {
        $price = Price::factory()->noMemberPrice()->create();
        $this->assertNull($price->member_price);
    }

    // ==================== UUID TRAIT TESTS ====================

    public function test_price_uses_uuid(): void
    {
        $price = Price::factory()->create();
        $this->assertNotNull($price->id);
        $this->assertIsString($price->id);
        // UUID format check
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/',
            $price->id
        );
    }

    // ==================== TENANT ISOLATION TESTS ====================

    public function test_prices_are_isolated_by_tenant(): void
    {
        $tenant1 = Tenant::factory()->create();
        $tenant2 = Tenant::factory()->create();

        Price::factory()->count(3)->create(['tenant_id' => $tenant1->id]);
        Price::factory()->count(2)->create(['tenant_id' => $tenant2->id]);

        $tenant1Prices = Price::where('tenant_id', $tenant1->id)->get();
        $tenant2Prices = Price::where('tenant_id', $tenant2->id)->get();

        $this->assertCount(3, $tenant1Prices);
        $this->assertCount(2, $tenant2Prices);
    }

    // ==================== ACTIVE/INACTIVE TESTS ====================

    public function test_price_is_active_by_default(): void
    {
        $price = Price::factory()->create();
        $this->assertTrue($price->is_active);
    }

    public function test_price_can_be_deactivated(): void
    {
        $price = Price::factory()->inactive()->create();
        $this->assertFalse($price->is_active);
    }

    // ==================== PRICE COMPARISON TESTS ====================

    public function test_member_price_is_less_than_selling_price(): void
    {
        $price = Price::factory()->create([
            'selling_price' => 25000,
            'member_price' => 22500,
        ]);

        $this->assertLessThan($price->selling_price, $price->member_price);
    }

    public function test_min_selling_price_is_less_than_selling_price(): void
    {
        $price = Price::factory()->create([
            'selling_price' => 25000,
            'min_selling_price' => 18750,
        ]);

        $this->assertLessThan($price->selling_price, $price->min_selling_price);
    }
}
