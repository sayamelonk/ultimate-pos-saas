<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\CustomerPoint;
use Illuminate\Database\Eloquent\Collection;

class CustomerService
{
    public const POINTS_PER_AMOUNT = 10000;

    public const POINT_VALUE = 100;

    public function calculatePointsEarned(float $grandTotal): float
    {
        return floor($grandTotal / self::POINTS_PER_AMOUNT);
    }

    public function getPointsValue(float $points): float
    {
        return $points * self::POINT_VALUE;
    }

    public function addPoints(
        Customer $customer,
        float $points,
        ?string $transactionId,
        string $userId,
        ?string $description = null
    ): CustomerPoint {
        return $customer->addPoints($points, $transactionId, $userId, $description ?? 'Points earned from purchase');
    }

    public function redeemPoints(
        Customer $customer,
        float $points,
        ?string $transactionId,
        string $userId,
        ?string $description = null
    ): CustomerPoint {
        if ($points > $customer->total_points) {
            throw new \InvalidArgumentException('Insufficient points balance');
        }

        return $customer->redeemPoints($points, $transactionId, $userId, $description ?? 'Points redeemed for discount');
    }

    public function adjustPoints(
        Customer $customer,
        float $points,
        string $userId,
        ?string $description = null
    ): CustomerPoint {
        return $customer->adjustPoints($points, $userId, $description ?? 'Manual points adjustment');
    }

    public function updateMembershipLevel(Customer $customer): void
    {
        $newLevel = $this->determineMembershipLevel($customer->total_spent);

        if ($newLevel !== $customer->membership_level) {
            $customer->update(['membership_level' => $newLevel]);
        }
    }

    public function determineMembershipLevel(float $totalSpent): string
    {
        if ($totalSpent >= 50000000) {
            return Customer::LEVEL_PLATINUM;
        }

        if ($totalSpent >= 20000000) {
            return Customer::LEVEL_GOLD;
        }

        if ($totalSpent >= 5000000) {
            return Customer::LEVEL_SILVER;
        }

        return Customer::LEVEL_REGULAR;
    }

    public function incrementVisit(Customer $customer): void
    {
        $customer->increment('total_visits');
    }

    public function addSpending(Customer $customer, float $amount): void
    {
        $customer->increment('total_spent', $amount);
        $this->updateMembershipLevel($customer);
    }

    public function searchCustomers(string $tenantId, string $search, int $limit = 10): Collection
    {
        return Customer::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            })
            ->orderBy('name')
            ->limit($limit)
            ->get();
    }

    public function getPointHistory(Customer $customer, int $limit = 20): Collection
    {
        return $customer->points()
            ->with('transaction', 'createdByUser')
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();
    }

    public function generateCustomerCode(string $tenantId): string
    {
        $lastCustomer = Customer::where('tenant_id', $tenantId)
            ->orderByDesc('code')
            ->first();

        if (! $lastCustomer) {
            return 'CUST0001';
        }

        $lastNumber = (int) preg_replace('/[^0-9]/', '', $lastCustomer->code);

        return 'CUST'.str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
    }
}
