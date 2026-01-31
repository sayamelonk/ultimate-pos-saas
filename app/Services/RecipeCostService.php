<?php

namespace App\Services;

use App\Models\Recipe;
use App\Models\RecipeItem;
use App\Models\Unit;
use Illuminate\Support\Collection;

class RecipeCostService
{
    /**
     * Calculate recipe cost based on current ingredient prices
     */
    public function calculateRecipeCost(Recipe $recipe): float
    {
        $recipe->load('items.inventoryItem', 'items.unit');

        $totalCost = 0;

        foreach ($recipe->items as $item) {
            $totalCost += $this->calculateItemCost($item);
        }

        return $totalCost;
    }

    /**
     * Calculate cost for a single recipe item
     */
    public function calculateItemCost(RecipeItem $item): float
    {
        $inventoryItem = $item->inventoryItem;
        if (! $inventoryItem) {
            return 0;
        }

        // Get quantity in stock unit
        $quantityInStockUnit = $this->convertToStockUnit(
            $item->quantity,
            $item->unit,
            $inventoryItem->unit
        );

        // Apply waste factor
        $wasteFactor = 1 + ($item->waste_percentage / 100);
        $grossQuantity = $quantityInStockUnit * $wasteFactor;

        // Calculate cost
        return $grossQuantity * $inventoryItem->cost_price;
    }

    /**
     * Update recipe estimated cost
     */
    public function updateRecipeCost(Recipe $recipe): Recipe
    {
        $recipe->estimated_cost = $this->calculateRecipeCost($recipe);
        $recipe->save();

        return $recipe;
    }

    /**
     * Update all recipe costs for a tenant
     */
    public function updateAllRecipeCosts(string $tenantId): int
    {
        $recipes = Recipe::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->get();

        foreach ($recipes as $recipe) {
            $this->updateRecipeCost($recipe);
        }

        return $recipes->count();
    }

    /**
     * Get cost breakdown for a recipe
     */
    public function getCostBreakdown(Recipe $recipe): Collection
    {
        $recipe->load('items.inventoryItem', 'items.unit');

        return $recipe->items->map(function ($item) {
            $cost = $this->calculateItemCost($item);

            return [
                'inventory_item_id' => $item->inventory_item_id,
                'inventory_item_name' => $item->inventoryItem?->name,
                'quantity' => $item->quantity,
                'unit' => $item->unit?->abbreviation,
                'waste_percentage' => $item->waste_percentage,
                'gross_quantity' => $item->getGrossQuantity(),
                'unit_cost' => $item->inventoryItem?->cost_price ?? 0,
                'total_cost' => $cost,
            ];
        });
    }

    /**
     * Get cost per yield unit
     */
    public function getCostPerUnit(Recipe $recipe): float
    {
        if ($recipe->yield_qty <= 0) {
            return 0;
        }

        $totalCost = $this->calculateRecipeCost($recipe);

        return $totalCost / $recipe->yield_qty;
    }

    /**
     * Simulate cost with different ingredient prices
     */
    public function simulateCost(Recipe $recipe, array $priceOverrides): float
    {
        $recipe->load('items.inventoryItem', 'items.unit');

        $totalCost = 0;

        foreach ($recipe->items as $item) {
            $inventoryItem = $item->inventoryItem;
            if (! $inventoryItem) {
                continue;
            }

            // Use override price if provided
            $costPrice = $priceOverrides[$item->inventory_item_id] ?? $inventoryItem->cost_price;

            $quantityInStockUnit = $this->convertToStockUnit(
                $item->quantity,
                $item->unit,
                $inventoryItem->unit
            );

            $wasteFactor = 1 + ($item->waste_percentage / 100);
            $grossQuantity = $quantityInStockUnit * $wasteFactor;

            $totalCost += $grossQuantity * $costPrice;
        }

        return $totalCost;
    }

    /**
     * Find recipes affected by ingredient price change
     */
    public function getAffectedRecipes(string $inventoryItemId): Collection
    {
        return Recipe::whereHas('items', function ($query) use ($inventoryItemId) {
            $query->where('inventory_item_id', $inventoryItemId);
        })
            ->where('is_active', true)
            ->with(['items' => function ($query) use ($inventoryItemId) {
                $query->where('inventory_item_id', $inventoryItemId);
            }])
            ->get();
    }

    /**
     * Calculate food cost percentage
     */
    public function calculateFoodCostPercentage(Recipe $recipe, float $sellingPrice): float
    {
        if ($sellingPrice <= 0) {
            return 0;
        }

        $cost = $this->calculateRecipeCost($recipe);

        return ($cost / $sellingPrice) * 100;
    }

    /**
     * Suggest selling price based on target food cost percentage
     */
    public function suggestSellingPrice(Recipe $recipe, float $targetFoodCostPercent): float
    {
        if ($targetFoodCostPercent <= 0 || $targetFoodCostPercent >= 100) {
            return 0;
        }

        $cost = $this->calculateRecipeCost($recipe);

        return $cost / ($targetFoodCostPercent / 100);
    }

    /**
     * Convert quantity between units
     */
    private function convertToStockUnit(float $quantity, ?Unit $fromUnit, ?Unit $stockUnit): float
    {
        if (! $fromUnit || ! $stockUnit) {
            return $quantity;
        }

        // If same unit, no conversion needed
        if ($fromUnit->id === $stockUnit->id) {
            return $quantity;
        }

        // If both have same base unit, convert through base
        if ($fromUnit->base_unit_id && $fromUnit->base_unit_id === $stockUnit->base_unit_id) {
            $inBase = $quantity * $fromUnit->conversion_factor;

            return $inBase / $stockUnit->conversion_factor;
        }

        // If fromUnit is derived from stockUnit
        if ($fromUnit->base_unit_id === $stockUnit->id) {
            return $quantity * $fromUnit->conversion_factor;
        }

        // If stockUnit is derived from fromUnit
        if ($stockUnit->base_unit_id === $fromUnit->id) {
            return $quantity / $stockUnit->conversion_factor;
        }

        // Cannot convert - return original
        return $quantity;
    }
}
