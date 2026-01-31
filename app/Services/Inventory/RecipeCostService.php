<?php

namespace App\Services\Inventory;

use App\Models\Recipe;

class RecipeCostService
{
    public function updateRecipeCost(Recipe $recipe): Recipe
    {
        $recipe->load('items.inventoryItem');

        $totalCost = $recipe->calculateCost();

        $recipe->update([
            'estimated_cost' => $totalCost,
        ]);

        return $recipe->fresh();
    }

    public function getCostBreakdown(Recipe $recipe): array
    {
        $recipe->load('items.inventoryItem.unit');

        $ingredients = [];
        $totalCost = 0;

        foreach ($recipe->items as $item) {
            $itemCost = $item->calculateCost();
            $totalCost += $itemCost;

            $ingredients[] = [
                'name' => $item->inventoryItem->name,
                'sku' => $item->inventoryItem->sku,
                'quantity' => $item->quantity,
                'gross_quantity' => $item->getGrossQuantity(),
                'unit' => $item->inventoryItem->unit->abbreviation ?? '',
                'unit_cost' => $item->inventoryItem->cost_price ?? 0,
                'waste_percentage' => $item->waste_percentage ?? 0,
                'total_cost' => $itemCost,
                'percentage_of_total' => 0, // Will be calculated below
            ];
        }

        // Calculate percentage of total for each ingredient
        if ($totalCost > 0) {
            foreach ($ingredients as &$ingredient) {
                $ingredient['percentage_of_total'] = ($ingredient['total_cost'] / $totalCost) * 100;
            }
        }

        $yieldQty = $recipe->yield_qty ?? 1;
        $costPerUnit = $yieldQty > 0 ? $totalCost / $yieldQty : 0;

        return [
            'ingredients' => $ingredients,
            'total_cost' => $totalCost,
            'yield_quantity' => $yieldQty,
            'cost_per_unit' => $costPerUnit,
            'ingredient_count' => count($ingredients),
        ];
    }

    public function suggestSellingPrice(Recipe $recipe, float $marginPercentage = 30): float
    {
        $costPerUnit = $recipe->getCostPerUnit();

        if ($costPerUnit <= 0) {
            return 0;
        }

        // Price = Cost / (1 - Margin%)
        // E.g., if cost is 100 and margin is 30%, price = 100 / 0.7 = 142.86
        $marginFactor = 1 - ($marginPercentage / 100);

        return $marginFactor > 0 ? $costPerUnit / $marginFactor : 0;
    }

    public function calculateFoodCostPercentage(Recipe $recipe, float $sellingPrice): float
    {
        if ($sellingPrice <= 0) {
            return 0;
        }

        $costPerUnit = $recipe->getCostPerUnit();

        return ($costPerUnit / $sellingPrice) * 100;
    }

    public function recalculateAllRecipeCosts(string $tenantId): int
    {
        $recipes = Recipe::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->get();

        $count = 0;
        foreach ($recipes as $recipe) {
            $this->updateRecipeCost($recipe);
            $count++;
        }

        return $count;
    }

    public function getRecipesByIngredient(string $tenantId, string $inventoryItemId): \Illuminate\Support\Collection
    {
        return Recipe::where('tenant_id', $tenantId)
            ->whereHas('items', function ($query) use ($inventoryItemId) {
                $query->where('inventory_item_id', $inventoryItemId);
            })
            ->with(['items.inventoryItem', 'yieldUnit'])
            ->get();
    }
}
