<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockAdjustmentItem extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'stock_adjustment_id',
        'inventory_item_id',
        'batch_id',
        'system_qty',
        'actual_qty',
        'difference',
        'cost_price',
        'value_difference',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'system_qty' => 'decimal:4',
            'actual_qty' => 'decimal:4',
            'difference' => 'decimal:4',
            'cost_price' => 'decimal:2',
            'value_difference' => 'decimal:2',
        ];
    }

    public function stockAdjustment(): BelongsTo
    {
        return $this->belongsTo(StockAdjustment::class);
    }

    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class);
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(StockBatch::class, 'batch_id');
    }

    public function calculateDifference(): void
    {
        $this->difference = $this->actual_qty - $this->system_qty;
        $this->value_difference = $this->difference * $this->cost_price;
    }

    public function isIncrease(): bool
    {
        return $this->difference > 0;
    }

    public function isDecrease(): bool
    {
        return $this->difference < 0;
    }

    public function hasVariance(): bool
    {
        return $this->difference != 0;
    }
}
