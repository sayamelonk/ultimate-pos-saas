<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Discount extends Model
{
    use HasFactory, HasUuid;

    public const TYPE_PERCENTAGE = 'percentage';

    public const TYPE_FIXED_AMOUNT = 'fixed_amount';

    public const TYPE_BUY_X_GET_Y = 'buy_x_get_y';

    public const SCOPE_ORDER = 'order';

    public const SCOPE_ITEM = 'item';

    protected $fillable = [
        'tenant_id',
        'code',
        'name',
        'description',
        'type',
        'scope',
        'value',
        'max_discount',
        'min_purchase',
        'min_qty',
        'member_only',
        'membership_levels',
        'applicable_outlets',
        'applicable_items',
        'valid_from',
        'valid_until',
        'usage_limit',
        'usage_count',
        'is_auto_apply',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'decimal:2',
            'max_discount' => 'decimal:2',
            'min_purchase' => 'decimal:2',
            'min_qty' => 'integer',
            'member_only' => 'boolean',
            'membership_levels' => 'array',
            'applicable_outlets' => 'array',
            'applicable_items' => 'array',
            'valid_from' => 'date',
            'valid_until' => 'date',
            'usage_limit' => 'integer',
            'usage_count' => 'integer',
            'is_auto_apply' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function transactionDiscounts(): HasMany
    {
        return $this->hasMany(TransactionDiscount::class);
    }

    public function isValid(): bool
    {
        if (! $this->is_active) {
            return false;
        }

        $today = now()->startOfDay();

        if ($this->valid_from && $this->valid_from->startOfDay()->isAfter($today)) {
            return false;
        }

        if ($this->valid_until && $this->valid_until->endOfDay()->isBefore(now())) {
            return false;
        }

        if ($this->usage_limit && $this->usage_count >= $this->usage_limit) {
            return false;
        }

        return true;
    }

    public function isApplicableToOutlet(?string $outletId): bool
    {
        if (! $this->applicable_outlets || empty($this->applicable_outlets)) {
            return true;
        }

        return in_array($outletId, $this->applicable_outlets);
    }

    public function isApplicableToItem(?string $itemId): bool
    {
        if (! $this->applicable_items || empty($this->applicable_items)) {
            return true;
        }

        return in_array($itemId, $this->applicable_items);
    }

    public function isApplicableToMember(?Customer $customer): bool
    {
        if (! $this->member_only) {
            return true;
        }

        if (! $customer) {
            return false;
        }

        if (! $this->membership_levels || empty($this->membership_levels)) {
            return $customer->isMember();
        }

        return in_array($customer->membership_level, $this->membership_levels);
    }

    public function calculateDiscount(float $subtotal, int $quantity = 1): float
    {
        if ($this->min_purchase && $subtotal < $this->min_purchase) {
            return 0;
        }

        if ($this->min_qty && $quantity < $this->min_qty) {
            return 0;
        }

        $discount = 0;

        if ($this->type === self::TYPE_PERCENTAGE) {
            $discount = ($subtotal * $this->value) / 100;

            if ($this->max_discount && $discount > $this->max_discount) {
                $discount = $this->max_discount;
            }
        } elseif ($this->type === self::TYPE_FIXED_AMOUNT) {
            $discount = $this->value;
        }

        return min($discount, $subtotal);
    }

    public function incrementUsage(): void
    {
        $this->increment('usage_count');
    }

    public static function getTypes(): array
    {
        return [
            self::TYPE_PERCENTAGE => 'Percentage',
            self::TYPE_FIXED_AMOUNT => 'Fixed Amount',
            self::TYPE_BUY_X_GET_Y => 'Buy X Get Y',
        ];
    }

    public static function getScopes(): array
    {
        return [
            self::SCOPE_ORDER => 'Order Level',
            self::SCOPE_ITEM => 'Item Level',
        ];
    }
}
