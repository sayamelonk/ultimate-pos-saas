<?php

namespace Database\Seeders;

use App\Models\InventoryItem;
use App\Models\Product;
use App\Models\Recipe;
use App\Models\RecipeItem;
use App\Models\Tenant;
use App\Models\Unit;
use Illuminate\Database\Seeder;

class ProductRecipeSeeder extends Seeder
{
    public function run(): void
    {
        $tenant = Tenant::first();

        if (! $tenant) {
            return;
        }

        // Get piece unit for yield
        $pieceUnit = Unit::where('tenant_id', $tenant->id)
            ->where(function ($q) {
                $q->where('abbreviation', 'pcs')
                    ->orWhere('name', 'like', '%Piece%');
            })
            ->first() ?? Unit::where('tenant_id', $tenant->id)->first();

        // Get inventory items for recipes
        $kopiArabica = InventoryItem::where('tenant_id', $tenant->id)->where('name', 'like', '%Arabica%')->first();
        $kopiRobusta = InventoryItem::where('tenant_id', $tenant->id)->where('name', 'like', '%Robusta%')->first();
        $susuSegar = InventoryItem::where('tenant_id', $tenant->id)->where('name', 'like', '%Susu Segar%')->first();
        $susuUHT = InventoryItem::where('tenant_id', $tenant->id)->where('name', 'like', '%Susu UHT%')->first();
        $gulaPasir = InventoryItem::where('tenant_id', $tenant->id)->where('name', 'like', '%Gula Pasir%')->first();
        $gulaAren = InventoryItem::where('tenant_id', $tenant->id)->where('name', 'like', '%Gula Aren%')->first();
        $tehHijau = InventoryItem::where('tenant_id', $tenant->id)->where('name', 'like', '%Teh Hijau%')->first();

        // Define recipes for products
        $productRecipes = [
            // Coffee products
            'Americano' => [
                'description' => 'Espresso with hot water',
                'items' => [
                    ['item' => $kopiArabica, 'quantity' => 0.018, 'unit_cost' => 150000], // 18g coffee
                ],
            ],
            'Cappuccino' => [
                'description' => 'Espresso with steamed milk foam',
                'items' => [
                    ['item' => $kopiArabica, 'quantity' => 0.018, 'unit_cost' => 150000],
                    ['item' => $susuSegar, 'quantity' => 0.150, 'unit_cost' => 18000], // 150ml milk
                ],
            ],
            'Cafe Latte' => [
                'description' => 'Espresso with steamed milk',
                'items' => [
                    ['item' => $kopiArabica, 'quantity' => 0.018, 'unit_cost' => 150000],
                    ['item' => $susuSegar, 'quantity' => 0.200, 'unit_cost' => 18000], // 200ml milk
                ],
            ],
            'Iced Americano' => [
                'description' => 'Espresso with cold water over ice',
                'items' => [
                    ['item' => $kopiArabica, 'quantity' => 0.018, 'unit_cost' => 150000],
                ],
            ],
            'Iced Latte' => [
                'description' => 'Espresso with cold milk over ice',
                'items' => [
                    ['item' => $kopiArabica, 'quantity' => 0.018, 'unit_cost' => 150000],
                    ['item' => $susuSegar, 'quantity' => 0.200, 'unit_cost' => 18000],
                ],
            ],
            'Cold Brew' => [
                'description' => 'Slow-steeped cold brew coffee',
                'items' => [
                    ['item' => $kopiArabica, 'quantity' => 0.025, 'unit_cost' => 150000], // More coffee for cold brew
                ],
            ],
            'Espresso' => [
                'description' => 'Pure espresso shot',
                'items' => [
                    ['item' => $kopiArabica, 'quantity' => 0.018, 'unit_cost' => 150000],
                ],
            ],
            'Macchiato' => [
                'description' => 'Espresso with a dollop of foam',
                'items' => [
                    ['item' => $kopiArabica, 'quantity' => 0.018, 'unit_cost' => 150000],
                    ['item' => $susuSegar, 'quantity' => 0.030, 'unit_cost' => 18000], // 30ml milk
                ],
            ],

            // Tea products
            'Earl Grey' => [
                'description' => 'Bergamot-flavored black tea',
                'items' => [
                    ['item' => $tehHijau, 'quantity' => 0.003, 'unit_cost' => 200000], // 3g tea
                ],
            ],
            'Green Tea Latte' => [
                'description' => 'Japanese matcha with milk',
                'items' => [
                    ['item' => $tehHijau, 'quantity' => 0.005, 'unit_cost' => 200000],
                    ['item' => $susuSegar, 'quantity' => 0.200, 'unit_cost' => 18000],
                    ['item' => $gulaPasir, 'quantity' => 0.015, 'unit_cost' => 15000],
                ],
            ],
            'Chai Latte' => [
                'description' => 'Spiced tea with steamed milk',
                'items' => [
                    ['item' => $tehHijau, 'quantity' => 0.003, 'unit_cost' => 200000],
                    ['item' => $susuSegar, 'quantity' => 0.200, 'unit_cost' => 18000],
                    ['item' => $gulaPasir, 'quantity' => 0.015, 'unit_cost' => 15000],
                ],
            ],

            // Chocolate products
            'Hot Chocolate' => [
                'description' => 'Rich Belgian chocolate',
                'items' => [
                    ['item' => $susuSegar, 'quantity' => 0.250, 'unit_cost' => 18000],
                    ['item' => $gulaPasir, 'quantity' => 0.020, 'unit_cost' => 15000],
                ],
            ],
            'Iced Chocolate' => [
                'description' => 'Chilled chocolate drink',
                'items' => [
                    ['item' => $susuSegar, 'quantity' => 0.250, 'unit_cost' => 18000],
                    ['item' => $gulaPasir, 'quantity' => 0.020, 'unit_cost' => 15000],
                ],
            ],
        ];

        foreach ($productRecipes as $productName => $recipeData) {
            // Find the product
            $product = Product::where('tenant_id', $tenant->id)
                ->where('name', $productName)
                ->first();

            if (! $product) {
                $this->command->warn("Product not found: {$productName}");

                continue;
            }

            // Skip if product already has a recipe
            if ($product->recipe_id) {
                $this->command->info("Product already has recipe: {$productName}");

                continue;
            }

            // Create recipe
            $recipe = Recipe::create([
                'tenant_id' => $tenant->id,
                'name' => $productName,
                'description' => $recipeData['description'],
                'yield_qty' => 1,
                'yield_unit_id' => $pieceUnit->id,
                'is_active' => true,
            ]);

            // Add recipe items
            $totalCost = 0;
            foreach ($recipeData['items'] as $itemData) {
                if (! $itemData['item']) {
                    continue;
                }

                $itemCost = $itemData['quantity'] * $itemData['unit_cost'];
                $totalCost += $itemCost;

                RecipeItem::create([
                    'recipe_id' => $recipe->id,
                    'inventory_item_id' => $itemData['item']->id,
                    'quantity' => $itemData['quantity'],
                    'unit_id' => $itemData['item']->unit_id ?? $pieceUnit->id,
                    'waste_percentage' => 0,
                    'sort_order' => 0,
                ]);
            }

            // Update recipe total cost
            $recipe->update(['estimated_cost' => $totalCost]);

            // Link recipe to product
            $product->update([
                'recipe_id' => $recipe->id,
                'cost_price' => $totalCost,
            ]);

            $this->command->info("Created recipe for: {$productName} (Cost: Rp ".number_format($totalCost).')');
        }

        // Link existing Es Kopi Susu recipe to a product if available
        $esKopiSusuRecipe = Recipe::where('name', 'Es Kopi Susu')->first();
        if ($esKopiSusuRecipe) {
            // Could link to Iced Latte or create a new product
            $this->command->info('Existing Es Kopi Susu recipe available: '.$esKopiSusuRecipe->id);
        }

        // Link existing Matcha Latte recipe
        $matchaRecipe = Recipe::where('name', 'Matcha Latte')->first();
        if ($matchaRecipe) {
            $greenTeaLatte = Product::where('tenant_id', $tenant->id)
                ->where('name', 'Green Tea Latte')
                ->first();

            if ($greenTeaLatte && ! $greenTeaLatte->recipe_id) {
                $greenTeaLatte->update([
                    'recipe_id' => $matchaRecipe->id,
                    'cost_price' => $matchaRecipe->estimated_cost,
                ]);
                $this->command->info('Linked Matcha Latte recipe to Green Tea Latte');
            }
        }
    }
}
