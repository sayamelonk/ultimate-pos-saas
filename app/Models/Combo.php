<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Combo extends Model
{
    use HasFactory, HasUuid;

    public const PRICING_FIXED = 'fixed';

    public const PRICING_SUM = 'sum';

    public const PRICING_DISCOUNT_PERCENT = 'discount_percent';

    public const PRICING_DISCOUNT_AMOUNT = 'discount_amount';

    protected $fillable = [
        'product_id',
        'pricing_type',
        'discount_value',
        'allow_substitutions',
        'min_items',
        'max_items',
    ];

    protected function casts(): array
    {
        return [
            'discount_value' => 'decimal:2',
            'allow_substitutions' => 'boolean',
            'min_items' => 'integer',
            'max_items' => 'integer',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(ComboItem::class)->orderBy('sort_order');
    }

    public function calculatePrice(): float
    {
        $itemsTotal = $this->items->sum(function ($item) {
            if ($item->product) {
                return $item->product->base_price * $item->quantity;
            }

            return 0;
        });

        return match ($this->pricing_type) {
            self::PRICING_FIXED => (float) $this->product->base_price,
            self::PRICING_SUM => $itemsTotal,
            self::PRICING_DISCOUNT_PERCENT => $itemsTotal * (1 - ($this->discount_value / 100)),
            self::PRICING_DISCOUNT_AMOUNT => max(0, $itemsTotal - $this->discount_value),
            default => (float) $this->product->base_price,
        };
    }

    public function getSavingsAttribute(): float
    {
        $itemsTotal = $this->items->sum(function ($item) {
            if ($item->product) {
                return $item->product->base_price * $item->quantity;
            }

            return 0;
        });

        return max(0, $itemsTotal - $this->product->base_price);
    }
}
