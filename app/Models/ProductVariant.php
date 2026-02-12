<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductVariant extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'product_id',
        'sku',
        'barcode',
        'name',
        'option_ids',
        'price',
        'cost_price',
        'inventory_item_id',
        'recipe_id',
        'image',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'option_ids' => 'array',
            'price' => 'decimal:2',
            'cost_price' => 'decimal:2',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class);
    }

    public function recipe(): BelongsTo
    {
        return $this->belongsTo(Recipe::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function getOptionNames(): array
    {
        if (empty($this->option_ids)) {
            return [];
        }

        return VariantOption::whereIn('id', $this->option_ids)
            ->orderBy('sort_order')
            ->pluck('name')
            ->toArray();
    }

    public function getMarginAttribute(): float
    {
        if ($this->cost_price <= 0) {
            return 100;
        }

        return (($this->price - $this->cost_price) / $this->price) * 100;
    }
}
