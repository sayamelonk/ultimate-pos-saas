<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Unit extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'tenant_id',
        'name',
        'abbreviation',
        'base_unit_id',
        'conversion_factor',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'conversion_factor' => 'decimal:6',
            'is_active' => 'boolean',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function baseUnit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'base_unit_id');
    }

    public function derivedUnits(): HasMany
    {
        return $this->hasMany(Unit::class, 'base_unit_id');
    }

    public function inventoryItems(): HasMany
    {
        return $this->hasMany(InventoryItem::class);
    }

    public function supplierItems(): HasMany
    {
        return $this->hasMany(SupplierItem::class);
    }

    public function isBaseUnit(): bool
    {
        return $this->base_unit_id === null;
    }

    public function convertToBase(float $quantity): float
    {
        return $quantity * $this->conversion_factor;
    }

    public function convertFromBase(float $quantity): float
    {
        return $quantity / $this->conversion_factor;
    }
}
