<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Price extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'tenant_id',
        'inventory_item_id',
        'outlet_id',
        'selling_price',
        'member_price',
        'min_selling_price',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'selling_price' => 'decimal:2',
            'member_price' => 'decimal:2',
            'min_selling_price' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class);
    }

    public function outlet(): BelongsTo
    {
        return $this->belongsTo(Outlet::class);
    }

    public static function getPriceForOutlet(string $itemId, string $outletId, bool $isMember = false): ?float
    {
        $price = self::where('inventory_item_id', $itemId)
            ->where('outlet_id', $outletId)
            ->where('is_active', true)
            ->first();

        if (! $price) {
            return null;
        }

        if ($isMember && $price->member_price) {
            return (float) $price->member_price;
        }

        return (float) $price->selling_price;
    }
}
