<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KitchenOrder extends Model
{
    use HasFactory, HasUuid;

    public const STATUS_PENDING = 'pending';

    public const STATUS_PREPARING = 'preparing';

    public const STATUS_READY = 'ready';

    public const STATUS_SERVED = 'served';

    public const STATUS_CANCELLED = 'cancelled';

    public const PRIORITY_NORMAL = 'normal';

    public const PRIORITY_RUSH = 'rush';

    public const PRIORITY_VIP = 'vip';

    protected $fillable = [
        'tenant_id',
        'outlet_id',
        'transaction_id',
        'station_id',
        'table_id',
        'order_number',
        'order_type',
        'table_name',
        'customer_name',
        'status',
        'priority',
        'notes',
        'cancel_reason',
        'started_at',
        'completed_at',
        'served_at',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'served_at' => 'datetime',
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

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function station(): BelongsTo
    {
        return $this->belongsTo(KitchenStation::class, 'station_id');
    }

    public function table(): BelongsTo
    {
        return $this->belongsTo(Table::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(KitchenOrderItem::class);
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

    public function isServed(): bool
    {
        return $this->status === self::STATUS_SERVED;
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

    public function canMarkServed(): bool
    {
        return $this->status === self::STATUS_READY;
    }

    public function canCancel(): bool
    {
        return in_array($this->status, [self::STATUS_PENDING, self::STATUS_PREPARING]);
    }

    public function canRecall(): bool
    {
        return $this->status === self::STATUS_SERVED;
    }

    // Actions
    public function start(): void
    {
        $this->update([
            'status' => self::STATUS_PREPARING,
            'started_at' => now(),
        ]);

        // Start all pending items
        $this->items()->where('status', 'pending')->update([
            'status' => 'preparing',
            'started_at' => now(),
        ]);
    }

    public function markReady(): void
    {
        $this->update([
            'status' => self::STATUS_READY,
            'completed_at' => now(),
        ]);

        // Mark all items as ready
        $this->items()->whereIn('status', ['pending', 'preparing'])->update([
            'status' => 'ready',
            'completed_at' => now(),
        ]);
    }

    public function markServed(): void
    {
        $this->update([
            'status' => self::STATUS_SERVED,
            'served_at' => now(),
        ]);
    }

    public function cancel(?string $reason = null): void
    {
        $this->update([
            'status' => self::STATUS_CANCELLED,
            'cancel_reason' => $reason,
        ]);

        // Cancel all items
        $this->items()->update(['status' => 'cancelled']);
    }

    public function recall(): void
    {
        $this->update([
            'status' => self::STATUS_READY,
            'served_at' => null,
        ]);
    }

    public function bump(): void
    {
        switch ($this->status) {
            case self::STATUS_PENDING:
                $this->start();
                break;
            case self::STATUS_PREPARING:
                $this->markReady();
                break;
            case self::STATUS_READY:
                $this->markServed();
                break;
        }
    }

    public function setPriority(string $priority): void
    {
        $this->update(['priority' => $priority]);
    }

    public function checkAndUpdateStatus(): void
    {
        // If all items are ready, mark order as ready
        $pendingItems = $this->items()->whereIn('status', ['pending', 'preparing'])->count();

        if ($pendingItems === 0 && $this->status === self::STATUS_PREPARING) {
            $this->update([
                'status' => self::STATUS_READY,
                'completed_at' => now(),
            ]);
        }
    }

    // Computed attributes
    public function getElapsedTimeAttribute(): int
    {
        if ($this->status === self::STATUS_SERVED) {
            return $this->created_at->diffInMinutes($this->served_at);
        }

        if ($this->status === self::STATUS_READY) {
            return $this->created_at->diffInMinutes($this->completed_at);
        }

        return $this->created_at->diffInMinutes(now());
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
    public function scopeForOutlet($query, string $outletId)
    {
        return $query->where('outlet_id', $outletId);
    }

    public function scopeWithStatus($query, string|array $status)
    {
        $statuses = is_array($status) ? $status : [$status];

        return $query->whereIn('status', $statuses);
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', [
            self::STATUS_PENDING,
            self::STATUS_PREPARING,
            self::STATUS_READY,
        ]);
    }

    public function scopeForStation($query, ?string $stationId)
    {
        if ($stationId) {
            return $query->where('station_id', $stationId);
        }

        return $query;
    }

    public function scopeToday($query)
    {
        return $query->whereDate('created_at', today());
    }

    public static function getStatuses(): array
    {
        return [
            self::STATUS_PENDING => 'Pending',
            self::STATUS_PREPARING => 'Preparing',
            self::STATUS_READY => 'Ready',
            self::STATUS_SERVED => 'Served',
            self::STATUS_CANCELLED => 'Cancelled',
        ];
    }

    public static function getPriorities(): array
    {
        return [
            self::PRIORITY_NORMAL => 'Normal',
            self::PRIORITY_RUSH => 'Rush',
            self::PRIORITY_VIP => 'VIP',
        ];
    }
}
