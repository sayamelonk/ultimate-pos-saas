<?php

namespace Database\Seeders;

use App\Models\Combo;
use App\Models\ComboItem;
use App\Models\Modifier;
use App\Models\ModifierGroup;
use App\Models\Outlet;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductOutlet;
use App\Models\Tenant;
use App\Models\VariantGroup;
use App\Models\VariantOption;
use App\Services\Menu\ProductService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DemoRestaurantProductSeeder extends Seeder
{
    private Tenant $tenant;

    private Outlet $outlet;

    /** @var array<string, ProductCategory> */
    private array $categories = [];

    /** @var array<string, VariantGroup> */
    private array $variantGroups = [];

    /** @var array<string, ModifierGroup> */
    private array $modifierGroups = [];

    /** @var array<string, Product> */
    private array $products = [];

    public function run(): void
    {
        $this->tenant = Tenant::where('name', 'Restaurant Professional')->firstOrFail();
        $this->outlet = Outlet::where('tenant_id', $this->tenant->id)->firstOrFail();

        $this->command->info("Seeding products for: {$this->tenant->name} / {$this->outlet->name}");

        $this->seedCategories();
        $this->seedVariantGroups();
        $this->seedModifierGroups();
        $this->seedSingleProducts();
        $this->seedVariantProducts();
        $this->seedComboProducts();
        $this->assignAllProductsToOutlet();

        $productCount = Product::where('tenant_id', $this->tenant->id)->count();
        $this->command->info("Done! Total products: {$productCount}");
    }

    private function seedCategories(): void
    {
        $data = [
            [
                'name' => 'Appetizers',
                'code' => 'APP',
                'color' => '#FF6B35',
                'icon' => 'flame',
                'description' => 'Starter dishes to begin your meal',
                'sort_order' => 1,
                'children' => [
                    ['name' => 'Soups', 'code' => 'SOUP', 'color' => '#E8A87C', 'sort_order' => 1],
                    ['name' => 'Salads', 'code' => 'SALAD', 'color' => '#85CDCA', 'sort_order' => 2],
                    ['name' => 'Finger Food', 'code' => 'FINGER', 'color' => '#D4A574', 'sort_order' => 3],
                ],
            ],
            [
                'name' => 'Main Course',
                'code' => 'MAIN',
                'color' => '#C44536',
                'icon' => 'utensils',
                'description' => 'Signature main dishes',
                'sort_order' => 2,
                'children' => [
                    ['name' => 'Steak & Grill', 'code' => 'STEAK', 'color' => '#8B0000', 'sort_order' => 1],
                    ['name' => 'Pasta', 'code' => 'PASTA', 'color' => '#DAA520', 'sort_order' => 2],
                    ['name' => 'Rice & Noodle', 'code' => 'RICE', 'color' => '#F5DEB3', 'sort_order' => 3],
                    ['name' => 'Seafood', 'code' => 'SEAFOOD', 'color' => '#4682B4', 'sort_order' => 4],
                    ['name' => 'Chicken', 'code' => 'CHICKEN', 'color' => '#CD853F', 'sort_order' => 5],
                ],
            ],
            [
                'name' => 'Desserts',
                'code' => 'DESSERT',
                'color' => '#E8A0BF',
                'icon' => 'cake',
                'description' => 'Sweet treats to end your meal',
                'sort_order' => 3,
            ],
            [
                'name' => 'Beverages',
                'code' => 'BEV',
                'color' => '#3B82F6',
                'icon' => 'coffee',
                'description' => 'Drinks and refreshments',
                'sort_order' => 4,
                'children' => [
                    ['name' => 'Hot Drinks', 'code' => 'HOT', 'color' => '#B91C1C', 'sort_order' => 1],
                    ['name' => 'Cold Drinks', 'code' => 'COLD', 'color' => '#0EA5E9', 'sort_order' => 2],
                    ['name' => 'Fresh Juices', 'code' => 'JUICE', 'color' => '#F97316', 'sort_order' => 3],
                ],
            ],
            [
                'name' => 'Sides',
                'code' => 'SIDE',
                'color' => '#A3A830',
                'icon' => 'layers',
                'description' => 'Side dishes and extras',
                'sort_order' => 5,
            ],
        ];

        foreach ($data as $catData) {
            $children = $catData['children'] ?? [];
            unset($catData['children']);

            $parent = ProductCategory::create([
                'tenant_id' => $this->tenant->id,
                'is_active' => true,
                'show_in_pos' => true,
                'show_in_menu' => true,
                ...$catData,
            ]);
            $this->categories[$parent->code] = $parent;

            foreach ($children as $childData) {
                $child = ProductCategory::create([
                    'tenant_id' => $this->tenant->id,
                    'parent_id' => $parent->id,
                    'icon' => $parent->icon,
                    'description' => null,
                    'is_active' => true,
                    'show_in_pos' => true,
                    'show_in_menu' => true,
                    ...$childData,
                ]);
                $this->categories[$child->code] = $child;
            }
        }

        $this->command->info('  Categories: '.count($this->categories));
    }

    private function seedVariantGroups(): void
    {
        $data = [
            [
                'name' => 'Size',
                'description' => 'Portion size',
                'display_type' => 'button',
                'sort_order' => 1,
                'options' => [
                    ['name' => 'Regular', 'price_adjustment' => 0, 'sort_order' => 0],
                    ['name' => 'Large', 'price_adjustment' => 15000, 'sort_order' => 1],
                ],
            ],
            [
                'name' => 'Spice Level',
                'description' => 'How spicy do you want it',
                'display_type' => 'button',
                'sort_order' => 2,
                'options' => [
                    ['name' => 'Mild', 'price_adjustment' => 0, 'sort_order' => 0],
                    ['name' => 'Medium', 'price_adjustment' => 0, 'sort_order' => 1],
                    ['name' => 'Spicy', 'price_adjustment' => 0, 'sort_order' => 2],
                    ['name' => 'Extra Spicy', 'price_adjustment' => 0, 'sort_order' => 3],
                ],
            ],
            [
                'name' => 'Doneness',
                'description' => 'Steak cooking temperature',
                'display_type' => 'button',
                'sort_order' => 3,
                'options' => [
                    ['name' => 'Rare', 'price_adjustment' => 0, 'sort_order' => 0],
                    ['name' => 'Medium Rare', 'price_adjustment' => 0, 'sort_order' => 1],
                    ['name' => 'Medium', 'price_adjustment' => 0, 'sort_order' => 2],
                    ['name' => 'Medium Well', 'price_adjustment' => 0, 'sort_order' => 3],
                    ['name' => 'Well Done', 'price_adjustment' => 0, 'sort_order' => 4],
                ],
            ],
            [
                'name' => 'Pasta Type',
                'description' => 'Choose your pasta',
                'display_type' => 'button',
                'sort_order' => 4,
                'options' => [
                    ['name' => 'Spaghetti', 'price_adjustment' => 0, 'sort_order' => 0],
                    ['name' => 'Penne', 'price_adjustment' => 0, 'sort_order' => 1],
                    ['name' => 'Fettuccine', 'price_adjustment' => 0, 'sort_order' => 2],
                    ['name' => 'Linguine', 'price_adjustment' => 5000, 'sort_order' => 3],
                ],
            ],
            [
                'name' => 'Drink Size',
                'description' => 'Beverage size',
                'display_type' => 'button',
                'sort_order' => 5,
                'options' => [
                    ['name' => 'Small', 'price_adjustment' => 0, 'sort_order' => 0],
                    ['name' => 'Medium', 'price_adjustment' => 5000, 'sort_order' => 1],
                    ['name' => 'Large', 'price_adjustment' => 10000, 'sort_order' => 2],
                ],
            ],
            [
                'name' => 'Temperature',
                'description' => 'Hot or iced',
                'display_type' => 'button',
                'sort_order' => 6,
                'options' => [
                    ['name' => 'Hot', 'price_adjustment' => 0, 'sort_order' => 0],
                    ['name' => 'Iced', 'price_adjustment' => 3000, 'sort_order' => 1],
                ],
            ],
        ];

        foreach ($data as $groupData) {
            $options = $groupData['options'];
            unset($groupData['options']);

            $group = VariantGroup::create([
                'tenant_id' => $this->tenant->id,
                'is_active' => true,
                ...$groupData,
            ]);
            $this->variantGroups[$group->name] = $group;

            foreach ($options as $optionData) {
                VariantOption::create([
                    'variant_group_id' => $group->id,
                    'is_active' => true,
                    ...$optionData,
                ]);
            }
        }

        $this->command->info('  Variant Groups: '.count($this->variantGroups));
    }

    private function seedModifierGroups(): void
    {
        $data = [
            [
                'name' => 'Steak Sauce',
                'description' => 'Choose your steak sauce',
                'selection_type' => 'single',
                'min_selections' => 0,
                'max_selections' => 1,
                'sort_order' => 1,
                'modifiers' => [
                    ['name' => 'Black Pepper Sauce', 'price' => 0, 'sort_order' => 1],
                    ['name' => 'Mushroom Sauce', 'price' => 5000, 'sort_order' => 2],
                    ['name' => 'BBQ Sauce', 'price' => 0, 'sort_order' => 3],
                    ['name' => 'Garlic Butter', 'price' => 5000, 'sort_order' => 4],
                ],
            ],
            [
                'name' => 'Side Dish',
                'description' => 'Choose a side for your main',
                'selection_type' => 'single',
                'min_selections' => 1,
                'max_selections' => 1,
                'sort_order' => 2,
                'modifiers' => [
                    ['name' => 'French Fries', 'price' => 0, 'sort_order' => 1],
                    ['name' => 'Mashed Potato', 'price' => 0, 'sort_order' => 2],
                    ['name' => 'Steamed Rice', 'price' => 0, 'sort_order' => 3],
                    ['name' => 'Garden Salad', 'price' => 0, 'sort_order' => 4],
                    ['name' => 'Garlic Bread', 'price' => 5000, 'sort_order' => 5],
                ],
            ],
            [
                'name' => 'Extra Topping',
                'description' => 'Add extra toppings',
                'selection_type' => 'multiple',
                'min_selections' => 0,
                'max_selections' => 5,
                'sort_order' => 3,
                'modifiers' => [
                    ['name' => 'Extra Cheese', 'price' => 8000, 'sort_order' => 1],
                    ['name' => 'Bacon Bits', 'price' => 12000, 'sort_order' => 2],
                    ['name' => 'Fried Egg', 'price' => 5000, 'sort_order' => 3],
                    ['name' => 'Mushrooms', 'price' => 8000, 'sort_order' => 4],
                    ['name' => 'Avocado', 'price' => 15000, 'sort_order' => 5],
                ],
            ],
            [
                'name' => 'Dressing',
                'description' => 'Salad dressing choice',
                'selection_type' => 'single',
                'min_selections' => 0,
                'max_selections' => 1,
                'sort_order' => 4,
                'modifiers' => [
                    ['name' => 'Caesar Dressing', 'price' => 0, 'sort_order' => 1],
                    ['name' => 'Balsamic Vinaigrette', 'price' => 0, 'sort_order' => 2],
                    ['name' => 'Thousand Island', 'price' => 0, 'sort_order' => 3],
                    ['name' => 'Honey Mustard', 'price' => 0, 'sort_order' => 4],
                ],
            ],
            [
                'name' => 'Pasta Add-on',
                'description' => 'Add protein to your pasta',
                'selection_type' => 'multiple',
                'min_selections' => 0,
                'max_selections' => 3,
                'sort_order' => 5,
                'modifiers' => [
                    ['name' => 'Grilled Chicken', 'price' => 15000, 'sort_order' => 1],
                    ['name' => 'Smoked Beef', 'price' => 18000, 'sort_order' => 2],
                    ['name' => 'Shrimp (5 pcs)', 'price' => 20000, 'sort_order' => 3],
                    ['name' => 'Extra Parmesan', 'price' => 8000, 'sort_order' => 4],
                ],
            ],
        ];

        foreach ($data as $groupData) {
            $modifiers = $groupData['modifiers'];
            unset($groupData['modifiers']);

            $group = ModifierGroup::create([
                'tenant_id' => $this->tenant->id,
                'is_active' => true,
                ...$groupData,
            ]);
            $this->modifierGroups[$group->name] = $group;

            foreach ($modifiers as $modData) {
                Modifier::create([
                    'modifier_group_id' => $group->id,
                    'is_active' => true,
                    ...$modData,
                ]);
            }
        }

        $this->command->info('  Modifier Groups: '.count($this->modifierGroups));
    }

    private function seedSingleProducts(): void
    {
        $singles = [
            // Soups
            ['name' => 'Mushroom Cream Soup', 'sku' => 'SOUP-001', 'category' => 'SOUP', 'base_price' => 35000, 'cost_price' => 12000, 'description' => 'Creamy mushroom soup with croutons', 'is_featured' => true],
            ['name' => 'Tomato Basil Soup', 'sku' => 'SOUP-002', 'category' => 'SOUP', 'base_price' => 32000, 'cost_price' => 10000, 'description' => 'Classic tomato soup with fresh basil'],
            ['name' => 'French Onion Soup', 'sku' => 'SOUP-003', 'category' => 'SOUP', 'base_price' => 38000, 'cost_price' => 13000, 'description' => 'Caramelized onion soup with melted gruyere'],

            // Salads
            ['name' => 'Caesar Salad', 'sku' => 'SALAD-001', 'category' => 'SALAD', 'base_price' => 45000, 'cost_price' => 15000, 'description' => 'Romaine lettuce with caesar dressing and parmesan', 'is_featured' => true, 'modifiers' => ['Dressing', 'Extra Topping']],
            ['name' => 'Greek Salad', 'sku' => 'SALAD-002', 'category' => 'SALAD', 'base_price' => 42000, 'cost_price' => 14000, 'description' => 'Fresh vegetables with feta cheese and olives', 'modifiers' => ['Dressing']],

            // Finger Food
            ['name' => 'Chicken Wings (6 pcs)', 'sku' => 'FINGER-001', 'category' => 'FINGER', 'base_price' => 55000, 'cost_price' => 20000, 'description' => 'Crispy fried chicken wings with dipping sauce', 'is_featured' => true],
            ['name' => 'French Fries', 'sku' => 'FINGER-002', 'category' => 'FINGER', 'base_price' => 28000, 'cost_price' => 8000, 'description' => 'Golden crispy french fries', 'modifiers' => ['Extra Topping']],
            ['name' => 'Onion Rings', 'sku' => 'FINGER-003', 'category' => 'FINGER', 'base_price' => 32000, 'cost_price' => 10000, 'description' => 'Beer-battered onion rings'],
            ['name' => 'Spring Rolls (4 pcs)', 'sku' => 'FINGER-004', 'category' => 'FINGER', 'base_price' => 35000, 'cost_price' => 12000, 'description' => 'Crispy vegetable spring rolls with sweet chili'],
            ['name' => 'Mozzarella Sticks (5 pcs)', 'sku' => 'FINGER-005', 'category' => 'FINGER', 'base_price' => 42000, 'cost_price' => 15000, 'description' => 'Breaded mozzarella with marinara sauce'],

            // Desserts
            ['name' => 'Chocolate Lava Cake', 'sku' => 'DES-001', 'category' => 'DESSERT', 'base_price' => 48000, 'cost_price' => 18000, 'description' => 'Warm chocolate cake with molten center', 'is_featured' => true],
            ['name' => 'Tiramisu', 'sku' => 'DES-002', 'category' => 'DESSERT', 'base_price' => 45000, 'cost_price' => 16000, 'description' => 'Classic Italian coffee-flavored dessert'],
            ['name' => 'Cheesecake', 'sku' => 'DES-003', 'category' => 'DESSERT', 'base_price' => 45000, 'cost_price' => 16000, 'description' => 'New York style cheesecake with berry sauce'],
            ['name' => 'Creme Brulee', 'sku' => 'DES-004', 'category' => 'DESSERT', 'base_price' => 42000, 'cost_price' => 14000, 'description' => 'French custard with caramelized sugar top'],
            ['name' => 'Ice Cream (2 Scoops)', 'sku' => 'DES-005', 'category' => 'DESSERT', 'base_price' => 30000, 'cost_price' => 10000, 'description' => 'Choice of vanilla, chocolate, or strawberry'],

            // Sides
            ['name' => 'Garlic Bread (3 pcs)', 'sku' => 'SIDE-001', 'category' => 'SIDE', 'base_price' => 22000, 'cost_price' => 7000, 'description' => 'Toasted bread with garlic butter'],
            ['name' => 'Steamed Rice', 'sku' => 'SIDE-002', 'category' => 'SIDE', 'base_price' => 12000, 'cost_price' => 3000, 'description' => 'Steamed jasmine rice'],
            ['name' => 'Mashed Potato', 'sku' => 'SIDE-003', 'category' => 'SIDE', 'base_price' => 18000, 'cost_price' => 6000, 'description' => 'Creamy mashed potato with butter'],
            ['name' => 'Coleslaw', 'sku' => 'SIDE-004', 'category' => 'SIDE', 'base_price' => 15000, 'cost_price' => 5000, 'description' => 'Fresh creamy coleslaw'],
            ['name' => 'Mixed Vegetables', 'sku' => 'SIDE-005', 'category' => 'SIDE', 'base_price' => 18000, 'cost_price' => 6000, 'description' => 'Sauteed seasonal vegetables'],
        ];

        $sortOrder = 0;
        foreach ($singles as $data) {
            $categoryCode = $data['category'];
            $modifiers = $data['modifiers'] ?? [];
            unset($data['category'], $data['modifiers']);

            $product = Product::create([
                'tenant_id' => $this->tenant->id,
                'category_id' => $this->categories[$categoryCode]->id ?? null,
                'slug' => Str::slug($data['name']),
                'product_type' => Product::TYPE_SINGLE,
                'is_active' => true,
                'sort_order' => $sortOrder++,
                ...$data,
            ]);

            $this->attachModifiers($product, $modifiers);
            $this->products[$product->sku] = $product;
        }

        $this->command->info('  Single Products: '.count($singles));
    }

    private function seedVariantProducts(): void
    {
        $productService = app(ProductService::class);

        $variants = [
            // Steak & Grill
            [
                'name' => 'Ribeye Steak',
                'sku' => 'STEAK-001',
                'category' => 'STEAK',
                'base_price' => 185000,
                'cost_price' => 75000,
                'description' => 'Premium 250g Australian ribeye',
                'is_featured' => true,
                'variant_groups' => ['Size', 'Doneness'],
                'modifiers' => ['Steak Sauce', 'Side Dish', 'Extra Topping'],
            ],
            [
                'name' => 'Sirloin Steak',
                'sku' => 'STEAK-002',
                'category' => 'STEAK',
                'base_price' => 145000,
                'cost_price' => 55000,
                'description' => 'Tender 200g sirloin steak',
                'is_featured' => true,
                'variant_groups' => ['Size', 'Doneness'],
                'modifiers' => ['Steak Sauce', 'Side Dish'],
            ],
            [
                'name' => 'Grilled Chicken Steak',
                'sku' => 'STEAK-003',
                'category' => 'STEAK',
                'base_price' => 85000,
                'cost_price' => 30000,
                'description' => 'Marinated grilled chicken breast',
                'variant_groups' => ['Size'],
                'modifiers' => ['Steak Sauce', 'Side Dish'],
            ],

            // Pasta
            [
                'name' => 'Aglio Olio',
                'sku' => 'PASTA-001',
                'category' => 'PASTA',
                'base_price' => 55000,
                'cost_price' => 18000,
                'description' => 'Garlic and chili olive oil pasta',
                'is_featured' => true,
                'variant_groups' => ['Pasta Type', 'Spice Level'],
                'modifiers' => ['Pasta Add-on'],
            ],
            [
                'name' => 'Carbonara',
                'sku' => 'PASTA-002',
                'category' => 'PASTA',
                'base_price' => 65000,
                'cost_price' => 22000,
                'description' => 'Creamy egg and bacon pasta',
                'is_featured' => true,
                'variant_groups' => ['Pasta Type'],
                'modifiers' => ['Pasta Add-on', 'Extra Topping'],
            ],
            [
                'name' => 'Bolognese',
                'sku' => 'PASTA-003',
                'category' => 'PASTA',
                'base_price' => 60000,
                'cost_price' => 20000,
                'description' => 'Rich meat sauce pasta',
                'variant_groups' => ['Pasta Type'],
                'modifiers' => ['Pasta Add-on', 'Extra Topping'],
            ],
            [
                'name' => 'Pesto Pasta',
                'sku' => 'PASTA-004',
                'category' => 'PASTA',
                'base_price' => 58000,
                'cost_price' => 19000,
                'description' => 'Fresh basil pesto with pine nuts',
                'variant_groups' => ['Pasta Type'],
                'modifiers' => ['Pasta Add-on'],
            ],

            // Rice & Noodle
            [
                'name' => 'Nasi Goreng Special',
                'sku' => 'RICE-001',
                'category' => 'RICE',
                'base_price' => 48000,
                'cost_price' => 15000,
                'description' => 'Indonesian fried rice with egg, chicken satay, and crackers',
                'is_featured' => true,
                'variant_groups' => ['Spice Level'],
                'modifiers' => ['Extra Topping'],
            ],
            [
                'name' => 'Mie Goreng',
                'sku' => 'RICE-002',
                'category' => 'RICE',
                'base_price' => 45000,
                'cost_price' => 14000,
                'description' => 'Stir-fried noodles with vegetables and egg',
                'variant_groups' => ['Spice Level'],
                'modifiers' => ['Extra Topping'],
            ],
            [
                'name' => 'Chicken Teriyaki Rice',
                'sku' => 'RICE-003',
                'category' => 'RICE',
                'base_price' => 52000,
                'cost_price' => 18000,
                'description' => 'Grilled chicken with teriyaki sauce over steamed rice',
                'variant_groups' => ['Size'],
            ],

            // Seafood
            [
                'name' => 'Grilled Salmon',
                'sku' => 'SEA-001',
                'category' => 'SEAFOOD',
                'base_price' => 165000,
                'cost_price' => 70000,
                'description' => 'Norwegian salmon fillet with lemon butter sauce',
                'is_featured' => true,
                'variant_groups' => ['Size'],
                'modifiers' => ['Side Dish'],
            ],
            [
                'name' => 'Fish & Chips',
                'sku' => 'SEA-002',
                'category' => 'SEAFOOD',
                'base_price' => 75000,
                'cost_price' => 28000,
                'description' => 'Beer-battered fish with fries and tartar sauce',
                'variant_groups' => ['Size'],
            ],

            // Chicken
            [
                'name' => 'Chicken Cordon Bleu',
                'sku' => 'CHKN-001',
                'category' => 'CHICKEN',
                'base_price' => 78000,
                'cost_price' => 28000,
                'description' => 'Chicken stuffed with ham and cheese',
                'is_featured' => true,
                'variant_groups' => ['Size'],
                'modifiers' => ['Side Dish', 'Steak Sauce'],
            ],
            [
                'name' => 'Chicken Katsu',
                'sku' => 'CHKN-002',
                'category' => 'CHICKEN',
                'base_price' => 65000,
                'cost_price' => 22000,
                'description' => 'Japanese breaded chicken cutlet with tonkatsu sauce',
                'variant_groups' => ['Size'],
                'modifiers' => ['Side Dish'],
            ],

            // Beverages
            [
                'name' => 'Lemon Tea',
                'sku' => 'BEV-001',
                'category' => 'HOT',
                'base_price' => 22000,
                'cost_price' => 6000,
                'description' => 'Fresh lemon tea',
                'variant_groups' => ['Drink Size', 'Temperature'],
            ],
            [
                'name' => 'Cappuccino',
                'sku' => 'BEV-002',
                'category' => 'HOT',
                'base_price' => 32000,
                'cost_price' => 10000,
                'description' => 'Espresso with steamed milk foam',
                'is_featured' => true,
                'variant_groups' => ['Drink Size'],
            ],
            [
                'name' => 'Fresh Orange Juice',
                'sku' => 'JUICE-001',
                'category' => 'JUICE',
                'base_price' => 28000,
                'cost_price' => 10000,
                'description' => 'Freshly squeezed orange juice',
                'variant_groups' => ['Drink Size'],
            ],
            [
                'name' => 'Mango Smoothie',
                'sku' => 'JUICE-002',
                'category' => 'JUICE',
                'base_price' => 35000,
                'cost_price' => 12000,
                'description' => 'Fresh mango blended with yogurt',
                'variant_groups' => ['Drink Size'],
            ],
            [
                'name' => 'Iced Latte',
                'sku' => 'COLD-001',
                'category' => 'COLD',
                'base_price' => 35000,
                'cost_price' => 11000,
                'description' => 'Espresso with cold milk over ice',
                'variant_groups' => ['Drink Size'],
            ],
            [
                'name' => 'Mineral Water',
                'sku' => 'COLD-002',
                'category' => 'COLD',
                'base_price' => 12000,
                'cost_price' => 3000,
                'description' => 'Bottled mineral water',
                'variant_groups' => ['Drink Size'],
            ],
        ];

        $sortOrder = 100;
        foreach ($variants as $data) {
            $categoryCode = $data['category'];
            $variantGroupNames = $data['variant_groups'] ?? [];
            $modifierNames = $data['modifiers'] ?? [];
            unset($data['category'], $data['variant_groups'], $data['modifiers']);

            $product = Product::create([
                'tenant_id' => $this->tenant->id,
                'category_id' => $this->categories[$categoryCode]->id ?? null,
                'slug' => Str::slug($data['name']),
                'product_type' => Product::TYPE_VARIANT,
                'is_active' => true,
                'sort_order' => $sortOrder++,
                ...$data,
            ]);

            // Attach variant groups
            foreach ($variantGroupNames as $index => $groupName) {
                if (isset($this->variantGroups[$groupName])) {
                    $product->variantGroups()->attach($this->variantGroups[$groupName]->id, [
                        'id' => Str::uuid(),
                        'is_required' => true,
                        'sort_order' => $index,
                    ]);
                }
            }

            $this->attachModifiers($product, $modifierNames);

            // Generate variant combinations
            $productService->generateVariants($product);

            $this->products[$product->sku] = $product;
        }

        $this->command->info('  Variant Products: '.count($variants));
    }

    private function seedComboProducts(): void
    {
        $combos = [
            [
                'name' => 'Lunch Set A - Steak',
                'description' => 'Ribeye steak with soup and a drink',
                'sku' => 'COMBO-LUNCH-A',
                'base_price' => 199000,
                'cost_price' => 85000,
                'pricing_type' => 'fixed',
                'is_featured' => true,
                'items' => [
                    ['sku' => 'STEAK-001', 'quantity' => 1, 'sort_order' => 0, 'group_name' => 'Main'],
                    ['sku' => 'SOUP-001', 'quantity' => 1, 'sort_order' => 1, 'group_name' => 'Starter'],
                    ['sku' => 'COLD-002', 'quantity' => 1, 'sort_order' => 2, 'group_name' => 'Drink'],
                ],
            ],
            [
                'name' => 'Lunch Set B - Pasta',
                'description' => 'Any pasta with salad and a drink',
                'sku' => 'COMBO-LUNCH-B',
                'base_price' => 89000,
                'cost_price' => 35000,
                'pricing_type' => 'fixed',
                'is_featured' => true,
                'items' => [
                    ['category' => 'PASTA', 'quantity' => 1, 'sort_order' => 0, 'group_name' => 'Pasta'],
                    ['sku' => 'SALAD-001', 'quantity' => 1, 'sort_order' => 1, 'group_name' => 'Salad'],
                    ['sku' => 'COLD-002', 'quantity' => 1, 'sort_order' => 2, 'group_name' => 'Drink'],
                ],
            ],
            [
                'name' => 'Family Platter',
                'description' => 'Perfect for sharing: wings, fries, onion rings, and spring rolls',
                'sku' => 'COMBO-FAMILY',
                'base_price' => 135000,
                'cost_price' => 45000,
                'pricing_type' => 'fixed',
                'is_featured' => true,
                'items' => [
                    ['sku' => 'FINGER-001', 'quantity' => 1, 'sort_order' => 0, 'group_name' => 'Wings'],
                    ['sku' => 'FINGER-002', 'quantity' => 1, 'sort_order' => 1, 'group_name' => 'Fries'],
                    ['sku' => 'FINGER-003', 'quantity' => 1, 'sort_order' => 2, 'group_name' => 'Rings'],
                    ['sku' => 'FINGER-004', 'quantity' => 1, 'sort_order' => 3, 'group_name' => 'Rolls'],
                ],
            ],
            [
                'name' => 'Couple Dinner',
                'description' => '2 steaks with 2 desserts - 15% off total',
                'sku' => 'COMBO-COUPLE',
                'base_price' => 380000,
                'cost_price' => 160000,
                'pricing_type' => 'discount_percent',
                'discount_value' => 15,
                'allow_substitutions' => true,
                'items' => [
                    ['category' => 'STEAK', 'quantity' => 2, 'sort_order' => 0, 'group_name' => 'Main Course'],
                    ['category' => 'DESSERT', 'quantity' => 2, 'sort_order' => 1, 'group_name' => 'Dessert'],
                ],
            ],
            [
                'name' => 'Nasi Goreng Komplit',
                'description' => 'Nasi goreng with chicken wings and iced tea',
                'sku' => 'COMBO-NASGOR',
                'base_price' => 75000,
                'cost_price' => 30000,
                'pricing_type' => 'fixed',
                'is_featured' => true,
                'items' => [
                    ['sku' => 'RICE-001', 'quantity' => 1, 'sort_order' => 0, 'group_name' => 'Main'],
                    ['sku' => 'FINGER-001', 'quantity' => 1, 'sort_order' => 1, 'group_name' => 'Side'],
                    ['sku' => 'BEV-001', 'quantity' => 1, 'sort_order' => 2, 'group_name' => 'Drink'],
                ],
            ],
            [
                'name' => 'Dessert Duo',
                'description' => 'Any 2 desserts with Rp 10.000 discount',
                'sku' => 'COMBO-DESSERT',
                'base_price' => 80000,
                'cost_price' => 30000,
                'pricing_type' => 'discount_amount',
                'discount_value' => 10000,
                'allow_substitutions' => true,
                'items' => [
                    ['category' => 'DESSERT', 'quantity' => 2, 'sort_order' => 0, 'group_name' => 'Dessert'],
                ],
            ],
            [
                'name' => 'Kids Meal',
                'description' => 'Chicken katsu with fries and orange juice',
                'sku' => 'COMBO-KIDS',
                'base_price' => 65000,
                'cost_price' => 25000,
                'pricing_type' => 'fixed',
                'items' => [
                    ['sku' => 'CHKN-002', 'quantity' => 1, 'sort_order' => 0, 'group_name' => 'Main'],
                    ['sku' => 'FINGER-002', 'quantity' => 1, 'sort_order' => 1, 'group_name' => 'Side'],
                    ['sku' => 'JUICE-001', 'quantity' => 1, 'sort_order' => 2, 'group_name' => 'Drink'],
                ],
            ],
        ];

        $sortOrder = 200;
        foreach ($combos as $comboData) {
            $items = $comboData['items'];
            $pricingType = $comboData['pricing_type'];
            $discountValue = $comboData['discount_value'] ?? 0;
            $allowSubstitutions = $comboData['allow_substitutions'] ?? false;
            unset($comboData['items'], $comboData['pricing_type'], $comboData['discount_value'], $comboData['allow_substitutions']);

            $product = Product::create([
                'tenant_id' => $this->tenant->id,
                'sku' => $comboData['sku'],
                'name' => $comboData['name'],
                'slug' => Str::slug($comboData['name']),
                'description' => $comboData['description'],
                'base_price' => $comboData['base_price'],
                'cost_price' => $comboData['cost_price'],
                'product_type' => Product::TYPE_COMBO,
                'is_active' => true,
                'is_featured' => $comboData['is_featured'] ?? false,
                'sort_order' => $sortOrder++,
            ]);

            $combo = Combo::create([
                'product_id' => $product->id,
                'pricing_type' => $pricingType,
                'discount_value' => $discountValue,
                'allow_substitutions' => $allowSubstitutions,
                'min_items' => count($items),
                'max_items' => count($items),
            ]);

            foreach ($items as $itemData) {
                $productId = null;
                $categoryId = null;

                if (isset($itemData['sku']) && isset($this->products[$itemData['sku']])) {
                    $productId = $this->products[$itemData['sku']]->id;
                } elseif (isset($itemData['category']) && isset($this->categories[$itemData['category']])) {
                    $categoryId = $this->categories[$itemData['category']]->id;
                }

                if (! $productId && ! $categoryId) {
                    continue;
                }

                ComboItem::create([
                    'combo_id' => $combo->id,
                    'product_id' => $productId,
                    'category_id' => $categoryId,
                    'quantity' => $itemData['quantity'] ?? 1,
                    'sort_order' => $itemData['sort_order'] ?? 0,
                    'group_name' => $itemData['group_name'] ?? null,
                ]);
            }

            $this->products[$product->sku] = $product;
        }

        $this->command->info('  Combo Products: '.count($combos));
    }

    private function assignAllProductsToOutlet(): void
    {
        $products = Product::where('tenant_id', $this->tenant->id)->get();
        $sortOrder = 0;

        foreach ($products as $product) {
            ProductOutlet::create([
                'product_id' => $product->id,
                'outlet_id' => $this->outlet->id,
                'is_available' => true,
                'is_featured' => $product->is_featured,
                'sort_order' => $sortOrder++,
            ]);
        }

        $this->command->info("  Assigned {$products->count()} products to outlet: {$this->outlet->name}");
    }

    private function attachModifiers(Product $product, array $modifierNames): void
    {
        foreach ($modifierNames as $index => $name) {
            if (isset($this->modifierGroups[$name])) {
                $product->modifierGroups()->attach($this->modifierGroups[$name]->id, [
                    'id' => Str::uuid(),
                    'is_required' => false,
                    'min_selections' => 0,
                    'max_selections' => null,
                    'sort_order' => $index,
                ]);
            }
        }
    }
}
