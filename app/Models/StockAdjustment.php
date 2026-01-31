<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockAdjustment extends Model
{
    use HasFactory, HasUuid;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_CANCELLED = 'cancelled';

    public const TYPE_STOCK_TAKE = 'stock_take';

    public const TYPE_CORRECTION = 'correction';

    public const TYPE_OPENING_BALANCE = 'opening_balance';

    protected $fillable = [
        'tenant_id',
        'outlet_id',
        'adjustment_number',
        'adjustment_date',
        'type',
        'status',
        'reason',
        'notes',
        'created_by',
        'approved_by',
        'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'adjustment_date' => 'date',
            'approved_at' => 'datetime',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function outlet(): BelongsTo
    {
        return $this->belongsTo(Outlet::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(StockAdjustmentItem::class);
    }

    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function getTotalValueDifference(): float
    {
        return $this->items->sum('value_difference');
    }

    public function getPositiveAdjustments(): float
    {
        return $this->items->where('difference', '>', 0)->sum('difference');
    }

    public function getNegativeAdjustments(): float
    {
        return abs($this->items->where('difference', '<', 0)->sum('difference'));
    }
}
