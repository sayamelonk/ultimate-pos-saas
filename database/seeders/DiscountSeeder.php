<?php

namespace Database\Seeders;

use App\Models\Discount;
use App\Models\Tenant;
use Illuminate\Database\Seeder;

class DiscountSeeder extends Seeder
{
    public function run(): void
    {
        $tenants = Tenant::all();

        $discounts = [
            [
                'code' => 'WELCOME10',
                'name' => 'Welcome Discount 10%',
                'description' => 'Welcome discount for new customers',
                'type' => Discount::TYPE_PERCENTAGE,
                'scope' => Discount::SCOPE_ORDER,
                'value' => 10,
                'max_discount' => 50000,
                'min_purchase' => 100000,
                'member_only' => false,
                'is_auto_apply' => false,
            ],
            [
                'code' => 'MEMBER15',
                'name' => 'Member Discount 15%',
                'description' => '15% discount for all members',
                'type' => Discount::TYPE_PERCENTAGE,
                'scope' => Discount::SCOPE_ORDER,
                'value' => 15,
                'max_discount' => 75000,
                'min_purchase' => 150000,
                'member_only' => true,
                'membership_levels' => ['silver', 'gold', 'platinum'],
                'is_auto_apply' => false,
            ],
            [
                'code' => 'VIP20',
                'name' => 'VIP Discount 20%',
                'description' => '20% discount for VIP members',
                'type' => Discount::TYPE_PERCENTAGE,
                'scope' => Discount::SCOPE_ORDER,
                'value' => 20,
                'max_discount' => 100000,
                'min_purchase' => 200000,
                'member_only' => true,
                'membership_levels' => ['gold', 'platinum'],
                'is_auto_apply' => false,
            ],
            [
                'code' => 'FLAT25K',
                'name' => 'Flat Rp 25.000 Off',
                'description' => 'Flat Rp 25.000 discount for minimum purchase Rp 100.000',
                'type' => Discount::TYPE_FIXED_AMOUNT,
                'scope' => Discount::SCOPE_ORDER,
                'value' => 25000,
                'min_purchase' => 100000,
                'member_only' => false,
                'is_auto_apply' => false,
            ],
            [
                'code' => 'FLAT50K',
                'name' => 'Flat Rp 50.000 Off',
                'description' => 'Flat Rp 50.000 discount for minimum purchase Rp 200.000',
                'type' => Discount::TYPE_FIXED_AMOUNT,
                'scope' => Discount::SCOPE_ORDER,
                'value' => 50000,
                'min_purchase' => 200000,
                'member_only' => false,
                'is_auto_apply' => false,
            ],
            [
                'code' => 'HAPPY5',
                'name' => 'Happy Hour 5%',
                'description' => 'Happy hour discount - auto applied',
                'type' => Discount::TYPE_PERCENTAGE,
                'scope' => Discount::SCOPE_ORDER,
                'value' => 5,
                'max_discount' => 25000,
                'min_purchase' => 50000,
                'member_only' => false,
                'is_auto_apply' => true,
            ],
            [
                'code' => 'BIRTHDAY',
                'name' => 'Birthday Special 25%',
                'description' => '25% discount for birthday customers',
                'type' => Discount::TYPE_PERCENTAGE,
                'scope' => Discount::SCOPE_ORDER,
                'value' => 25,
                'max_discount' => 100000,
                'min_purchase' => 0,
                'member_only' => true,
                'is_auto_apply' => false,
            ],
            [
                'code' => 'WEEKEND10',
                'name' => 'Weekend Special 10%',
                'description' => '10% discount on weekends',
                'type' => Discount::TYPE_PERCENTAGE,
                'scope' => Discount::SCOPE_ORDER,
                'value' => 10,
                'max_discount' => 50000,
                'min_purchase' => 100000,
                'member_only' => false,
                'is_auto_apply' => false,
            ],
        ];

        foreach ($tenants as $tenant) {
            foreach ($discounts as $discount) {
                Discount::firstOrCreate(
                    [
                        'tenant_id' => $tenant->id,
                        'code' => $discount['code'],
                    ],
                    array_merge($discount, [
                        'tenant_id' => $tenant->id,
                        'is_active' => true,
                        'valid_from' => now(),
                        'valid_until' => now()->addYear(),
                    ])
                );
            }
        }
    }
}
