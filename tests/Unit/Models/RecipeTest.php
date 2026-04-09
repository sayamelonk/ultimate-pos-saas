<?php

namespace Tests\Unit\Models;

use App\Models\InventoryItem;
use App\Models\Recipe;
use App\Models\RecipeItem;
use App\Models\Tenant;
use App\Models\Unit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecipeTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;

    protected Unit $unit;

    protected InventoryItem $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create();
        $this->unit = Unit::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->product = InventoryItem::factory()->create([
            'tenant_id' => $this->tenant->id,
            'unit_id' => $this->unit->id,
        ]);
    }

    // ============================================================
    // BASIC CREATION TESTS
    // ============================================================

    public function test_can_create_recipe(): void
    {
        $recipe = Recipe::factory()->create([
            'tenant_id' => $this->tenant->id,
            'product_id' => $this->product->id,
            'yield_unit_id' => $this->unit->id,
            'name' => 'Test Recipe',
        ]);

        $this->assertDatabaseHas('recipes', [
            'id' => $recipe->id,
            'name' => 'Test Recipe',
        ]);
    }

    public function test_recipe_belongs_to_tenant(): void
    {
        $recipe = Recipe::factory()->create([
            'tenant_id' => $this->tenant->id,
            'product_id' => $this->product->id,
            'yield_unit_id' => $this->unit->id,
        ]);

        $this->assertInstanceOf(Tenant::class, $recipe->tenant);
        $this->assertEquals($this->tenant->id, $recipe->tenant->id);
    }

    public function test_recipe_belongs_to_product(): void
    {
        $recipe = Recipe::factory()->create([
            'tenant_id' => $this->tenant->id,
            'product_id' => $this->product->id,
            'yield_unit_id' => $this->unit->id,
        ]);

        $this->assertInstanceOf(InventoryItem::class, $recipe->product);
        $this->assertEquals($this->product->id, $recipe->product->id);
    }

    public function test_recipe_belongs_to_yield_unit(): void
    {
        $recipe = Recipe::factory()->create([
            'tenant_id' => $this->tenant->id,
            'product_id' => $this->product->id,
            'yield_unit_id' => $this->unit->id,
        ]);

        $this->assertInstanceOf(Unit::class, $recipe->yieldUnit);
        $this->assertEquals($this->unit->id, $recipe->yieldUnit->id);
    }

    // ============================================================
    // ITEMS RELATIONSHIP TESTS
    // ============================================================

    public function test_recipe_has_many_items(): void
    {
        $recipe = Recipe::factory()->create([
            'tenant_id' => $this->tenant->id,
            'product_id' => $this->product->id,
            'yield_unit_id' => $this->unit->id,
        ]);

        $ingredient1 = InventoryItem::factory()->create([
            'tenant_id' => $this->tenant->id,
            'unit_id' => $this->unit->id,
        ]);

        $ingredient2 = InventoryItem::factory()->create([
            'tenant_id' => $this->tenant->id,
            'unit_id' => $this->unit->id,
        ]);

        RecipeItem::factory()->create([
            'recipe_id' => $recipe->id,
            'inventory_item_id' => $ingredient1->id,
            'unit_id' => $this->unit->id,
            'sort_order' => 1,
        ]);

        RecipeItem::factory()->create([
            'recipe_id' => $recipe->id,
            'inventory_item_id' => $ingredient2->id,
            'unit_id' => $this->unit->id,
            'sort_order' => 2,
        ]);

        $this->assertCount(2, $recipe->items);
    }

    public function test_recipe_items_are_ordered_by_sort_order(): void
    {
        $recipe = Recipe::factory()->create([
            'tenant_id' => $this->tenant->id,
            'product_id' => $this->product->id,
            'yield_unit_id' => $this->unit->id,
        ]);

        $ingredient1 = InventoryItem::factory()->create([
            'tenant_id' => $this->tenant->id,
            'unit_id' => $this->unit->id,
            'name' => 'Ingredient B',
        ]);

        $ingredient2 = InventoryItem::factory()->create([
            'tenant_id' => $this->tenant->id,
            'unit_id' => $this->unit->id,
            'name' => 'Ingredient A',
        ]);

        RecipeItem::factory()->create([
            'recipe_id' => $recipe->id,
            'inventory_item_id' => $ingredient1->id,
            'unit_id' => $this->unit->id,
            'sort_order' => 2,
        ]);

        RecipeItem::factory()->create([
            'recipe_id' => $recipe->id,
            'inventory_item_id' => $ingredient2->id,
            'unit_id' => $this->unit->id,
            'sort_order' => 1,
        ]);

        $items = $recipe->items;
        $this->assertEquals($ingredient2->id, $items[0]->inventory_item_id);
        $this->assertEquals($ingredient1->id, $items[1]->inventory_item_id);
    }

    // ============================================================
    // TIME CALCULATION TESTS
    // ============================================================

    public function test_get_total_time_minutes(): void
    {
        $recipe = Recipe::factory()->create([
            'tenant_id' => $this->tenant->id,
            'product_id' => $this->product->id,
            'yield_unit_id' => $this->unit->id,
            'prep_time_minutes' => 15,
            'cook_time_minutes' => 30,
        ]);

        $this->assertEquals(45, $recipe->getTotalTimeMinutes());
    }

    public function test_get_total_time_minutes_with_null_prep(): void
    {
        $recipe = Recipe::factory()->create([
            'tenant_id' => $this->tenant->id,
            'product_id' => $this->product->id,
            'yield_unit_id' => $this->unit->id,
            'prep_time_minutes' => null,
            'cook_time_minutes' => 30,
        ]);

        $this->assertEquals(30, $recipe->getTotalTimeMinutes());
    }

    public function test_get_total_time_minutes_with_null_cook(): void
    {
        $recipe = Recipe::factory()->create([
            'tenant_id' => $this->tenant->id,
            'product_id' => $this->product->id,
            'yield_unit_id' => $this->unit->id,
            'prep_time_minutes' => 15,
            'cook_time_minutes' => null,
        ]);

        $this->assertEquals(15, $recipe->getTotalTimeMinutes());
    }

    public function test_get_total_time_minutes_with_both_null(): void
    {
        $recipe = Recipe::factory()->create([
            'tenant_id' => $this->tenant->id,
            'product_id' => $this->product->id,
            'yield_unit_id' => $this->unit->id,
            'prep_time_minutes' => null,
            'cook_time_minutes' => null,
        ]);

        $this->assertEquals(0, $recipe->getTotalTimeMinutes());
    }

    // ============================================================
    // COST CALCULATION TESTS
    // ============================================================

    public function test_get_cost_per_unit(): void
    {
        $recipe = Recipe::factory()->create([
            'tenant_id' => $this->tenant->id,
            'product_id' => $this->product->id,
            'yield_unit_id' => $this->unit->id,
            'estimated_cost' => 50000,
            'yield_qty' => 10,
        ]);

        $this->assertEquals(5000, $recipe->getCostPerUnit());
    }

    public function test_get_cost_per_unit_with_zero_yield(): void
    {
        $recipe = Recipe::factory()->create([
            'tenant_id' => $this->tenant->id,
            'product_id' => $this->product->id,
            'yield_unit_id' => $this->unit->id,
            'estimated_cost' => 50000,
            'yield_qty' => 0,
        ]);

        $this->assertEquals(0, $recipe->getCostPerUnit());
    }

    public function test_get_cost_per_unit_with_decimal_yield(): void
    {
        $recipe = Recipe::factory()->create([
            'tenant_id' => $this->tenant->id,
            'product_id' => $this->product->id,
            'yield_unit_id' => $this->unit->id,
            'estimated_cost' => 100000,
            'yield_qty' => 2.5,
        ]);

        $this->assertEquals(40000, $recipe->getCostPerUnit());
    }

    // ============================================================
    // CASTING TESTS
    // ============================================================

    public function test_decimal_fields_are_properly_cast(): void
    {
        $recipe = Recipe::factory()->create([
            'tenant_id' => $this->tenant->id,
            'product_id' => $this->product->id,
            'yield_unit_id' => $this->unit->id,
            'yield_qty' => 5.5000,
            'estimated_cost' => 75000.50,
        ]);

        $this->assertIsString($recipe->yield_qty);
        $this->assertIsString($recipe->estimated_cost);
    }

    public function test_integer_fields_are_properly_cast(): void
    {
        $recipe = Recipe::factory()->create([
            'tenant_id' => $this->tenant->id,
            'product_id' => $this->product->id,
            'yield_unit_id' => $this->unit->id,
            'prep_time_minutes' => '15',
            'cook_time_minutes' => '30',
        ]);

        $this->assertIsInt($recipe->prep_time_minutes);
        $this->assertIsInt($recipe->cook_time_minutes);
    }

    public function test_boolean_fields_are_properly_cast(): void
    {
        $recipe = Recipe::factory()->create([
            'tenant_id' => $this->tenant->id,
            'product_id' => $this->product->id,
            'yield_unit_id' => $this->unit->id,
            'is_active' => 1,
        ]);

        $this->assertTrue($recipe->is_active);
    }

    // ============================================================
    // FACTORY STATE TESTS
    // ============================================================

    public function test_inactive_factory_state(): void
    {
        $recipe = Recipe::factory()->inactive()->create([
            'tenant_id' => $this->tenant->id,
            'product_id' => $this->product->id,
            'yield_unit_id' => $this->unit->id,
        ]);

        $this->assertFalse($recipe->is_active);
    }

    public function test_quick_recipe_factory_state(): void
    {
        $recipe = Recipe::factory()->quickRecipe()->create([
            'tenant_id' => $this->tenant->id,
            'product_id' => $this->product->id,
            'yield_unit_id' => $this->unit->id,
        ]);

        $this->assertLessThanOrEqual(5, $recipe->prep_time_minutes);
        $this->assertLessThanOrEqual(10, $recipe->cook_time_minutes);
    }

    public function test_complex_recipe_factory_state(): void
    {
        $recipe = Recipe::factory()->complexRecipe()->create([
            'tenant_id' => $this->tenant->id,
            'product_id' => $this->product->id,
            'yield_unit_id' => $this->unit->id,
        ]);

        $this->assertGreaterThanOrEqual(30, $recipe->prep_time_minutes);
        $this->assertGreaterThanOrEqual(60, $recipe->cook_time_minutes);
    }

    public function test_with_version_factory_state(): void
    {
        $recipe = Recipe::factory()->withVersion(3)->create([
            'tenant_id' => $this->tenant->id,
            'product_id' => $this->product->id,
            'yield_unit_id' => $this->unit->id,
        ]);

        $this->assertEquals(3, $recipe->version);
    }
}
