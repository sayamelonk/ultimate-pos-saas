<?php

namespace Database\Seeders;

use App\Models\InventoryItem;
use App\Models\Recipe;
use App\Models\RecipeItem;
use App\Models\Tenant;
use App\Models\Unit;
use Illuminate\Database\Seeder;

class RecipeSeeder extends Seeder
{
    public function run(): void
    {
        $tenants = Tenant::all();

        foreach ($tenants as $tenant) {
            $this->seedRecipesForTenant($tenant);
        }
    }

    private function seedRecipesForTenant(Tenant $tenant): void
    {
        $items = InventoryItem::where('tenant_id', $tenant->id)->get()->keyBy('name');
        $units = Unit::where('tenant_id', $tenant->id)->get()->keyBy('abbreviation');

        if ($items->isEmpty() || $units->isEmpty()) {
            return;
        }

        $recipes = $this->getRecipeDefinitions();

        foreach ($recipes as $recipeData) {
            $yieldUnit = $units->get($recipeData['yield_unit']);
            if (! $yieldUnit) {
                continue;
            }

            $recipe = Recipe::create([
                'tenant_id' => $tenant->id,
                'product_id' => null,
                'name' => $recipeData['name'],
                'description' => $recipeData['description'],
                'instructions' => $recipeData['instructions'],
                'yield_qty' => $recipeData['yield_qty'],
                'yield_unit_id' => $yieldUnit->id,
                'estimated_cost' => 0,
                'prep_time_minutes' => $recipeData['prep_time'],
                'cook_time_minutes' => $recipeData['cook_time'],
                'version' => 1,
                'is_active' => true,
            ]);

            $totalCost = 0;
            $sortOrder = 1;

            foreach ($recipeData['ingredients'] as $ingredient) {
                $item = $items->get($ingredient['item']);
                $unit = $units->get($ingredient['unit']);

                if (! $item || ! $unit) {
                    continue;
                }

                $wastePercentage = $ingredient['waste'] ?? 0;

                RecipeItem::create([
                    'recipe_id' => $recipe->id,
                    'inventory_item_id' => $item->id,
                    'quantity' => $ingredient['qty'],
                    'unit_id' => $unit->id,
                    'waste_percentage' => $wastePercentage,
                    'notes' => $ingredient['notes'] ?? null,
                    'sort_order' => $sortOrder++,
                ]);

                // Calculate cost
                $grossQty = $ingredient['qty'] * (1 + $wastePercentage / 100);
                $totalCost += $grossQty * (float) $item->cost_price;
            }

            $recipe->update(['estimated_cost' => $totalCost]);
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getRecipeDefinitions(): array
    {
        return [
            // Beverages
            [
                'name' => 'Es Kopi Susu',
                'description' => 'Signature iced coffee with fresh milk',
                'instructions' => "1. Brew espresso shot\n2. Add palm sugar syrup\n3. Pour fresh milk\n4. Add ice cubes\n5. Stir and serve",
                'yield_qty' => 1,
                'yield_unit' => 'pcs',
                'prep_time' => 2,
                'cook_time' => 3,
                'ingredients' => [
                    ['item' => 'Biji Kopi Arabica', 'qty' => 18, 'unit' => 'g', 'notes' => 'Ground fresh'],
                    ['item' => 'Susu Segar Full Cream', 'qty' => 150, 'unit' => 'ml'],
                    ['item' => 'Gula Aren', 'qty' => 20, 'unit' => 'g'],
                ],
            ],
            [
                'name' => 'Matcha Latte',
                'description' => 'Premium matcha green tea latte',
                'instructions' => "1. Sift matcha powder\n2. Add hot water and whisk\n3. Steam milk\n4. Combine and serve",
                'yield_qty' => 1,
                'yield_unit' => 'pcs',
                'prep_time' => 3,
                'cook_time' => 2,
                'ingredients' => [
                    ['item' => 'Teh Hijau Premium', 'qty' => 3, 'unit' => 'g'],
                    ['item' => 'Susu Segar Full Cream', 'qty' => 200, 'unit' => 'ml'],
                    ['item' => 'Gula Pasir Putih', 'qty' => 15, 'unit' => 'g'],
                ],
            ],

            // Main Dishes
            [
                'name' => 'Nasi Goreng Spesial',
                'description' => 'Indonesian special fried rice with chicken and prawns',
                'instructions' => "1. Heat oil in wok\n2. Sauté garlic and shallots\n3. Add chicken, cook until done\n4. Add prawns\n5. Add rice, stir fry\n6. Season with kecap manis and sambal\n7. Add egg, scramble\n8. Garnish and serve",
                'yield_qty' => 1,
                'yield_unit' => 'pcs',
                'prep_time' => 10,
                'cook_time' => 8,
                'ingredients' => [
                    ['item' => 'Beras Putih Premium', 'qty' => 200, 'unit' => 'g', 'notes' => 'Cooked and cooled'],
                    ['item' => 'Dada Ayam Fillet', 'qty' => 80, 'unit' => 'g', 'waste' => 5],
                    ['item' => 'Udang Vaname Ukuran 30', 'qty' => 50, 'unit' => 'g', 'waste' => 20, 'notes' => 'Peeled'],
                    ['item' => 'Telur Ayam Negeri', 'qty' => 55, 'unit' => 'g'],
                    ['item' => 'Bawang Putih', 'qty' => 10, 'unit' => 'g', 'waste' => 10],
                    ['item' => 'Bawang Merah', 'qty' => 15, 'unit' => 'g', 'waste' => 10],
                    ['item' => 'Kecap Manis', 'qty' => 20, 'unit' => 'ml'],
                    ['item' => 'Saus Sambal', 'qty' => 10, 'unit' => 'ml'],
                    ['item' => 'Minyak Goreng Sawit', 'qty' => 30, 'unit' => 'ml'],
                    ['item' => 'Garam Halus', 'qty' => 2, 'unit' => 'g'],
                ],
            ],
            [
                'name' => 'Ayam Goreng Kremes',
                'description' => 'Crispy fried chicken with spiced crumbs',
                'instructions' => "1. Marinate chicken with spices\n2. Coat with seasoned flour\n3. Deep fry until golden\n4. Make kremes from batter drippings\n5. Serve with rice and sambal",
                'yield_qty' => 1,
                'yield_unit' => 'pcs',
                'prep_time' => 15,
                'cook_time' => 12,
                'ingredients' => [
                    ['item' => 'Paha Ayam', 'qty' => 250, 'unit' => 'g'],
                    ['item' => 'Tepung Terigu Protein Sedang', 'qty' => 50, 'unit' => 'g'],
                    ['item' => 'Tepung Maizena', 'qty' => 20, 'unit' => 'g'],
                    ['item' => 'Bawang Putih', 'qty' => 15, 'unit' => 'g', 'waste' => 10],
                    ['item' => 'Ketumbar Bubuk', 'qty' => 3, 'unit' => 'g'],
                    ['item' => 'Kunyit Bubuk', 'qty' => 2, 'unit' => 'g'],
                    ['item' => 'Garam Halus', 'qty' => 5, 'unit' => 'g'],
                    ['item' => 'Minyak Goreng Sawit', 'qty' => 200, 'unit' => 'ml', 'notes' => 'For deep frying'],
                ],
            ],
            [
                'name' => 'Spaghetti Carbonara',
                'description' => 'Classic Italian pasta with creamy egg sauce and bacon',
                'instructions' => "1. Cook pasta al dente\n2. Fry bacon until crispy\n3. Mix egg yolks with parmesan\n4. Toss hot pasta with bacon\n5. Add egg mixture off heat\n6. Season with pepper\n7. Serve immediately",
                'yield_qty' => 1,
                'yield_unit' => 'pcs',
                'prep_time' => 5,
                'cook_time' => 15,
                'ingredients' => [
                    ['item' => 'Pasta Spaghetti', 'qty' => 120, 'unit' => 'g'],
                    ['item' => 'Telur Ayam Negeri', 'qty' => 110, 'unit' => 'g', 'notes' => '2 whole + 1 yolk'],
                    ['item' => 'Keju Parmesan', 'qty' => 40, 'unit' => 'g'],
                    ['item' => 'Daging Sapi Sandung Lamur', 'qty' => 60, 'unit' => 'g', 'notes' => 'Diced as bacon substitute'],
                    ['item' => 'Bawang Putih', 'qty' => 8, 'unit' => 'g', 'waste' => 10],
                    ['item' => 'Merica Bubuk', 'qty' => 2, 'unit' => 'g'],
                    ['item' => 'Garam Halus', 'qty' => 3, 'unit' => 'g'],
                    ['item' => 'Minyak Zaitun Extra Virgin', 'qty' => 15, 'unit' => 'ml'],
                ],
            ],
            [
                'name' => 'Salmon Teriyaki',
                'description' => 'Pan-seared salmon with homemade teriyaki glaze',
                'instructions' => "1. Season salmon with salt and pepper\n2. Make teriyaki sauce\n3. Pan sear salmon skin-side down\n4. Flip and add sauce\n5. Glaze until caramelized\n6. Serve with steamed rice",
                'yield_qty' => 1,
                'yield_unit' => 'pcs',
                'prep_time' => 5,
                'cook_time' => 10,
                'ingredients' => [
                    ['item' => 'Ikan Salmon Fillet', 'qty' => 180, 'unit' => 'g'],
                    ['item' => 'Kecap Asin', 'qty' => 30, 'unit' => 'ml'],
                    ['item' => 'Gula Pasir Putih', 'qty' => 15, 'unit' => 'g'],
                    ['item' => 'Jahe', 'qty' => 5, 'unit' => 'g', 'waste' => 15],
                    ['item' => 'Bawang Putih', 'qty' => 5, 'unit' => 'g', 'waste' => 10],
                    ['item' => 'Minyak Wijen', 'qty' => 5, 'unit' => 'ml'],
                    ['item' => 'Minyak Goreng Sawit', 'qty' => 15, 'unit' => 'ml'],
                ],
            ],

            // Soups
            [
                'name' => 'Sop Buntut',
                'description' => 'Indonesian oxtail soup with vegetables',
                'instructions' => "1. Boil oxtail until tender\n2. Sauté aromatics\n3. Add to broth\n4. Add vegetables\n5. Season and simmer\n6. Serve with fried shallots",
                'yield_qty' => 1,
                'yield_unit' => 'pcs',
                'prep_time' => 20,
                'cook_time' => 120,
                'ingredients' => [
                    ['item' => 'Iga Sapi', 'qty' => 300, 'unit' => 'g', 'notes' => 'Cut into pieces'],
                    ['item' => 'Wortel', 'qty' => 80, 'unit' => 'g', 'waste' => 15],
                    ['item' => 'Kentang', 'qty' => 100, 'unit' => 'g', 'waste' => 20],
                    ['item' => 'Tomat', 'qty' => 50, 'unit' => 'g', 'waste' => 5],
                    ['item' => 'Bawang Bombay', 'qty' => 50, 'unit' => 'g', 'waste' => 10],
                    ['item' => 'Serai', 'qty' => 10, 'unit' => 'g'],
                    ['item' => 'Merica Bubuk', 'qty' => 3, 'unit' => 'g'],
                    ['item' => 'Garam Halus', 'qty' => 5, 'unit' => 'g'],
                ],
            ],

            // Appetizers / Sides
            [
                'name' => 'Caesar Salad',
                'description' => 'Classic caesar salad with homemade dressing',
                'instructions' => "1. Prepare caesar dressing\n2. Toast croutons\n3. Wash and dry romaine\n4. Toss lettuce with dressing\n5. Top with parmesan and croutons",
                'yield_qty' => 1,
                'yield_unit' => 'pcs',
                'prep_time' => 10,
                'cook_time' => 5,
                'ingredients' => [
                    ['item' => 'Selada Romaine', 'qty' => 150, 'unit' => 'g', 'waste' => 15],
                    ['item' => 'Keju Parmesan', 'qty' => 25, 'unit' => 'g'],
                    ['item' => 'Mayonnaise', 'qty' => 40, 'unit' => 'g'],
                    ['item' => 'Bawang Putih', 'qty' => 5, 'unit' => 'g', 'waste' => 10],
                    ['item' => 'Lemon Import', 'qty' => 15, 'unit' => 'g', 'notes' => 'Juice only'],
                    ['item' => 'Minyak Zaitun Extra Virgin', 'qty' => 10, 'unit' => 'ml'],
                ],
            ],
            [
                'name' => 'French Fries',
                'description' => 'Crispy golden french fries',
                'instructions' => "1. Cut potatoes into strips\n2. Soak in cold water\n3. Dry thoroughly\n4. First fry at low temp\n5. Second fry at high temp\n6. Season with salt",
                'yield_qty' => 1,
                'yield_unit' => 'pcs',
                'prep_time' => 15,
                'cook_time' => 10,
                'ingredients' => [
                    ['item' => 'Kentang', 'qty' => 200, 'unit' => 'g', 'waste' => 20],
                    ['item' => 'Minyak Goreng Sawit', 'qty' => 300, 'unit' => 'ml', 'notes' => 'For deep frying'],
                    ['item' => 'Garam Halus', 'qty' => 3, 'unit' => 'g'],
                ],
            ],

            // Desserts
            [
                'name' => 'Pancake Stack',
                'description' => 'Fluffy buttermilk pancakes with maple syrup',
                'instructions' => "1. Mix dry ingredients\n2. Combine wet ingredients\n3. Fold together\n4. Cook on griddle\n5. Stack and serve with butter and syrup",
                'yield_qty' => 1,
                'yield_unit' => 'pcs',
                'prep_time' => 5,
                'cook_time' => 10,
                'ingredients' => [
                    ['item' => 'Tepung Terigu Protein Sedang', 'qty' => 100, 'unit' => 'g'],
                    ['item' => 'Susu Segar Full Cream', 'qty' => 120, 'unit' => 'ml'],
                    ['item' => 'Telur Ayam Negeri', 'qty' => 55, 'unit' => 'g'],
                    ['item' => 'Gula Pasir Putih', 'qty' => 20, 'unit' => 'g'],
                    ['item' => 'Butter Unsalted', 'qty' => 30, 'unit' => 'g'],
                    ['item' => 'Sirup Maple', 'qty' => 30, 'unit' => 'ml'],
                ],
            ],

            // Sauces (for kitchen prep)
            [
                'name' => 'Sambal Matah',
                'description' => 'Balinese raw shallot and lemongrass sambal',
                'instructions' => "1. Slice shallots thinly\n2. Slice lemongrass finely\n3. Chop chilies\n4. Mix all ingredients\n5. Add lime juice and oil\n6. Season with salt",
                'yield_qty' => 200,
                'yield_unit' => 'g',
                'prep_time' => 15,
                'cook_time' => 0,
                'ingredients' => [
                    ['item' => 'Bawang Merah', 'qty' => 100, 'unit' => 'g', 'waste' => 10],
                    ['item' => 'Serai', 'qty' => 30, 'unit' => 'g'],
                    ['item' => 'Jeruk Nipis', 'qty' => 30, 'unit' => 'g'],
                    ['item' => 'Minyak Kelapa', 'qty' => 50, 'unit' => 'ml'],
                    ['item' => 'Garam Halus', 'qty' => 5, 'unit' => 'g'],
                ],
            ],
            [
                'name' => 'Tomato Basil Sauce',
                'description' => 'Classic Italian tomato sauce for pasta',
                'instructions' => "1. Sauté garlic in olive oil\n2. Add canned tomatoes\n3. Simmer for 20 minutes\n4. Add fresh basil\n5. Season and blend if desired",
                'yield_qty' => 500,
                'yield_unit' => 'g',
                'prep_time' => 5,
                'cook_time' => 25,
                'ingredients' => [
                    ['item' => 'Tomat Kalengan Utuh', 'qty' => 2, 'unit' => 'pcs'],
                    ['item' => 'Bawang Putih', 'qty' => 20, 'unit' => 'g', 'waste' => 10],
                    ['item' => 'Bawang Bombay', 'qty' => 80, 'unit' => 'g', 'waste' => 10],
                    ['item' => 'Daun Basil Segar', 'qty' => 15, 'unit' => 'g'],
                    ['item' => 'Minyak Zaitun Extra Virgin', 'qty' => 40, 'unit' => 'ml'],
                    ['item' => 'Garam Halus', 'qty' => 5, 'unit' => 'g'],
                    ['item' => 'Gula Pasir Putih', 'qty' => 5, 'unit' => 'g'],
                ],
            ],
        ];
    }
}
