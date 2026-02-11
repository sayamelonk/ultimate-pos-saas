<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuthorizationSetting extends Model
{
    protected $fillable = [
        'tenant_id',
        'require_auth_void',
        'require_auth_refund',
        'require_auth_discount',
        'discount_threshold_percent',
        'require_auth_price_override',
        'require_auth_no_sale',
        'require_auth_reprint',
        'require_auth_cancel_order',
        'pin_length',
        'max_pin_attempts',
        'lockout_minutes',
    ];

    protected function casts(): array
    {
        return [
            'require_auth_void' => 'boolean',
            'require_auth_refund' => 'boolean',
            'require_auth_discount' => 'boolean',
            'discount_threshold_percent' => 'decimal:2',
            'require_auth_price_override' => 'boolean',
            'require_auth_no_sale' => 'boolean',
            'require_auth_reprint' => 'boolean',
            'require_auth_cancel_order' => 'boolean',
            'pin_length' => 'integer',
            'max_pin_attempts' => 'integer',
            'lockout_minutes' => 'integer',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public static function getForTenant(string $tenantId): self
    {
        return self::firstOrCreate(
            ['tenant_id' => $tenantId],
            [
                'require_auth_void' => true,
                'require_auth_refund' => true,
                'require_auth_discount' => false,
                'discount_threshold_percent' => 20,
                'require_auth_price_override' => true,
                'require_auth_no_sale' => true,
                'require_auth_reprint' => false,
                'require_auth_cancel_order' => true,
                'pin_length' => 4,
                'max_pin_attempts' => 3,
                'lockout_minutes' => 5,
            ]
        );
    }

    public function requiresAuth(string $action, ?float $discountPercent = null): bool
    {
        return match ($action) {
            'void' => $this->require_auth_void,
            'refund' => $this->require_auth_refund,
            'discount' => $this->require_auth_discount && $discountPercent > $this->discount_threshold_percent,
            'price_override' => $this->require_auth_price_override,
            'no_sale' => $this->require_auth_no_sale,
            'reprint' => $this->require_auth_reprint,
            'cancel_order' => $this->require_auth_cancel_order,
            default => false,
        };
    }
}
