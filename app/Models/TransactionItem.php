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
        'item_name',
        'item_sku',
        'quantity',
        'unit_name',
        'unit_price',
        'cost_price',
        'discount_amount',
        'subtotal',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:4',
            'unit_price' => 'decimal:2',
            'cost_price' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'subtotal' => 'decimal:2',
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
}
