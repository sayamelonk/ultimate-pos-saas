<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Recipe extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'tenant_id',
        'product_id',
        'name',
        'description',
        'instructions',
        'yield_qty',
        'yield_unit_id',
        'estimated_cost',
        'prep_time_minutes',
        'cook_time_minutes',
        'version',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'yield_qty' => 'decimal:4',
            'estimated_cost' => 'decimal:2',
            'prep_time_minutes' => 'integer',
            'cook_time_minutes' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function product(): ?BelongsTo
    {
        // Product model will be added in Phase 3
        if (! class_exists(Product::class)) {
            return null;
        }

        return $this->belongsTo(Product::class);
    }

    public function yieldUnit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'yield_unit_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(RecipeItem::class)->orderBy('sort_order');
    }

    public function getTotalTimeMinutes(): int
    {
        return ($this->prep_time_minutes ?? 0) + ($this->cook_time_minutes ?? 0);
    }

    public function calculateCost(): float
    {
        return $this->items->sum(function ($item) {
            return $item->calculateCost();
        });
    }

    public function getCostPerUnit(): float
    {
        if ($this->yield_qty <= 0) {
            return 0;
        }

        return $this->estimated_cost / $this->yield_qty;
    }

    // Accessors for backward compatibility with views
    public function getTotalCostAttribute(): float
    {
        return $this->estimated_cost;
    }

    public function getYieldQuantityAttribute(): float
    {
        return $this->yield_qty;
    }

    public function getIngredientsAttribute(): HasMany
    {
        return $this->items();
    }
}
