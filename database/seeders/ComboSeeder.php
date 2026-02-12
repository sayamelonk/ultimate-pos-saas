<?php

namespace Database\Seeders;

use App\Models\Combo;
use App\Models\ComboItem;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Tenant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ComboSeeder extends Seeder
{
    public function run(): void
    {
        $tenant = Tenant::first();

        if (! $tenant) {
            return;
        }

        // Get products for combo items
        $cappuccino = Product::where('sku', 'HOT-CAP-001')->first();
        $icedLatte = Product::where('sku', 'ICE-LAT-001')->first();
        $americano = Product::where('sku', 'HOT-AME-001')->first();
        $croissant = Product::where('sku', 'PASTRY-CR-001')->first();
        $painAuChocolat = Product::where('sku', 'PASTRY-PC-001')->first();
        $cheesecake = Product::where('sku', 'CAKE-CHE-001')->first();
        $clubSandwich = Product::where('sku', 'SAND-CLB-001')->first();

        // Get categories for category-based combos
        $coffeeCategory = ProductCategory::where('code', 'COF')->first();
        $pastryCategory = ProductCategory::where('code', 'PASTRY')->first();
        $cakeCategory = ProductCategory::where('code', 'CAKE')->first();

        $combos = [
            [
                'name' => 'Breakfast Combo',
                'description' => 'Start your morning with a hot coffee and fresh pastry',
                'base_price' => 45000,
                'cost_price' => 15000,
                'pricing_type' => 'fixed',
                'is_active' => true,
                'is_featured' => true,
                'items' => [
                    ['product_id' => $cappuccino?->id, 'quantity' => 1, 'sort_order' => 0],
                    ['product_id' => $croissant?->id, 'quantity' => 1, 'sort_order' => 1],
                ],
            ],
            [
                'name' => 'Coffee & Cake',
                'description' => 'Enjoy a coffee with your favorite slice of cake',
                'base_price' => 65000,
                'cost_price' => 25000,
                'pricing_type' => 'discount_percent',
                'discount_value' => 15,
                'allow_substitutions' => true,
                'is_active' => true,
                'items' => [
                    ['category_id' => $coffeeCategory?->id, 'quantity' => 1, 'sort_order' => 0, 'group_name' => 'Drink'],
                    ['category_id' => $cakeCategory?->id, 'quantity' => 1, 'sort_order' => 1, 'group_name' => 'Dessert'],
                ],
            ],
            [
                'name' => 'Lunch Set',
                'description' => 'Club sandwich with iced coffee',
                'base_price' => 75000,
                'cost_price' => 30000,
                'pricing_type' => 'fixed',
                'is_active' => true,
                'is_featured' => true,
                'items' => [
                    ['product_id' => $clubSandwich?->id, 'quantity' => 1, 'sort_order' => 0, 'group_name' => 'Main'],
                    ['product_id' => $icedLatte?->id, 'quantity' => 1, 'sort_order' => 1, 'group_name' => 'Drink'],
                ],
            ],
            [
                'name' => 'Double Coffee Deal',
                'description' => 'Two coffees for a special price',
                'base_price' => 55000,
                'cost_price' => 18000,
                'pricing_type' => 'discount_amount',
                'discount_value' => 10000,
                'allow_substitutions' => true,
                'is_active' => true,
                'items' => [
                    ['category_id' => $coffeeCategory?->id, 'quantity' => 2, 'sort_order' => 0],
                ],
            ],
            [
                'name' => 'Afternoon Tea Set',
                'description' => 'Perfect for afternoon break - 2 coffees and 2 pastries',
                'base_price' => 120000,
                'cost_price' => 45000,
                'pricing_type' => 'fixed',
                'allow_substitutions' => true,
                'is_active' => true,
                'is_featured' => true,
                'items' => [
                    ['category_id' => $coffeeCategory?->id, 'quantity' => 2, 'sort_order' => 0, 'group_name' => 'Drinks'],
                    ['category_id' => $pastryCategory?->id, 'quantity' => 2, 'sort_order' => 1, 'group_name' => 'Pastries'],
                ],
            ],
            [
                'name' => 'Sweet Treat Combo',
                'description' => 'Pain au Chocolat with hot Americano',
                'base_price' => 48000,
                'cost_price' => 16000,
                'pricing_type' => 'sum',
                'is_active' => true,
                'items' => [
                    ['product_id' => $americano?->id, 'quantity' => 1, 'sort_order' => 0],
                    ['product_id' => $painAuChocolat?->id, 'quantity' => 1, 'sort_order' => 1],
                ],
            ],
            [
                'name' => 'Dessert Special',
                'description' => 'Cheesecake with any coffee of your choice',
                'base_price' => 70000,
                'cost_price' => 28000,
                'pricing_type' => 'discount_percent',
                'discount_value' => 10,
                'allow_substitutions' => true,
                'is_active' => true,
                'items' => [
                    ['product_id' => $cheesecake?->id, 'quantity' => 1, 'sort_order' => 0, 'group_name' => 'Dessert'],
                    ['category_id' => $coffeeCategory?->id, 'quantity' => 1, 'sort_order' => 1, 'group_name' => 'Drink'],
                ],
            ],
        ];

        $sortOrder = 100; // Start after regular products

        foreach ($combos as $comboData) {
            $items = $comboData['items'] ?? [];
            unset($comboData['items']);

            // Extract combo-specific fields
            $pricingType = $comboData['pricing_type'] ?? 'fixed';
            $discountValue = $comboData['discount_value'] ?? 0;
            $allowSubstitutions = $comboData['allow_substitutions'] ?? false;
            unset($comboData['pricing_type'], $comboData['discount_value'], $comboData['allow_substitutions']);

            // Create the product first
            $product = Product::create([
                'tenant_id' => $tenant->id,
                'sku' => 'COMBO-'.Str::upper(Str::random(6)),
                'name' => $comboData['name'],
                'slug' => Str::slug($comboData['name']),
                'description' => $comboData['description'] ?? null,
                'base_price' => $comboData['base_price'],
                'cost_price' => $comboData['cost_price'] ?? 0,
                'product_type' => Product::TYPE_COMBO,
                'is_active' => $comboData['is_active'] ?? true,
                'is_featured' => $comboData['is_featured'] ?? false,
                'sort_order' => $sortOrder++,
            ]);

            // Create the combo settings
            $combo = Combo::create([
                'product_id' => $product->id,
                'pricing_type' => $pricingType,
                'discount_value' => $discountValue,
                'allow_substitutions' => $allowSubstitutions,
                'min_items' => count($items),
                'max_items' => count($items),
            ]);

            // Create combo items
            foreach ($items as $itemData) {
                // Skip items with null product_id and null category_id
                if (! ($itemData['product_id'] ?? null) && ! ($itemData['category_id'] ?? null)) {
                    continue;
                }

                ComboItem::create([
                    'combo_id' => $combo->id,
                    'product_id' => $itemData['product_id'] ?? null,
                    'category_id' => $itemData['category_id'] ?? null,
                    'quantity' => $itemData['quantity'] ?? 1,
                    'sort_order' => $itemData['sort_order'] ?? 0,
                    'group_name' => $itemData['group_name'] ?? null,
                ]);
            }
        }
    }
}
