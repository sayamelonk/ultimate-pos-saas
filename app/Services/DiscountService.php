<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Discount;
use Illuminate\Support\Collection;

class DiscountService
{
    public function getApplicableDiscounts(
        string $tenantId,
        string $outletId,
        array $items,
        ?string $customerId = null
    ): Collection {
        $customer = $customerId ? Customer::find($customerId) : null;
        $subtotal = $this->calculateSubtotal($items);
        $totalQty = $this->calculateTotalQuantity($items);

        $discounts = Discount::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->where('is_auto_apply', true)
            ->where('valid_from', '<=', now()->toDateString())
            ->where(function ($query) {
                $query->whereNull('valid_until')
                    ->orWhere('valid_until', '>=', now()->toDateString());
            })
            ->where(function ($query) {
                $query->whereNull('usage_limit')
                    ->orWhereColumn('usage_count', '<', 'usage_limit');
            })
            ->get();

        return $discounts->filter(function (Discount $discount) use ($outletId, $customer, $subtotal, $totalQty) {
            if (! $discount->isApplicableToOutlet($outletId)) {
                return false;
            }

            if (! $discount->isApplicableToMember($customer)) {
                return false;
            }

            if ($discount->min_purchase && $subtotal < $discount->min_purchase) {
                return false;
            }

            if ($discount->min_qty && $totalQty < $discount->min_qty) {
                return false;
            }

            return true;
        });
    }

    public function validateDiscountCode(
        string $tenantId,
        string $code,
        string $outletId,
        float $subtotal,
        ?string $customerId = null
    ): ?Discount {
        $customer = $customerId ? Customer::find($customerId) : null;

        $discount = Discount::where('tenant_id', $tenantId)
            ->where('code', $code)
            ->where('is_active', true)
            ->first();

        if (! $discount) {
            return null;
        }

        if (! $discount->isValid()) {
            return null;
        }

        if (! $discount->isApplicableToOutlet($outletId)) {
            return null;
        }

        if (! $discount->isApplicableToMember($customer)) {
            return null;
        }

        if ($discount->min_purchase && $subtotal < $discount->min_purchase) {
            return null;
        }

        return $discount;
    }

    public function calculateDiscount(Discount $discount, float $subtotal, int $quantity = 1): float
    {
        return $discount->calculateDiscount($subtotal, $quantity);
    }

    public function applyDiscount(Discount $discount): void
    {
        $discount->incrementUsage();
    }

    public function calculateOrderDiscount(
        array $discounts,
        float $subtotal,
        array $items = []
    ): array {
        $totalDiscount = 0;
        $appliedDiscounts = [];

        foreach ($discounts as $discountData) {
            $discount = null;

            if (isset($discountData['discount_id'])) {
                $discount = Discount::find($discountData['discount_id']);
            }

            $discountAmount = 0;

            if ($discount) {
                $quantity = isset($discountData['item_id']) ? $this->getItemQuantity($items, $discountData['item_id']) : count($items);
                $discountAmount = $this->calculateDiscount($discount, $subtotal, $quantity);
            } elseif (isset($discountData['type']) && isset($discountData['value'])) {
                if ($discountData['type'] === 'percentage') {
                    $discountAmount = ($subtotal * $discountData['value']) / 100;
                    if (isset($discountData['max_discount']) && $discountAmount > $discountData['max_discount']) {
                        $discountAmount = $discountData['max_discount'];
                    }
                } else {
                    $discountAmount = $discountData['value'];
                }
            }

            $discountAmount = min($discountAmount, $subtotal - $totalDiscount);

            if ($discountAmount > 0) {
                $totalDiscount += $discountAmount;
                $appliedDiscounts[] = [
                    'discount_id' => $discount?->id,
                    'discount_name' => $discount?->name ?? ($discountData['name'] ?? 'Manual Discount'),
                    'type' => $discount?->type ?? ($discountData['type'] ?? 'fixed_amount'),
                    'value' => $discount?->value ?? ($discountData['value'] ?? 0),
                    'amount' => $discountAmount,
                    'item_id' => $discountData['item_id'] ?? null,
                ];
            }
        }

        return [
            'total_discount' => $totalDiscount,
            'applied_discounts' => $appliedDiscounts,
        ];
    }

    private function calculateSubtotal(array $items): float
    {
        return collect($items)->sum(fn ($item) => ($item['quantity'] ?? 1) * ($item['unit_price'] ?? 0));
    }

    private function calculateTotalQuantity(array $items): int
    {
        return (int) collect($items)->sum(fn ($item) => $item['quantity'] ?? 1);
    }

    private function getItemQuantity(array $items, string $itemId): int
    {
        foreach ($items as $item) {
            if (($item['inventory_item_id'] ?? $item['id'] ?? null) === $itemId) {
                return (int) ($item['quantity'] ?? 1);
            }
        }

        return 1;
    }
}
