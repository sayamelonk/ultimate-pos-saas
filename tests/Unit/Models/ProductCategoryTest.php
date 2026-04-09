<?php

namespace Tests\Unit\Models;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductCategoryTest extends TestCase
{
    use RefreshDatabase;

    // ==================== CREATION TESTS ====================

    public function test_can_create_category(): void
    {
        $tenant = Tenant::factory()->create();
        $category = ProductCategory::factory()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Makanan',
        ]);

        $this->assertDatabaseHas('product_categories', [
            'id' => $category->id,
            'tenant_id' => $tenant->id,
            'name' => 'Makanan',
        ]);
    }

    public function test_category_has_required_attributes(): void
    {
        $category = ProductCategory::factory()->create();

        $this->assertNotNull($category->id);
        $this->assertNotNull($category->tenant_id);
        $this->assertNotNull($category->code);
        $this->assertNotNull($category->name);
        $this->assertNotNull($category->slug);
    }

    public function test_slug_is_auto_generated_from_name(): void
    {
        $tenant = Tenant::factory()->create();
        $category = ProductCategory::create([
            'tenant_id' => $tenant->id,
            'code' => 'CAT-001',
            'name' => 'Main Course Items',
            'is_active' => true,
            'show_in_pos' => true,
            'show_in_menu' => true,
        ]);

        $this->assertEquals('main-course-items', $category->slug);
    }

    public function test_slug_is_not_overwritten_if_provided(): void
    {
        $tenant = Tenant::factory()->create();
        $category = ProductCategory::create([
            'tenant_id' => $tenant->id,
            'code' => 'CAT-001',
            'name' => 'Main Course Items',
            'slug' => 'custom-slug',
            'is_active' => true,
            'show_in_pos' => true,
            'show_in_menu' => true,
        ]);

        $this->assertEquals('custom-slug', $category->slug);
    }

    // ==================== RELATIONSHIP TESTS ====================

    public function test_category_belongs_to_tenant(): void
    {
        $tenant = Tenant::factory()->create();
        $category = ProductCategory::factory()->create(['tenant_id' => $tenant->id]);

        $this->assertInstanceOf(Tenant::class, $category->tenant);
        $this->assertEquals($tenant->id, $category->tenant->id);
    }

    public function test_category_can_have_parent(): void
    {
        $tenant = Tenant::factory()->create();
        $parent = ProductCategory::factory()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Makanan',
        ]);
        $child = ProductCategory::factory()->create([
            'tenant_id' => $tenant->id,
            'parent_id' => $parent->id,
            'name' => 'Nasi',
        ]);

        $this->assertInstanceOf(ProductCategory::class, $child->parent);
        $this->assertEquals($parent->id, $child->parent->id);
    }

    public function test_category_can_have_children(): void
    {
        $tenant = Tenant::factory()->create();
        $parent = ProductCategory::factory()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Minuman',
        ]);

        ProductCategory::factory()->count(3)->create([
            'tenant_id' => $tenant->id,
            'parent_id' => $parent->id,
        ]);

        $this->assertCount(3, $parent->children);
    }

    public function test_children_are_ordered_by_sort_order(): void
    {
        $tenant = Tenant::factory()->create();
        $parent = ProductCategory::factory()->create(['tenant_id' => $tenant->id]);

        $child3 = ProductCategory::factory()->create([
            'tenant_id' => $tenant->id,
            'parent_id' => $parent->id,
            'name' => 'Third',
            'sort_order' => 3,
        ]);
        $child1 = ProductCategory::factory()->create([
            'tenant_id' => $tenant->id,
            'parent_id' => $parent->id,
            'name' => 'First',
            'sort_order' => 1,
        ]);
        $child2 = ProductCategory::factory()->create([
            'tenant_id' => $tenant->id,
            'parent_id' => $parent->id,
            'name' => 'Second',
            'sort_order' => 2,
        ]);

        $children = $parent->children;
        $this->assertEquals('First', $children[0]->name);
        $this->assertEquals('Second', $children[1]->name);
        $this->assertEquals('Third', $children[2]->name);
    }

    public function test_category_can_have_products(): void
    {
        $tenant = Tenant::factory()->create();
        $category = ProductCategory::factory()->create(['tenant_id' => $tenant->id]);

        Product::factory()->count(5)->create([
            'tenant_id' => $tenant->id,
            'category_id' => $category->id,
        ]);

        $this->assertCount(5, $category->products);
    }

    public function test_active_products_only_returns_active_products(): void
    {
        $tenant = Tenant::factory()->create();
        $category = ProductCategory::factory()->create(['tenant_id' => $tenant->id]);

        Product::factory()->count(3)->create([
            'tenant_id' => $tenant->id,
            'category_id' => $category->id,
            'is_active' => true,
        ]);
        Product::factory()->count(2)->create([
            'tenant_id' => $tenant->id,
            'category_id' => $category->id,
            'is_active' => false,
        ]);

        $this->assertCount(5, $category->products);
        $this->assertCount(3, $category->activeProducts);
    }

    // ==================== SCOPE TESTS ====================

    public function test_scope_active_returns_only_active_categories(): void
    {
        $tenant = Tenant::factory()->create();
        ProductCategory::factory()->count(3)->create([
            'tenant_id' => $tenant->id,
            'is_active' => true,
        ]);
        ProductCategory::factory()->count(2)->inactive()->create([
            'tenant_id' => $tenant->id,
        ]);

        $activeCategories = ProductCategory::where('tenant_id', $tenant->id)->active()->get();
        $this->assertCount(3, $activeCategories);
    }

    public function test_scope_for_pos_returns_only_pos_visible_categories(): void
    {
        $tenant = Tenant::factory()->create();
        ProductCategory::factory()->count(4)->create([
            'tenant_id' => $tenant->id,
            'show_in_pos' => true,
        ]);
        ProductCategory::factory()->count(2)->hiddenFromPos()->create([
            'tenant_id' => $tenant->id,
        ]);

        $posCategories = ProductCategory::where('tenant_id', $tenant->id)->forPos()->get();
        $this->assertCount(4, $posCategories);
    }

    public function test_scope_for_menu_returns_only_menu_visible_categories(): void
    {
        $tenant = Tenant::factory()->create();
        ProductCategory::factory()->count(3)->create([
            'tenant_id' => $tenant->id,
            'show_in_menu' => true,
        ]);
        ProductCategory::factory()->count(3)->hiddenFromMenu()->create([
            'tenant_id' => $tenant->id,
        ]);

        $menuCategories = ProductCategory::where('tenant_id', $tenant->id)->forMenu()->get();
        $this->assertCount(3, $menuCategories);
    }

    public function test_scope_roots_returns_only_root_categories(): void
    {
        $tenant = Tenant::factory()->create();
        $root1 = ProductCategory::factory()->create([
            'tenant_id' => $tenant->id,
            'parent_id' => null,
        ]);
        $root2 = ProductCategory::factory()->create([
            'tenant_id' => $tenant->id,
            'parent_id' => null,
        ]);
        ProductCategory::factory()->create([
            'tenant_id' => $tenant->id,
            'parent_id' => $root1->id,
        ]);
        ProductCategory::factory()->create([
            'tenant_id' => $tenant->id,
            'parent_id' => $root2->id,
        ]);

        $rootCategories = ProductCategory::where('tenant_id', $tenant->id)->roots()->get();
        $this->assertCount(2, $rootCategories);
    }

    public function test_scopes_can_be_chained(): void
    {
        $tenant = Tenant::factory()->create();
        // Active, visible in POS, root category
        ProductCategory::factory()->count(2)->create([
            'tenant_id' => $tenant->id,
            'is_active' => true,
            'show_in_pos' => true,
            'parent_id' => null,
        ]);
        // Active but hidden from POS
        ProductCategory::factory()->hiddenFromPos()->create([
            'tenant_id' => $tenant->id,
            'is_active' => true,
            'parent_id' => null,
        ]);
        // Inactive but visible in POS
        ProductCategory::factory()->inactive()->create([
            'tenant_id' => $tenant->id,
            'show_in_pos' => true,
            'parent_id' => null,
        ]);

        $result = ProductCategory::where('tenant_id', $tenant->id)
            ->active()
            ->forPos()
            ->roots()
            ->get();

        $this->assertCount(2, $result);
    }

    // ==================== FULL PATH ATTRIBUTE TESTS ====================

    public function test_full_path_returns_name_for_root_category(): void
    {
        $category = ProductCategory::factory()->create([
            'name' => 'Makanan',
            'parent_id' => null,
        ]);

        $this->assertEquals('Makanan', $category->fullPath);
    }

    public function test_full_path_returns_parent_and_child_names(): void
    {
        $tenant = Tenant::factory()->create();
        $parent = ProductCategory::factory()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Makanan',
            'parent_id' => null,
        ]);
        $child = ProductCategory::factory()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Nasi',
            'parent_id' => $parent->id,
        ]);

        $this->assertEquals('Makanan > Nasi', $child->fullPath);
    }

    public function test_full_path_returns_full_hierarchy(): void
    {
        $tenant = Tenant::factory()->create();
        $level1 = ProductCategory::factory()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Makanan',
            'parent_id' => null,
        ]);
        $level2 = ProductCategory::factory()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Nasi',
            'parent_id' => $level1->id,
        ]);
        $level3 = ProductCategory::factory()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Nasi Goreng',
            'parent_id' => $level2->id,
        ]);

        $this->assertEquals('Makanan > Nasi > Nasi Goreng', $level3->fullPath);
    }

    // ==================== CASTING TESTS ====================

    public function test_sort_order_is_cast_to_integer(): void
    {
        $category = ProductCategory::factory()->create(['sort_order' => '5']);
        $this->assertIsInt($category->sort_order);
        $this->assertEquals(5, $category->sort_order);
    }

    public function test_is_active_is_cast_to_boolean(): void
    {
        $category = ProductCategory::factory()->create(['is_active' => 1]);
        $this->assertIsBool($category->is_active);
        $this->assertTrue($category->is_active);
    }

    public function test_show_in_pos_is_cast_to_boolean(): void
    {
        $category = ProductCategory::factory()->create(['show_in_pos' => 0]);
        $this->assertIsBool($category->show_in_pos);
        $this->assertFalse($category->show_in_pos);
    }

    public function test_show_in_menu_is_cast_to_boolean(): void
    {
        $category = ProductCategory::factory()->create(['show_in_menu' => 1]);
        $this->assertIsBool($category->show_in_menu);
        $this->assertTrue($category->show_in_menu);
    }

    // ==================== FACTORY STATE TESTS ====================

    public function test_factory_inactive_state(): void
    {
        $category = ProductCategory::factory()->inactive()->create();
        $this->assertFalse($category->is_active);
    }

    public function test_factory_hidden_from_pos_state(): void
    {
        $category = ProductCategory::factory()->hiddenFromPos()->create();
        $this->assertFalse($category->show_in_pos);
    }

    public function test_factory_hidden_from_menu_state(): void
    {
        $category = ProductCategory::factory()->hiddenFromMenu()->create();
        $this->assertFalse($category->show_in_menu);
    }

    public function test_factory_with_parent_state(): void
    {
        $tenant = Tenant::factory()->create();
        $parent = ProductCategory::factory()->create(['tenant_id' => $tenant->id]);
        $child = ProductCategory::factory()->withParent($parent)->create();

        $this->assertEquals($parent->id, $child->parent_id);
        $this->assertEquals($parent->tenant_id, $child->tenant_id);
    }

    // ==================== HIERARCHY TESTS ====================

    public function test_root_category_has_no_parent(): void
    {
        $category = ProductCategory::factory()->create(['parent_id' => null]);
        $this->assertNull($category->parent);
    }

    public function test_can_create_deep_hierarchy(): void
    {
        $tenant = Tenant::factory()->create();
        $level1 = ProductCategory::factory()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Level 1',
            'parent_id' => null,
        ]);
        $level2 = ProductCategory::factory()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Level 2',
            'parent_id' => $level1->id,
        ]);
        $level3 = ProductCategory::factory()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Level 3',
            'parent_id' => $level2->id,
        ]);
        $level4 = ProductCategory::factory()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Level 4',
            'parent_id' => $level3->id,
        ]);

        $this->assertNull($level1->parent);
        $this->assertEquals($level1->id, $level2->parent->id);
        $this->assertEquals($level2->id, $level3->parent->id);
        $this->assertEquals($level3->id, $level4->parent->id);
    }

    public function test_deleting_parent_sets_child_parent_id_to_null(): void
    {
        $tenant = Tenant::factory()->create();
        $parent = ProductCategory::factory()->create(['tenant_id' => $tenant->id]);
        $childId = ProductCategory::factory()->create([
            'tenant_id' => $tenant->id,
            'parent_id' => $parent->id,
        ])->id;

        $parent->delete();

        $child = ProductCategory::find($childId);
        $this->assertNotNull($child);
        // Parent ID is set to null due to nullOnDelete constraint
        $this->assertNull($child->parent_id);
        $this->assertNull($child->parent);
    }

    // ==================== TENANT ISOLATION TESTS ====================

    public function test_categories_are_isolated_by_tenant(): void
    {
        $tenant1 = Tenant::factory()->create();
        $tenant2 = Tenant::factory()->create();

        ProductCategory::factory()->count(3)->create(['tenant_id' => $tenant1->id]);
        ProductCategory::factory()->count(2)->create(['tenant_id' => $tenant2->id]);

        $tenant1Categories = ProductCategory::where('tenant_id', $tenant1->id)->get();
        $tenant2Categories = ProductCategory::where('tenant_id', $tenant2->id)->get();

        $this->assertCount(3, $tenant1Categories);
        $this->assertCount(2, $tenant2Categories);
    }

    // ==================== UUID TRAIT TESTS ====================

    public function test_category_uses_uuid(): void
    {
        $category = ProductCategory::factory()->create();
        $this->assertNotNull($category->id);
        $this->assertIsString($category->id);
        // UUID format check
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/',
            $category->id
        );
    }
}
