<?php

namespace Database\Seeders;

use App\Models\PaymentMethod;
use App\Models\Tenant;
use Illuminate\Database\Seeder;

class PaymentMethodSeeder extends Seeder
{
    public function run(): void
    {
        $tenants = Tenant::all();

        $paymentMethods = [
            [
                'code' => 'CASH',
                'name' => 'Cash',
                'type' => PaymentMethod::TYPE_CASH,
                'provider' => null,
                'icon' => 'cash',
                'charge_percentage' => 0,
                'charge_fixed' => 0,
                'requires_reference' => false,
                'opens_cash_drawer' => true,
                'sort_order' => 1,
            ],
            [
                'code' => 'DEBIT',
                'name' => 'Debit Card',
                'type' => PaymentMethod::TYPE_CARD,
                'provider' => 'Bank',
                'icon' => 'credit-card',
                'charge_percentage' => 0.5,
                'charge_fixed' => 0,
                'requires_reference' => true,
                'opens_cash_drawer' => false,
                'sort_order' => 2,
            ],
            [
                'code' => 'CREDIT',
                'name' => 'Credit Card',
                'type' => PaymentMethod::TYPE_CARD,
                'provider' => 'Bank',
                'icon' => 'credit-card',
                'charge_percentage' => 2.5,
                'charge_fixed' => 0,
                'requires_reference' => true,
                'opens_cash_drawer' => false,
                'sort_order' => 3,
            ],
            [
                'code' => 'GOPAY',
                'name' => 'GoPay',
                'type' => PaymentMethod::TYPE_DIGITAL_WALLET,
                'provider' => 'Gojek',
                'icon' => 'qrcode',
                'charge_percentage' => 1.5,
                'charge_fixed' => 0,
                'requires_reference' => true,
                'opens_cash_drawer' => false,
                'sort_order' => 4,
            ],
            [
                'code' => 'OVO',
                'name' => 'OVO',
                'type' => PaymentMethod::TYPE_DIGITAL_WALLET,
                'provider' => 'OVO',
                'icon' => 'qrcode',
                'charge_percentage' => 1.5,
                'charge_fixed' => 0,
                'requires_reference' => true,
                'opens_cash_drawer' => false,
                'sort_order' => 5,
            ],
            [
                'code' => 'DANA',
                'name' => 'DANA',
                'type' => PaymentMethod::TYPE_DIGITAL_WALLET,
                'provider' => 'DANA',
                'icon' => 'qrcode',
                'charge_percentage' => 1.5,
                'charge_fixed' => 0,
                'requires_reference' => true,
                'opens_cash_drawer' => false,
                'sort_order' => 6,
            ],
            [
                'code' => 'SHOPEEPAY',
                'name' => 'ShopeePay',
                'type' => PaymentMethod::TYPE_DIGITAL_WALLET,
                'provider' => 'Shopee',
                'icon' => 'qrcode',
                'charge_percentage' => 1.5,
                'charge_fixed' => 0,
                'requires_reference' => true,
                'opens_cash_drawer' => false,
                'sort_order' => 7,
            ],
            [
                'code' => 'QRIS',
                'name' => 'QRIS',
                'type' => PaymentMethod::TYPE_DIGITAL_WALLET,
                'provider' => 'QRIS',
                'icon' => 'qrcode',
                'charge_percentage' => 0.7,
                'charge_fixed' => 0,
                'requires_reference' => true,
                'opens_cash_drawer' => false,
                'sort_order' => 8,
            ],
            [
                'code' => 'TRANSFER',
                'name' => 'Bank Transfer',
                'type' => PaymentMethod::TYPE_TRANSFER,
                'provider' => 'Bank',
                'icon' => 'building',
                'charge_percentage' => 0,
                'charge_fixed' => 0,
                'requires_reference' => true,
                'opens_cash_drawer' => false,
                'sort_order' => 9,
            ],
        ];

        foreach ($tenants as $tenant) {
            foreach ($paymentMethods as $method) {
                PaymentMethod::firstOrCreate(
                    [
                        'tenant_id' => $tenant->id,
                        'code' => $method['code'],
                    ],
                    array_merge($method, [
                        'tenant_id' => $tenant->id,
                        'is_active' => true,
                    ])
                );
            }
        }
    }
}
