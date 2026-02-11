<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class StockBatchMovement extends Model
{
    use HasFactory, HasUuid;

    public const TYPE_RECEIVE = 'receive';

    public const TYPE_SALE = 'sale';

    public const TYPE_TRANSFER_OUT = 'transfer_out';

    public const TYPE_TRANSFER_IN = 'transfer_in';

    public const TYPE_ADJUSTMENT = 'adjustment';

    public const TYPE_WASTE = 'waste';

    public const TYPE_RETURN = 'return';

    public const TYPE_EXPIRED = 'expired';

    public const TYPE_PRODUCTION = 'production';

    protected $fillable = [
        'tenant_id',
        'outlet_id',
        'stock_batch_id',
        'inventory_item_id',
        'type',
        'quantity',
        'balance_before',
        'balance_after',
        'reference_type',
        'reference_id',
        'reference_number',
        'notes',
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:4',
            'balance_before' => 'decimal:4',
            'balance_after' => 'decimal:4',
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

    public function stockBatch(): BelongsTo
    {
        return $this->belongsTo(StockBatch::class);
    }

    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reference(): MorphTo
    {
        return $this->morphTo();
    }

    // ==================== Helpers ====================

    public function isInflow(): bool
    {
        return in_array($this->type, [
            self::TYPE_RECEIVE,
            self::TYPE_TRANSFER_IN,
            self::TYPE_RETURN,
        ]);
    }

    public function isOutflow(): bool
    {
        return ! $this->isInflow();
    }

    public static function getTypeLabel(string $type): string
    {
        return match ($type) {
            self::TYPE_RECEIVE => 'Goods Receive',
            self::TYPE_SALE => 'Sale',
            self::TYPE_TRANSFER_OUT => 'Transfer Out',
            self::TYPE_TRANSFER_IN => 'Transfer In',
            self::TYPE_ADJUSTMENT => 'Adjustment',
            self::TYPE_WASTE => 'Waste',
            self::TYPE_RETURN => 'Return',
            self::TYPE_EXPIRED => 'Expired',
            self::TYPE_PRODUCTION => 'Production',
            default => ucfirst($type),
        };
    }

    public static function getTypeColor(string $type): string
    {
        return match ($type) {
            self::TYPE_RECEIVE, self::TYPE_TRANSFER_IN, self::TYPE_RETURN => 'success',
            self::TYPE_SALE, self::TYPE_PRODUCTION => 'info',
            self::TYPE_TRANSFER_OUT => 'warning',
            self::TYPE_WASTE, self::TYPE_EXPIRED => 'danger',
            self::TYPE_ADJUSTMENT => 'secondary',
            default => 'secondary',
        };
    }
}
