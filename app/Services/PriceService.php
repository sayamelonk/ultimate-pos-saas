<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\InventoryItem;
use App\Models\Price;

class PriceService
{
    public function getSellingPrice(string $itemId, string $outletId, ?string $customerId = null): float
    {
        $price = Price::where('inventory_item_id', $itemId)
            ->where('outlet_id', $outletId)
            ->where('is_active', true)
            ->first();

        if (! $price) {
            $item = InventoryItem::find($itemId);

            return $item ? (float) $item->cost_price * 1.5 : 0;
        }

        if ($customerId) {
            $customer = Customer::find($customerId);
            if ($customer && $customer->is_active && $customer->isMember() && $price->member_price) {
                return (float) $price->member_price;
            }
        }

        return (float) $price->selling_price;
    }

    public function setPrice(
        string $tenantId,
        string $itemId,
        string $outletId,
        float $sellingPrice,
        ?float $memberPrice = null,
        ?float $minSellingPrice = null
    ): Price {
        return Price::updateOrCreate(
            [
                'inventory_item_id' => $itemId,
                'outlet_id' => $outletId,
            ],
            [
                'tenant_id' => $tenantId,
                'selling_price' => $sellingPrice,
                'member_price' => $memberPrice,
                'min_selling_price' => $minSellingPrice,
                'is_active' => true,
            ]
        );
    }

    public function bulkUpdatePrices(string $tenantId, string $outletId, array $prices): int
    {
        $count = 0;

        foreach ($prices as $itemId => $priceData) {
            $this->setPrice(
                $tenantId,
                $itemId,
                $outletId,
                $priceData['selling_price'],
                $priceData['member_price'] ?? null,
                $priceData['min_selling_price'] ?? null
            );
            $count++;
        }

        return $count;
    }

    public function copyPrices(string $sourceOutletId, string $targetOutletId, ?float $adjustmentPercentage = null): int
    {
        $sourcePrices = Price::where('outlet_id', $sourceOutletId)
            ->where('is_active', true)
            ->get();

        $count = 0;

        foreach ($sourcePrices as $sourcePrice) {
            $sellingPrice = $sourcePrice->selling_price;
            $memberPrice = $sourcePrice->member_price;
            $minSellingPrice = $sourcePrice->min_selling_price;

            if ($adjustmentPercentage !== null) {
                $multiplier = 1 + ($adjustmentPercentage / 100);
                $sellingPrice = round($sellingPrice * $multiplier, 2);
                $memberPrice = $memberPrice ? round($memberPrice * $multiplier, 2) : null;
                $minSellingPrice = $minSellingPrice ? round($minSellingPrice * $multiplier, 2) : null;
            }

            Price::updateOrCreate(
                [
                    'inventory_item_id' => $sourcePrice->inventory_item_id,
                    'outlet_id' => $targetOutletId,
                ],
                [
                    'tenant_id' => $sourcePrice->tenant_id,
                    'selling_price' => $sellingPrice,
                    'member_price' => $memberPrice,
                    'min_selling_price' => $minSellingPrice,
                    'is_active' => true,
                ]
            );
            $count++;
        }

        return $count;
    }

    public function getPricesForOutlet(string $outletId): \Illuminate\Database\Eloquent\Collection
    {
        return Price::where('outlet_id', $outletId)
            ->where('is_active', true)
            ->with('inventoryItem')
            ->get();
    }

    public function getItemsWithoutPrice(string $tenantId, string $outletId): \Illuminate\Database\Eloquent\Collection
    {
        $pricedItemIds = Price::where('outlet_id', $outletId)
            ->where('is_active', true)
            ->pluck('inventory_item_id');

        return InventoryItem::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->whereNotIn('id', $pricedItemIds)
            ->get();
    }
}
