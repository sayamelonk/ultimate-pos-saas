<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseOrderItem extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'purchase_order_id',
        'inventory_item_id',
        'unit_id',
        'unit_conversion',
        'quantity',
        'unit_price',
        'discount_percent',
        'discount_amount',
        'tax_percent',
        'tax_amount',
        'total',
        'received_qty',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'unit_conversion' => 'decimal:4',
            'quantity' => 'decimal:4',
            'unit_price' => 'decimal:2',
            'discount_percent' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'tax_percent' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'total' => 'decimal:2',
            'received_qty' => 'decimal:4',
        ];
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function goodsReceiveItems(): HasMany
    {
        return $this->hasMany(GoodsReceiveItem::class);
    }

    public function getRemainingQty(): float
    {
        return $this->quantity - $this->received_qty;
    }

    public function isFullyReceived(): bool
    {
        return $this->received_qty >= $this->quantity;
    }

    public function getStockQuantity(): float
    {
        return $this->quantity * $this->unit_conversion;
    }

    public function calculateTotals(): void
    {
        $subtotal = $this->quantity * $this->unit_price;
        $this->discount_amount = $subtotal * ($this->discount_percent / 100);
        $afterDiscount = $subtotal - $this->discount_amount;
        $this->tax_amount = $afterDiscount * ($this->tax_percent / 100);
        $this->total = $afterDiscount + $this->tax_amount;
    }
}
