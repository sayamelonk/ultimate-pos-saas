<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class StockMovement extends Model
{
    use HasFactory, HasUuid;

    public const TYPE_IN = 'in';

    public const TYPE_OUT = 'out';

    public const TYPE_ADJUSTMENT = 'adjustment';

    public const TYPE_TRANSFER_IN = 'transfer_in';

    public const TYPE_TRANSFER_OUT = 'transfer_out';

    public const TYPE_WASTE = 'waste';

    protected $fillable = [
        'outlet_id',
        'inventory_item_id',
        'batch_id',
        'type',
        'reference_type',
        'reference_id',
        'quantity',
        'cost_price',
        'stock_before',
        'stock_after',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:4',
            'cost_price' => 'decimal:2',
            'stock_before' => 'decimal:4',
            'stock_after' => 'decimal:4',
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

    public function batch(): BelongsTo
    {
        return $this->belongsTo(StockBatch::class, 'batch_id');
    }

    public function reference(): MorphTo
    {
        return $this->morphTo('reference', 'reference_type', 'reference_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isIncoming(): bool
    {
        return in_array($this->type, [self::TYPE_IN, self::TYPE_TRANSFER_IN, self::TYPE_ADJUSTMENT]) && $this->quantity > 0;
    }

    public function isOutgoing(): bool
    {
        return in_array($this->type, [self::TYPE_OUT, self::TYPE_TRANSFER_OUT, self::TYPE_WASTE]) || $this->quantity < 0;
    }

    public function getMovementValue(): float
    {
        return abs($this->quantity) * $this->cost_price;
    }

    // Accessor for backward compatibility with views
    public function getMovementTypeAttribute(): string
    {
        return $this->type;
    }

    public function getUnitCostAttribute(): ?float
    {
        return $this->cost_price;
    }

    public function getUserAttribute(): ?User
    {
        return $this->createdBy;
    }
}
