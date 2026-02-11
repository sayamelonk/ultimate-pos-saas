<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecipeItem extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'recipe_id',
        'inventory_item_id',
        'quantity',
        'unit_id',
        'waste_percentage',
        'notes',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:4',
            'waste_percentage' => 'decimal:2',
            'sort_order' => 'integer',
        ];
    }

    public function recipe(): BelongsTo
    {
        return $this->belongsTo(Recipe::class);
    }

    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function getGrossQuantity(): float
    {
        $wasteFactor = 1 + ($this->waste_percentage / 100);

        return $this->quantity * $wasteFactor;
    }

    public function calculateCost(): float
    {
        $costPrice = $this->inventoryItem->cost_price ?? 0;

        return $this->getGrossQuantity() * $costPrice;
    }
}
