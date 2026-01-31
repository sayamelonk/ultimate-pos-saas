<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryStock extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'outlet_id',
        'inventory_item_id',
        'quantity',
        'reserved_qty',
        'avg_cost',
        'last_cost',
        'last_received_at',
        'last_issued_at',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:4',
            'reserved_qty' => 'decimal:4',
            'avg_cost' => 'decimal:2',
            'last_cost' => 'decimal:2',
            'last_received_at' => 'datetime',
            'last_issued_at' => 'datetime',
        ];
    }

    public function outlet(): BelongsTo
    {
        return $this->belongsTo(Outlet::class);
    }

    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class);
    }

    public function getAvailableQuantity(): float
    {
        return $this->quantity - $this->reserved_qty;
    }

    public function getStockValue(): float
    {
        return $this->quantity * $this->avg_cost;
    }

    public function isLowStock(): bool
    {
        return $this->quantity <= $this->inventoryItem->reorder_point;
    }

    public function isOverStock(): bool
    {
        $maxStock = $this->inventoryItem->max_stock;

        return $maxStock && $this->quantity > $maxStock;
    }
}
