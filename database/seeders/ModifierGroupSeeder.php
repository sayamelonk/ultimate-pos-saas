<?php

namespace Database\Seeders;

use App\Models\Modifier;
use App\Models\ModifierGroup;
use App\Models\Tenant;
use Illuminate\Database\Seeder;

class ModifierGroupSeeder extends Seeder
{
    public function run(): void
    {
        $tenant = Tenant::first();

        if (! $tenant) {
            return;
        }

        $modifierGroups = [
            [
                'name' => 'Extra Shots',
                'description' => 'Add espresso shots to your drink',
                'selection_type' => 'multiple',
                'min_selections' => 0,
                'max_selections' => 3,
                'is_active' => true,
                'sort_order' => 1,
                'modifiers' => [
                    ['name' => 'Single Shot Espresso', 'price' => 5000, 'sort_order' => 1],
                    ['name' => 'Double Shot Espresso', 'price' => 10000, 'sort_order' => 2],
                    ['name' => 'Vanilla Shot', 'price' => 5000, 'sort_order' => 3],
                    ['name' => 'Caramel Shot', 'price' => 5000, 'sort_order' => 4],
                    ['name' => 'Hazelnut Shot', 'price' => 5000, 'sort_order' => 5],
                ],
            ],
            [
                'name' => 'Toppings',
                'description' => 'Add toppings to your drink',
                'selection_type' => 'multiple',
                'min_selections' => 0,
                'max_selections' => 5,
                'is_active' => true,
                'sort_order' => 2,
                'modifiers' => [
                    ['name' => 'Whipped Cream', 'price' => 5000, 'sort_order' => 1],
                    ['name' => 'Chocolate Drizzle', 'price' => 3000, 'sort_order' => 2],
                    ['name' => 'Caramel Drizzle', 'price' => 3000, 'sort_order' => 3],
                    ['name' => 'Cinnamon Powder', 'price' => 2000, 'sort_order' => 4],
                    ['name' => 'Cocoa Powder', 'price' => 2000, 'sort_order' => 5],
                    ['name' => 'Boba Pearls', 'price' => 8000, 'sort_order' => 6],
                    ['name' => 'Grass Jelly', 'price' => 6000, 'sort_order' => 7],
                ],
            ],
            [
                'name' => 'Sauce',
                'description' => 'Choose your sauce',
                'selection_type' => 'single',
                'min_selections' => 0,
                'max_selections' => 1,
                'is_active' => true,
                'sort_order' => 3,
                'modifiers' => [
                    ['name' => 'BBQ Sauce', 'price' => 0, 'sort_order' => 1],
                    ['name' => 'Mayo', 'price' => 0, 'sort_order' => 2],
                    ['name' => 'Ketchup', 'price' => 0, 'sort_order' => 3],
                    ['name' => 'Mustard', 'price' => 0, 'sort_order' => 4],
                    ['name' => 'Chili Sauce', 'price' => 0, 'sort_order' => 5],
                ],
            ],
            [
                'name' => 'Extra Add-ons (Food)',
                'description' => 'Add extras to your food',
                'selection_type' => 'multiple',
                'min_selections' => 0,
                'max_selections' => 5,
                'is_active' => true,
                'sort_order' => 4,
                'modifiers' => [
                    ['name' => 'Extra Cheese', 'price' => 5000, 'sort_order' => 1],
                    ['name' => 'Extra Egg', 'price' => 5000, 'sort_order' => 2],
                    ['name' => 'Extra Bacon', 'price' => 8000, 'sort_order' => 3],
                    ['name' => 'Extra Chicken', 'price' => 10000, 'sort_order' => 4],
                    ['name' => 'Avocado', 'price' => 12000, 'sort_order' => 5],
                ],
            ],
            [
                'name' => 'Packaging',
                'description' => 'Choose packaging type',
                'selection_type' => 'single',
                'min_selections' => 1,
                'max_selections' => 1,
                'is_active' => true,
                'sort_order' => 5,
                'modifiers' => [
                    ['name' => 'Dine In', 'price' => 0, 'sort_order' => 1],
                    ['name' => 'Take Away', 'price' => 2000, 'sort_order' => 2],
                ],
            ],
        ];

        foreach ($modifierGroups as $groupData) {
            $modifiers = $groupData['modifiers'] ?? [];
            unset($groupData['modifiers']);

            $group = ModifierGroup::create([
                'tenant_id' => $tenant->id,
                ...$groupData,
            ]);

            foreach ($modifiers as $modifierData) {
                Modifier::create([
                    'modifier_group_id' => $group->id,
                    'is_active' => true,
                    ...$modifierData,
                ]);
            }
        }
    }
}
