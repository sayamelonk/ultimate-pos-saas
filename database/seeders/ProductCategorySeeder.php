<?php

namespace Database\Seeders;

use App\Models\ProductCategory;
use App\Models\Tenant;
use Illuminate\Database\Seeder;

class ProductCategorySeeder extends Seeder
{
    public function run(): void
    {
        $tenant = Tenant::first();

        if (! $tenant) {
            return;
        }

        $categories = [
            [
                'name' => 'Coffee',
                'code' => 'COF',
                'color' => '#8B4513',
                'icon' => 'coffee',
                'description' => 'Hot and cold coffee beverages',
                'is_active' => true,
                'show_in_pos' => true,
                'show_in_menu' => true,
                'sort_order' => 1,
                'children' => [
                    ['name' => 'Hot Coffee', 'code' => 'HOT-COF', 'color' => '#A0522D', 'sort_order' => 1],
                    ['name' => 'Iced Coffee', 'code' => 'ICE-COF', 'color' => '#6B8E23', 'sort_order' => 2],
                    ['name' => 'Espresso Based', 'code' => 'ESP', 'color' => '#4A3728', 'sort_order' => 3],
                ],
            ],
            [
                'name' => 'Non-Coffee',
                'code' => 'NON-COF',
                'color' => '#228B22',
                'icon' => 'beaker',
                'description' => 'Refreshing non-coffee beverages',
                'is_active' => true,
                'show_in_pos' => true,
                'show_in_menu' => true,
                'sort_order' => 2,
                'children' => [
                    ['name' => 'Tea', 'code' => 'TEA', 'color' => '#2E8B57', 'sort_order' => 1],
                    ['name' => 'Chocolate', 'code' => 'CHOCO', 'color' => '#D2691E', 'sort_order' => 2],
                    ['name' => 'Fruit Drinks', 'code' => 'FRUIT', 'color' => '#FF6347', 'sort_order' => 3],
                    ['name' => 'Smoothies', 'code' => 'SMOOTH', 'color' => '#FF69B4', 'sort_order' => 4],
                ],
            ],
            [
                'name' => 'Food',
                'code' => 'FOOD',
                'color' => '#FF8C00',
                'icon' => 'cake',
                'description' => 'Snacks and light meals',
                'is_active' => true,
                'show_in_pos' => true,
                'show_in_menu' => true,
                'sort_order' => 3,
                'children' => [
                    ['name' => 'Pastries', 'code' => 'PASTRY', 'color' => '#DEB887', 'sort_order' => 1],
                    ['name' => 'Cakes', 'code' => 'CAKE', 'color' => '#FFB6C1', 'sort_order' => 2],
                    ['name' => 'Sandwiches', 'code' => 'SAND', 'color' => '#F4A460', 'sort_order' => 3],
                    ['name' => 'Rice Bowls', 'code' => 'RICE', 'color' => '#FAFAD2', 'sort_order' => 4],
                ],
            ],
            [
                'name' => 'Merchandise',
                'code' => 'MERCH',
                'color' => '#4169E1',
                'icon' => 'gift',
                'description' => 'Coffee beans and merchandise',
                'is_active' => true,
                'show_in_pos' => true,
                'show_in_menu' => false,
                'sort_order' => 4,
                'children' => [
                    ['name' => 'Coffee Beans', 'code' => 'BEANS', 'color' => '#8B4513', 'sort_order' => 1],
                    ['name' => 'Drinkware', 'code' => 'DRINK', 'color' => '#708090', 'sort_order' => 2],
                    ['name' => 'Equipment', 'code' => 'EQUIP', 'color' => '#696969', 'sort_order' => 3],
                ],
            ],
        ];

        foreach ($categories as $categoryData) {
            $children = $categoryData['children'] ?? [];
            unset($categoryData['children']);

            $parent = ProductCategory::create([
                'tenant_id' => $tenant->id,
                ...$categoryData,
            ]);

            foreach ($children as $childData) {
                ProductCategory::create([
                    'tenant_id' => $tenant->id,
                    'parent_id' => $parent->id,
                    'icon' => $parent->icon,
                    'description' => null,
                    'is_active' => true,
                    'show_in_pos' => true,
                    'show_in_menu' => true,
                    ...$childData,
                ]);
            }
        }
    }
}
