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

    public const STATUS_ACTIVE = 'active';

    public const STATUS_DEPLETED = 'depleted';

    public const STATUS_EXPIRED = 'expired';

    public const STATUS_DISPOSED = 'disposed';

    protected $fillable = [
        'tenant_id',
        'outlet_id',
        'inventory_item_id',
        'goods_receive_item_id',
        'batch_number',
        'production_date',
        'expiry_date',
        'initial_quantity',
        'current_quantity',
        'reserved_quantity',
        'unit_cost',
        'status',
        'supplier_batch_number',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'production_date' => 'date',
            'expiry_date' => 'date',
            'initial_quantity' => 'decimal:4',
            'current_quantity' => 'decimal:4',
            'reserved_quantity' => 'decimal:4',
            'unit_cost' => 'decimal:4',
        ];
    }

    // ==================== Relationships ====================

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
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
        return $this->hasMany(StockBatchMovement::class);
    }

    // ==================== Scopes ====================

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeWithStock($query)
    {
        return $query->where('current_quantity', '>', 0);
    }

    public function scopeExpiringSoon($query, int $days = 30)
    {
        return $query->where('status', self::STATUS_ACTIVE)
            ->whereNotNull('expiry_date')
            ->where('expiry_date', '<=', now()->addDays($days))
            ->where('expiry_date', '>', now());
    }

    public function scopeExpired($query)
    {
        return $query->whereNotNull('expiry_date')
            ->where('expiry_date', '<=', now());
    }

    public function scopeCritical($query, int $days = 7)
    {
        return $query->where('status', self::STATUS_ACTIVE)
            ->whereNotNull('expiry_date')
            ->where('expiry_date', '<=', now()->addDays($days))
            ->where('expiry_date', '>', now());
    }

    public function scopeForOutlet($query, $outletId)
    {
        return $query->where('outlet_id', $outletId);
    }

    public function scopeForItem($query, $itemId)
    {
        return $query->where('inventory_item_id', $itemId);
    }

    public function scopeFefo($query)
    {
        // First Expired First Out - order by expiry date ascending
        return $query->orderByRaw('CASE WHEN expiry_date IS NULL THEN 1 ELSE 0 END')
            ->orderBy('expiry_date', 'asc')
            ->orderBy('created_at', 'asc');
    }

    // ==================== Helpers ====================

    public function isExpired(): bool
    {
        return $this->expiry_date && $this->expiry_date->isPast();
    }

    public function isExpiringSoon(int $days = 30): bool
    {
        if (! $this->expiry_date) {
            return false;
        }

        return $this->expiry_date->isBetween(now(), now()->addDays($days));
    }

    public function isCritical(int $days = 7): bool
    {
        return $this->isExpiringSoon($days);
    }

    public function daysUntilExpiry(): ?int
    {
        if (! $this->expiry_date) {
            return null;
        }

        return (int) now()->diffInDays($this->expiry_date, false);
    }

    public function getExpiryStatus(): string
    {
        if (! $this->expiry_date) {
            return 'no_expiry';
        }

        $days = $this->daysUntilExpiry();

        if ($days < 0) {
            return 'expired';
        }

        if ($days <= 7) {
            return 'critical';
        }

        if ($days <= 30) {
            return 'warning';
        }

        return 'ok';
    }

    public function getExpiryBadgeType(): string
    {
        return match ($this->getExpiryStatus()) {
            'expired' => 'danger',
            'critical' => 'danger',
            'warning' => 'warning',
            'ok' => 'success',
            default => 'secondary',
        };
    }

    public function getAvailableQuantity(): float
    {
        return max(0, $this->current_quantity - $this->reserved_quantity);
    }

    public function isDepleted(): bool
    {
        return $this->current_quantity <= 0;
    }

    public function canDeduct(float $quantity): bool
    {
        return $this->getAvailableQuantity() >= $quantity;
    }

    public function deduct(float $quantity): bool
    {
        if (! $this->canDeduct($quantity)) {
            return false;
        }

        $this->current_quantity -= $quantity;

        if ($this->current_quantity <= 0) {
            $this->status = self::STATUS_DEPLETED;
        }

        return $this->save();
    }

    public function reserve(float $quantity): bool
    {
        if ($this->getAvailableQuantity() < $quantity) {
            return false;
        }

        $this->reserved_quantity += $quantity;

        return $this->save();
    }

    public function releaseReservation(float $quantity): bool
    {
        $this->reserved_quantity = max(0, $this->reserved_quantity - $quantity);

        return $this->save();
    }

    public function markAsExpired(): bool
    {
        $this->status = self::STATUS_EXPIRED;

        return $this->save();
    }

    public function markAsDisposed(): bool
    {
        $this->status = self::STATUS_DISPOSED;

        return $this->save();
    }

    // ==================== Static Helpers ====================

    public static function generateBatchNumber(string $outletId, ?string $prefix = 'BTH'): string
    {
        $today = now()->format('Ymd');
        $count = self::where('outlet_id', $outletId)
            ->whereDate('created_at', today())
            ->count() + 1;

        return sprintf('%s-%s-%03d', $prefix, $today, $count);
    }

    public static function getAvailableBatchesForItem(string $outletId, string $itemId, bool $fefo = true)
    {
        $query = self::where('outlet_id', $outletId)
            ->where('inventory_item_id', $itemId)
            ->where('status', self::STATUS_ACTIVE)
            ->where('current_quantity', '>', 0);

        if ($fefo) {
            $query->fefo();
        }

        return $query->get();
    }
}
