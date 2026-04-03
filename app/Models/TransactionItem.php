<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TransactionItem extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'transaction_id',
        'inventory_item_id',
        'product_id',
        'product_variant_id',
        'item_name',
        'item_sku',
        'quantity',
        'unit_name',
        'unit_price',
        'base_price',
        'variant_price_adjustment',
        'modifiers_total',
        'cost_price',
        'discount_amount',
        'subtotal',
        'notes',
        'modifiers',
        'item_notes',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:4',
            'unit_price' => 'decimal:2',
            'base_price' => 'decimal:2',
            'variant_price_adjustment' => 'decimal:2',
            'modifiers_total' => 'decimal:2',
            'cost_price' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'subtotal' => 'decimal:2',
            'modifiers' => 'array',
        ];
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class);
    }

    public function discounts(): HasMany
    {
        return $this->hasMany(TransactionDiscount::class);
    }

    public function getGrossAmount(): float
    {
        return $this->quantity * $this->unit_price;
    }

    public function getProfit(): float
    {
        return $this->subtotal - ($this->cost_price * $this->quantity);
    }

    public function getModifiersDisplayAttribute(): string
    {
        if (empty($this->modifiers)) {
            return '';
        }

        return collect($this->modifiers)
            ->pluck('name')
            ->implode(', ');
    }
}
