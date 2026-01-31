<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockTransferItem extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'stock_transfer_id',
        'inventory_item_id',
        'batch_id',
        'quantity',
        'received_qty',
        'unit_id',
        'cost_price',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:4',
            'received_qty' => 'decimal:4',
            'cost_price' => 'decimal:2',
        ];
    }

    public function stockTransfer(): BelongsTo
    {
        return $this->belongsTo(StockTransfer::class);
    }

    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class);
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(StockBatch::class, 'batch_id');
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function getValue(): float
    {
        return $this->quantity * $this->cost_price;
    }

    public function getReceivedValue(): float
    {
        return ($this->received_qty ?? 0) * $this->cost_price;
    }

    public function isFullyReceived(): bool
    {
        return $this->received_qty !== null && $this->received_qty >= $this->quantity;
    }

    public function hasVariance(): bool
    {
        return $this->received_qty !== null && $this->received_qty != $this->quantity;
    }

    public function getVariance(): float
    {
        if ($this->received_qty === null) {
            return 0;
        }

        return $this->received_qty - $this->quantity;
    }
}
