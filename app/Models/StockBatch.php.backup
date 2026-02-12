<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockBatch extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'outlet_id',
        'inventory_item_id',
        'batch_number',
        'production_date',
        'expiry_date',
        'initial_qty',
        'current_qty',
        'cost_price',
        'goods_receive_item_id',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'production_date' => 'date',
            'expiry_date' => 'date',
            'initial_qty' => 'decimal:4',
            'current_qty' => 'decimal:4',
            'cost_price' => 'decimal:2',
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

    public function goodsReceiveItem(): BelongsTo
    {
        return $this->belongsTo(GoodsReceiveItem::class);
    }

    public function movements(): HasMany
    {
        return $this->hasMany(StockMovement::class, 'batch_id');
    }

    public function isExpired(): bool
    {
        return $this->expiry_date && $this->expiry_date->isPast();
    }

    public function isExpiringSoon(int $days = 7): bool
    {
        return $this->expiry_date && $this->expiry_date->diffInDays(now()) <= $days;
    }

    public function isDepleted(): bool
    {
        return $this->current_qty <= 0;
    }

    public function getBatchValue(): float
    {
        return $this->current_qty * $this->cost_price;
    }
}
