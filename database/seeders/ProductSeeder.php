<?php

namespace Database\Seeders;

use App\Models\ModifierGroup;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Tenant;
use App\Models\VariantGroup;
use App\Services\Menu\ProductService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $tenant = Tenant::first();

        if (! $tenant) {
            return;
        }

        // Get categories
        $hotCoffee = ProductCategory::where('code', 'HOT-COF')->first();
        $icedCoffee = ProductCategory::where('code', 'ICE-COF')->first();
        $espresso = ProductCategory::where('code', 'ESP')->first();
        $tea = ProductCategory::where('code', 'TEA')->first();
        $chocolate = ProductCategory::where('code', 'CHOCO')->first();
        $smoothies = ProductCategory::where('code', 'SMOOTH')->first();
        $pastries = ProductCategory::where('code', 'PASTRY')->first();
        $cakes = ProductCategory::where('code', 'CAKE')->first();
        $sandwiches = ProductCategory::where('code', 'SAND')->first();

        // Get variant and modifier groups
        $sizeGroup = VariantGroup::where('name', 'Size')->first();
        $iceLevelGroup = VariantGroup::where('name', 'Ice Level')->first();
        $sugarGroup = VariantGroup::where('name', 'Sugar Level')->first();
        $milkGroup = VariantGroup::where('name', 'Milk Type')->first();
        $tempGroup = VariantGroup::where('name', 'Temperature')->first();

        $extraShotsGroup = ModifierGroup::where('name', 'Extra Shots')->first();
        $toppingsGroup = ModifierGroup::where('name', 'Toppings')->first();
        $sauceGroup = ModifierGroup::where('name', 'Sauce')->first();
        $addonsGroup = ModifierGroup::where('name', 'Extra Add-ons (Food)')->first();

        $products = [
            // Hot Coffee
            [
                'name' => 'Americano',
                'sku' => 'HOT-AME-001',
                'category_id' => $hotCoffee?->id,
                'description' => 'Classic espresso with hot water',
                'base_price' => 28000,
                'cost_price' => 8000,
                'product_type' => 'variant',
                'is_active' => true,
                'is_featured' => true,
                'variant_groups' => [$sizeGroup?->id, $sugarGroup?->id],
                'modifier_groups' => [$extraShotsGroup?->id],
            ],
            [
                'name' => 'Cappuccino',
                'sku' => 'HOT-CAP-001',
                'category_id' => $hotCoffee?->id,
                'description' => 'Espresso with steamed milk foam',
                'base_price' => 32000,
                'cost_price' => 10000,
                'product_type' => 'variant',
                'is_active' => true,
                'is_featured' => true,
                'variant_groups' => [$sizeGroup?->id, $sugarGroup?->id, $milkGroup?->id],
                'modifier_groups' => [$extraShotsGroup?->id, $toppingsGroup?->id],
            ],
            [
                'name' => 'Cafe Latte',
                'sku' => 'HOT-LAT-001',
                'category_id' => $hotCoffee?->id,
                'description' => 'Espresso with steamed milk',
                'base_price' => 32000,
                'cost_price' => 10000,
                'product_type' => 'variant',
                'is_active' => true,
                'variant_groups' => [$sizeGroup?->id, $sugarGroup?->id, $milkGroup?->id],
                'modifier_groups' => [$extraShotsGroup?->id, $toppingsGroup?->id],
            ],

            // Iced Coffee
            [
                'name' => 'Iced Americano',
                'sku' => 'ICE-AME-001',
                'category_id' => $icedCoffee?->id,
                'description' => 'Classic espresso with cold water over ice',
                'base_price' => 30000,
                'cost_price' => 9000,
                'product_type' => 'variant',
                'is_active' => true,
                'is_featured' => true,
                'variant_groups' => [$sizeGroup?->id, $iceLevelGroup?->id, $sugarGroup?->id],
                'modifier_groups' => [$extraShotsGroup?->id],
            ],
            [
                'name' => 'Iced Latte',
                'sku' => 'ICE-LAT-001',
                'category_id' => $icedCoffee?->id,
                'description' => 'Espresso with cold milk over ice',
                'base_price' => 35000,
                'cost_price' => 11000,
                'product_type' => 'variant',
                'is_active' => true,
                'is_featured' => true,
                'variant_groups' => [$sizeGroup?->id, $iceLevelGroup?->id, $sugarGroup?->id, $milkGroup?->id],
                'modifier_groups' => [$extraShotsGroup?->id, $toppingsGroup?->id],
            ],
            [
                'name' => 'Cold Brew',
                'sku' => 'ICE-CB-001',
                'category_id' => $icedCoffee?->id,
                'description' => 'Slow-steeped cold brew coffee',
                'base_price' => 38000,
                'cost_price' => 12000,
                'product_type' => 'variant',
                'is_active' => true,
                'variant_groups' => [$sizeGroup?->id, $iceLevelGroup?->id, $sugarGroup?->id],
                'modifier_groups' => [$toppingsGroup?->id],
            ],

            // Espresso Based
            [
                'name' => 'Espresso',
                'sku' => 'ESP-001',
                'category_id' => $espresso?->id,
                'description' => 'Pure espresso shot',
                'base_price' => 18000,
                'cost_price' => 5000,
                'product_type' => 'single',
                'is_active' => true,
            ],
            [
                'name' => 'Macchiato',
                'sku' => 'ESP-MAC-001',
                'category_id' => $espresso?->id,
                'description' => 'Espresso with a dollop of foam',
                'base_price' => 25000,
                'cost_price' => 7000,
                'product_type' => 'variant',
                'is_active' => true,
                'variant_groups' => [$tempGroup?->id],
            ],

            // Tea
            [
                'name' => 'Earl Grey',
                'sku' => 'TEA-EG-001',
                'category_id' => $tea?->id,
                'description' => 'Classic bergamot-flavored black tea',
                'base_price' => 25000,
                'cost_price' => 6000,
                'product_type' => 'variant',
                'is_active' => true,
                'variant_groups' => [$sizeGroup?->id, $tempGroup?->id, $sugarGroup?->id],
            ],
            [
                'name' => 'Green Tea Latte',
                'sku' => 'TEA-GTL-001',
                'category_id' => $tea?->id,
                'description' => 'Japanese matcha with milk',
                'base_price' => 35000,
                'cost_price' => 12000,
                'product_type' => 'variant',
                'is_active' => true,
                'is_featured' => true,
                'variant_groups' => [$sizeGroup?->id, $tempGroup?->id, $sugarGroup?->id, $milkGroup?->id],
                'modifier_groups' => [$toppingsGroup?->id],
            ],
            [
                'name' => 'Chai Latte',
                'sku' => 'TEA-CL-001',
                'category_id' => $tea?->id,
                'description' => 'Spiced tea with steamed milk',
                'base_price' => 32000,
                'cost_price' => 10000,
                'product_type' => 'variant',
                'is_active' => true,
                'variant_groups' => [$sizeGroup?->id, $tempGroup?->id, $sugarGroup?->id, $milkGroup?->id],
            ],

            // Chocolate
            [
                'name' => 'Hot Chocolate',
                'sku' => 'CHOCO-HOT-001',
                'category_id' => $chocolate?->id,
                'description' => 'Rich Belgian chocolate',
                'base_price' => 30000,
                'cost_price' => 9000,
                'product_type' => 'variant',
                'is_active' => true,
                'variant_groups' => [$sizeGroup?->id, $sugarGroup?->id, $milkGroup?->id],
                'modifier_groups' => [$toppingsGroup?->id],
            ],
            [
                'name' => 'Iced Chocolate',
                'sku' => 'CHOCO-ICE-001',
                'category_id' => $chocolate?->id,
                'description' => 'Chilled chocolate drink',
                'base_price' => 32000,
                'cost_price' => 10000,
                'product_type' => 'variant',
                'is_active' => true,
                'variant_groups' => [$sizeGroup?->id, $iceLevelGroup?->id, $sugarGroup?->id, $milkGroup?->id],
                'modifier_groups' => [$toppingsGroup?->id],
            ],

            // Smoothies
            [
                'name' => 'Berry Blast Smoothie',
                'sku' => 'SM-BERRY-001',
                'category_id' => $smoothies?->id,
                'description' => 'Mixed berries blended smooth',
                'base_price' => 38000,
                'cost_price' => 15000,
                'product_type' => 'variant',
                'is_active' => true,
                'variant_groups' => [$sizeGroup?->id],
                'modifier_groups' => [$toppingsGroup?->id],
            ],
            [
                'name' => 'Mango Smoothie',
                'sku' => 'SM-MANGO-001',
                'category_id' => $smoothies?->id,
                'description' => 'Fresh mango blended with yogurt',
                'base_price' => 35000,
                'cost_price' => 13000,
                'product_type' => 'variant',
                'is_active' => true,
                'variant_groups' => [$sizeGroup?->id],
            ],

            // Pastries
            [
                'name' => 'Croissant',
                'sku' => 'PASTRY-CR-001',
                'category_id' => $pastries?->id,
                'description' => 'Buttery French croissant',
                'base_price' => 22000,
                'cost_price' => 8000,
                'product_type' => 'single',
                'is_active' => true,
            ],
            [
                'name' => 'Pain au Chocolat',
                'sku' => 'PASTRY-PC-001',
                'category_id' => $pastries?->id,
                'description' => 'Chocolate-filled croissant',
                'base_price' => 28000,
                'cost_price' => 10000,
                'product_type' => 'single',
                'is_active' => true,
                'is_featured' => true,
            ],
            [
                'name' => 'Cinnamon Roll',
                'sku' => 'PASTRY-CIN-001',
                'category_id' => $pastries?->id,
                'description' => 'Sweet cinnamon swirl',
                'base_price' => 25000,
                'cost_price' => 9000,
                'product_type' => 'single',
                'is_active' => true,
            ],

            // Cakes
            [
                'name' => 'Cheesecake',
                'sku' => 'CAKE-CHE-001',
                'category_id' => $cakes?->id,
                'description' => 'New York style cheesecake',
                'base_price' => 45000,
                'cost_price' => 18000,
                'product_type' => 'single',
                'is_active' => true,
                'is_featured' => true,
            ],
            [
                'name' => 'Chocolate Brownie',
                'sku' => 'CAKE-BRO-001',
                'category_id' => $cakes?->id,
                'description' => 'Rich fudgy brownie',
                'base_price' => 28000,
                'cost_price' => 10000,
                'product_type' => 'single',
                'is_active' => true,
                'modifier_groups' => [$toppingsGroup?->id],
            ],
            [
                'name' => 'Tiramisu',
                'sku' => 'CAKE-TIR-001',
                'category_id' => $cakes?->id,
                'description' => 'Italian coffee-flavored dessert',
                'base_price' => 48000,
                'cost_price' => 20000,
                'product_type' => 'single',
                'is_active' => true,
            ],

            // Sandwiches
            [
                'name' => 'Club Sandwich',
                'sku' => 'SAND-CLB-001',
                'category_id' => $sandwiches?->id,
                'description' => 'Triple-decker chicken club',
                'base_price' => 55000,
                'cost_price' => 22000,
                'product_type' => 'single',
                'is_active' => true,
                'is_featured' => true,
                'modifier_groups' => [$sauceGroup?->id, $addonsGroup?->id],
            ],
            [
                'name' => 'Grilled Cheese',
                'sku' => 'SAND-GC-001',
                'category_id' => $sandwiches?->id,
                'description' => 'Classic melted cheese sandwich',
                'base_price' => 35000,
                'cost_price' => 12000,
                'product_type' => 'single',
                'is_active' => true,
                'modifier_groups' => [$addonsGroup?->id],
            ],
            [
                'name' => 'Tuna Melt',
                'sku' => 'SAND-TUN-001',
                'category_id' => $sandwiches?->id,
                'description' => 'Tuna salad with melted cheese',
                'base_price' => 48000,
                'cost_price' => 18000,
                'product_type' => 'single',
                'is_active' => true,
                'modifier_groups' => [$sauceGroup?->id],
            ],
        ];

        $productService = app(ProductService::class);

        foreach ($products as $productData) {
            $variantGroups = $productData['variant_groups'] ?? [];
            $modifierGroups = $productData['modifier_groups'] ?? [];
            unset($productData['variant_groups'], $productData['modifier_groups']);

            // Filter out null values from groups
            $variantGroups = array_filter($variantGroups);
            $modifierGroups = array_filter($modifierGroups);

            $product = Product::create([
                'tenant_id' => $tenant->id,
                'slug' => Str::slug($productData['name']),
                'sort_order' => 0,
                ...$productData,
            ]);

            // Attach variant groups with UUID
            foreach ($variantGroups as $index => $groupId) {
                $product->variantGroups()->attach($groupId, [
                    'id' => Str::uuid(),
                    'is_required' => true,
                    'sort_order' => $index,
                ]);
            }

            // Attach modifier groups with UUID
            foreach ($modifierGroups as $index => $groupId) {
                $product->modifierGroups()->attach($groupId, [
                    'id' => Str::uuid(),
                    'is_required' => false,
                    'min_selections' => 0,
                    'max_selections' => null,
                    'sort_order' => $index,
                ]);
            }

            // Generate variants if it's a variant product
            if ($product->product_type === Product::TYPE_VARIANT && count($variantGroups) > 0) {
                $productService->generateVariants($product);
            }
        }
    }
}
