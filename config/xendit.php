<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Xendit API Keys
    |--------------------------------------------------------------------------
    |
    | API keys untuk Xendit payment gateway. Pastikan menggunakan
    | staging keys untuk development dan production keys untuk live.
    |
    */

    'secret_key' => env('XENDIT_SECRET_KEY'),
    'public_key' => env('XENDIT_PUBLIC_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Webhook Verification Token
    |--------------------------------------------------------------------------
    |
    | Token untuk memverifikasi callback webhook dari Xendit.
    | Dapat dilihat di Xendit Dashboard > Settings > Callbacks.
    |
    */

    'webhook_token' => env('XENDIT_WEBHOOK_TOKEN'),

    /*
    |--------------------------------------------------------------------------
    | Production Mode
    |--------------------------------------------------------------------------
    |
    | Set ke true untuk menggunakan Xendit production environment.
    |
    */

    'is_production' => env('XENDIT_IS_PRODUCTION', false),

    /*
    |--------------------------------------------------------------------------
    | Invoice Settings
    |--------------------------------------------------------------------------
    |
    | Konfigurasi default untuk invoice yang dibuat.
    |
    */

    'invoice' => [
        'currency' => 'IDR',
        'invoice_duration' => 86400, // 24 jam dalam detik
        'reminder_time' => 1, // Reminder 1 jam sebelum expired
        'success_redirect_url' => '/subscription/payment/success',
        'failure_redirect_url' => '/subscription/payment/failed',
    ],

    /*
    |--------------------------------------------------------------------------
    | Payment Methods
    |--------------------------------------------------------------------------
    |
    | Metode pembayaran yang tersedia untuk customer.
    |
    */

    'payment_methods' => [
        'CREDIT_CARD',
        'BCA',
        'BNI',
        'BSI',
        'BRI',
        'MANDIRI',
        'PERMATA',
        'ALFAMART',
        'INDOMARET',
        'OVO',
        'DANA',
        'SHOPEEPAY',
        'LINKAJA',
        'QRIS',
    ],
];
