<?php

namespace App\Services\Menu;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\VariantOption;
use Illuminate\Support\Str;

class ProductService
{
    public function generateVariants(Product $product): void
    {
        $product->load('variantGroups.activeOptions');

        if ($product->variantGroups->isEmpty()) {
            return;
        }

        // Get all option combinations
        $optionSets = $product->variantGroups->map(function ($group) {
            return $group->activeOptions->pluck('id')->toArray();
        })->toArray();

        $combinations = $this->generateCombinations($optionSets);

        // Delete existing variants
        $product->variants()->delete();

        // Create new variants
        $counter = 1;
        foreach ($combinations as $optionIds) {
            $options = VariantOption::whereIn('id', $optionIds)
                ->orderBy('sort_order')
                ->get();

            $optionNames = $options->pluck('name')->toArray();
            $variantName = $product->name.' - '.implode(' / ', $optionNames);

            // Calculate price adjustment
            $priceAdjustment = $options->sum('price_adjustment');
            $finalPrice = $product->base_price + $priceAdjustment;

            ProductVariant::create([
                'product_id' => $product->id,
                'sku' => $product->sku.'-V'.$counter,
                'name' => $variantName,
                'option_ids' => $optionIds,
                'price' => $finalPrice,
                'cost_price' => $product->cost_price,
                'is_active' => true,
                'sort_order' => $counter - 1,
            ]);

            $counter++;
        }
    }

    public function duplicate(Product $product): Product
    {
        $product->load([
            'variantGroups',
            'modifierGroups',
            'productOutlets',
            'variants',
        ]);

        $newProduct = $product->replicate();
        $newProduct->sku = $product->sku.'-COPY';
        $newProduct->name = $product->name.' (Copy)';
        $newProduct->slug = Str::slug($newProduct->name);

        // Ensure unique slug
        $baseSlug = $newProduct->slug;
        $counter = 1;
        while (Product::where('tenant_id', $product->tenant_id)->where('slug', $newProduct->slug)->exists()) {
            $newProduct->slug = $baseSlug.'-'.$counter++;
        }

        $newProduct->is_active = false;
        $newProduct->save();

        // Duplicate variant groups relationship
        foreach ($product->variantGroups as $group) {
            $newProduct->variantGroups()->attach($group->id, [
                'id' => Str::uuid(),
                'is_required' => $group->pivot->is_required,
                'sort_order' => $group->pivot->sort_order,
            ]);
        }

        // Duplicate modifier groups relationship
        foreach ($product->modifierGroups as $group) {
            $newProduct->modifierGroups()->attach($group->id, [
                'id' => Str::uuid(),
                'is_required' => $group->pivot->is_required,
                'min_selections' => $group->pivot->min_selections,
                'max_selections' => $group->pivot->max_selections,
                'sort_order' => $group->pivot->sort_order,
            ]);
        }

        // Duplicate outlet availability
        foreach ($product->productOutlets as $outlet) {
            $newProduct->productOutlets()->create([
                'outlet_id' => $outlet->outlet_id,
                'is_available' => $outlet->is_available,
                'custom_price' => $outlet->custom_price,
                'is_featured' => $outlet->is_featured,
                'sort_order' => $outlet->sort_order,
            ]);
        }

        // Generate variants if variant product
        if ($newProduct->product_type === Product::TYPE_VARIANT) {
            $this->generateVariants($newProduct);
        }

        return $newProduct;
    }

    public function updatePricesFromRecipe(Product $product): void
    {
        if (! $product->recipe_id) {
            return;
        }

        $product->load('recipe');

        if ($product->recipe) {
            $product->update([
                'cost_price' => $product->recipe->estimated_cost / max(1, $product->recipe->yield_qty),
            ]);
        }
    }

    public function calculateMargin(Product $product): array
    {
        $costPrice = (float) $product->cost_price;
        $basePrice = (float) $product->base_price;

        if ($basePrice <= 0) {
            return [
                'margin_amount' => 0,
                'margin_percent' => 0,
                'markup_percent' => 0,
            ];
        }

        $marginAmount = $basePrice - $costPrice;
        $marginPercent = ($marginAmount / $basePrice) * 100;
        $markupPercent = $costPrice > 0 ? (($marginAmount / $costPrice) * 100) : 0;

        return [
            'margin_amount' => round($marginAmount, 2),
            'margin_percent' => round($marginPercent, 2),
            'markup_percent' => round($markupPercent, 2),
        ];
    }

    public function suggestPrice(Product $product, float $targetMargin): float
    {
        $costPrice = (float) $product->cost_price;

        if ($targetMargin >= 100) {
            return $costPrice;
        }

        return round($costPrice / (1 - ($targetMargin / 100)), 2);
    }

    private function generateCombinations(array $arrays, int $i = 0): array
    {
        if (! isset($arrays[$i])) {
            return [];
        }

        if ($i === count($arrays) - 1) {
            return array_map(fn ($item) => [$item], $arrays[$i]);
        }

        $combinations = [];
        $subsequentCombinations = $this->generateCombinations($arrays, $i + 1);

        foreach ($arrays[$i] as $item) {
            foreach ($subsequentCombinations as $combination) {
                $combinations[] = array_merge([$item], $combination);
            }
        }

        return $combinations;
    }
}
