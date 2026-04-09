<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KitchenOrderItem extends Model
{
    use HasFactory, HasUuid;

    public const STATUS_PENDING = 'pending';

    public const STATUS_PREPARING = 'preparing';

    public const STATUS_READY = 'ready';

    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'kitchen_order_id',
        'transaction_item_id',
        'station_id',
        'item_name',
        'quantity',
        'modifiers',
        'notes',
        'status',
        'started_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:4',
            'modifiers' => 'array',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function kitchenOrder(): BelongsTo
    {
        return $this->belongsTo(KitchenOrder::class);
    }

    public function transactionItem(): BelongsTo
    {
        return $this->belongsTo(TransactionItem::class);
    }

    public function station(): BelongsTo
    {
        return $this->belongsTo(KitchenStation::class, 'station_id');
    }

    // Status helpers
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isPreparing(): bool
    {
        return $this->status === self::STATUS_PREPARING;
    }

    public function isReady(): bool
    {
        return $this->status === self::STATUS_READY;
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    public function canStart(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function canMarkReady(): bool
    {
        return $this->status === self::STATUS_PREPARING;
    }

    // Actions
    public function start(): void
    {
        $this->update([
            'status' => self::STATUS_PREPARING,
            'started_at' => now(),
        ]);
    }

    public function markReady(): void
    {
        $this->update([
            'status' => self::STATUS_READY,
            'completed_at' => now(),
        ]);

        // Check if all items in order are ready
        $this->kitchenOrder->checkAndUpdateStatus();
    }

    public function cancel(): void
    {
        $this->update(['status' => self::STATUS_CANCELLED]);
    }

    // Computed
    public function getModifiersDisplayAttribute(): string
    {
        if (empty($this->modifiers)) {
            return '';
        }

        return collect($this->modifiers)
            ->pluck('name')
            ->implode(', ');
    }

    public function getPreparationTimeAttribute(): ?int
    {
        if (! $this->started_at) {
            return null;
        }

        $endTime = $this->completed_at ?? now();

        return $this->started_at->diffInMinutes($endTime);
    }

    // Scopes
    public function scopeForStation($query, ?string $stationId)
    {
        if ($stationId) {
            return $query->where('station_id', $stationId);
        }

        return $query;
    }

    public function scopeWithStatus($query, string|array $status)
    {
        $statuses = is_array($status) ? $status : [$status];

        return $query->whereIn('status', $statuses);
    }
}
