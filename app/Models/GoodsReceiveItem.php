<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class GoodsReceiveItem extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'goods_receive_id',
        'purchase_order_item_id',
        'inventory_item_id',
        'unit_id',
        'unit_conversion',
        'quantity',
        'stock_qty',
        'unit_price',
        'discount_percent',
        'discount_amount',
        'tax_percent',
        'tax_amount',
        'total',
        'batch_number',
        'production_date',
        'expiry_date',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'unit_conversion' => 'decimal:4',
            'quantity' => 'decimal:4',
            'stock_qty' => 'decimal:4',
            'unit_price' => 'decimal:2',
            'discount_percent' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'tax_percent' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'total' => 'decimal:2',
            'production_date' => 'date',
            'expiry_date' => 'date',
        ];
    }

    public function goodsReceive(): BelongsTo
    {
        return $this->belongsTo(GoodsReceive::class);
    }

    public function purchaseOrderItem(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrderItem::class);
    }

    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function stockBatch(): HasOne
    {
        return $this->hasOne(StockBatch::class, 'goods_receive_item_id');
    }

    public function getCostPerStockUnit(): float
    {
        if ($this->stock_qty <= 0) {
            return 0;
        }

        return $this->total / $this->stock_qty;
    }

    public function calculateTotals(): void
    {
        $subtotal = $this->quantity * $this->unit_price;
        $this->discount_amount = $subtotal * ($this->discount_percent / 100);
        $afterDiscount = $subtotal - $this->discount_amount;
        $this->tax_amount = $afterDiscount * ($this->tax_percent / 100);
        $this->total = $afterDiscount + $this->tax_amount;
        $this->stock_qty = $this->quantity * $this->unit_conversion;
    }
}
