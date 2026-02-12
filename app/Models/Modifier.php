<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Modifier extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'modifier_group_id',
        'inventory_item_id',
        'name',
        'display_name',
        'price',
        'cost_price',
        'quantity_used',
        'is_default',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'cost_price' => 'decimal:2',
            'quantity_used' => 'decimal:4',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function modifierGroup(): BelongsTo
    {
        return $this->belongsTo(ModifierGroup::class);
    }

    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function getDisplayNameOrNameAttribute(): string
    {
        return $this->display_name ?? $this->name;
    }

    public function getMarginAttribute(): float
    {
        if ($this->cost_price <= 0) {
            return 100;
        }

        return (($this->price - $this->cost_price) / $this->price) * 100;
    }
}
