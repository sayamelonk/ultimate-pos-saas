<?php

namespace Database\Seeders;

use App\Models\Tenant;
use App\Models\VariantGroup;
use App\Models\VariantOption;
use Illuminate\Database\Seeder;

class VariantGroupSeeder extends Seeder
{
    public function run(): void
    {
        $tenant = Tenant::first();

        if (! $tenant) {
            return;
        }

        $variantGroups = [
            [
                'name' => 'Size',
                'description' => 'Beverage size options',
                'display_type' => 'button',
                'is_active' => true,
                'sort_order' => 1,
                'options' => [
                    ['name' => 'Small', 'price_adjustment' => 0, 'sort_order' => 0],
                    ['name' => 'Medium', 'price_adjustment' => 5000, 'sort_order' => 1],
                    ['name' => 'Large', 'price_adjustment' => 10000, 'sort_order' => 2],
                ],
            ],
            [
                'name' => 'Ice Level',
                'description' => 'Amount of ice in beverage',
                'display_type' => 'button',
                'is_active' => true,
                'sort_order' => 2,
                'options' => [
                    ['name' => 'No Ice', 'price_adjustment' => 0, 'sort_order' => 0],
                    ['name' => 'Less Ice', 'price_adjustment' => 0, 'sort_order' => 1],
                    ['name' => 'Normal Ice', 'price_adjustment' => 0, 'sort_order' => 2],
                    ['name' => 'Extra Ice', 'price_adjustment' => 0, 'sort_order' => 3],
                ],
            ],
            [
                'name' => 'Sugar Level',
                'description' => 'Sweetness level',
                'display_type' => 'dropdown',
                'is_active' => true,
                'sort_order' => 3,
                'options' => [
                    ['name' => '0% (No Sugar)', 'price_adjustment' => 0, 'sort_order' => 0],
                    ['name' => '25%', 'price_adjustment' => 0, 'sort_order' => 1],
                    ['name' => '50%', 'price_adjustment' => 0, 'sort_order' => 2],
                    ['name' => '75%', 'price_adjustment' => 0, 'sort_order' => 3],
                    ['name' => '100% (Normal)', 'price_adjustment' => 0, 'sort_order' => 4],
                ],
            ],
            [
                'name' => 'Temperature',
                'description' => 'Hot or iced',
                'display_type' => 'button',
                'is_active' => true,
                'sort_order' => 4,
                'options' => [
                    ['name' => 'Hot', 'price_adjustment' => 0, 'sort_order' => 0],
                    ['name' => 'Iced', 'price_adjustment' => 3000, 'sort_order' => 1],
                ],
            ],
            [
                'name' => 'Milk Type',
                'description' => 'Type of milk for coffee',
                'display_type' => 'dropdown',
                'is_active' => true,
                'sort_order' => 5,
                'options' => [
                    ['name' => 'Regular Milk', 'price_adjustment' => 0, 'sort_order' => 0],
                    ['name' => 'Oat Milk', 'price_adjustment' => 8000, 'sort_order' => 1],
                    ['name' => 'Almond Milk', 'price_adjustment' => 8000, 'sort_order' => 2],
                    ['name' => 'Soy Milk', 'price_adjustment' => 5000, 'sort_order' => 3],
                    ['name' => 'Coconut Milk', 'price_adjustment' => 6000, 'sort_order' => 4],
                ],
            ],
            [
                'name' => 'Spice Level',
                'description' => 'For food items',
                'display_type' => 'button',
                'is_active' => true,
                'sort_order' => 6,
                'options' => [
                    ['name' => 'Mild', 'price_adjustment' => 0, 'sort_order' => 0],
                    ['name' => 'Medium', 'price_adjustment' => 0, 'sort_order' => 1],
                    ['name' => 'Spicy', 'price_adjustment' => 0, 'sort_order' => 2],
                    ['name' => 'Extra Spicy', 'price_adjustment' => 0, 'sort_order' => 3],
                ],
            ],
        ];

        foreach ($variantGroups as $groupData) {
            $options = $groupData['options'] ?? [];
            unset($groupData['options']);

            $group = VariantGroup::create([
                'tenant_id' => $tenant->id,
                ...$groupData,
            ]);

            foreach ($options as $optionData) {
                VariantOption::create([
                    'variant_group_id' => $group->id,
                    'is_active' => true,
                    ...$optionData,
                ]);
            }
        }
    }
}
