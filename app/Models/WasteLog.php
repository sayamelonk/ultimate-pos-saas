<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WasteLog extends Model
{
    use HasFactory, HasUuid;

    public const REASON_EXPIRED = 'expired';

    public const REASON_SPOILED = 'spoiled';

    public const REASON_DAMAGED = 'damaged';

    public const REASON_PREPARATION = 'preparation';

    public const REASON_OVERPRODUCTION = 'overproduction';

    public const REASON_OTHER = 'other';

    protected $fillable = [
        'tenant_id',
        'outlet_id',
        'inventory_item_id',
        'batch_id',
        'waste_date',
        'quantity',
        'unit_id',
        'cost_price',
        'total_cost',
        'reason',
        'notes',
        'logged_by',
    ];

    protected function casts(): array
    {
        return [
            'waste_date' => 'date',
            'quantity' => 'decimal:4',
            'cost_price' => 'decimal:2',
            'total_cost' => 'decimal:2',
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

    public function loggedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'logged_by');
    }

    public function calculateTotalCost(): void
    {
        $this->total_cost = $this->quantity * $this->cost_price;
    }

    public static function getReasons(): array
    {
        return [
            self::REASON_EXPIRED => 'Expired',
            self::REASON_SPOILED => 'Spoiled',
            self::REASON_DAMAGED => 'Damaged',
            self::REASON_PREPARATION => 'Preparation Waste',
            self::REASON_OVERPRODUCTION => 'Overproduction',
            self::REASON_OTHER => 'Other',
        ];
    }
}
