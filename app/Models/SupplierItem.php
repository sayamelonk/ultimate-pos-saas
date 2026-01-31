<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierItem extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'supplier_id',
        'inventory_item_id',
        'supplier_sku',
        'unit_id',
        'unit_conversion',
        'price',
        'lead_time_days',
        'min_order_qty',
        'is_preferred',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'unit_conversion' => 'decimal:4',
            'price' => 'decimal:2',
            'lead_time_days' => 'integer',
            'min_order_qty' => 'decimal:4',
            'is_preferred' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function getPriceInStockUnit(): float
    {
        return $this->price / $this->unit_conversion;
    }
}
