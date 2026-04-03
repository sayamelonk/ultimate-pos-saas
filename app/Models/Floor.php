<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Floor extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'tenant_id',
        'outlet_id',
        'name',
        'description',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_active' => 'boolean',
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

    public function tables(): HasMany
    {
        return $this->hasMany(Table::class)->orderBy('number');
    }

    public function activeTables(): HasMany
    {
        return $this->tables()->where('is_active', true);
    }

    public function getAvailableTablesCountAttribute(): int
    {
        return $this->tables()->where('status', 'available')->count();
    }

    public function getOccupiedTablesCountAttribute(): int
    {
        return $this->tables()->where('status', 'occupied')->count();
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForOutlet($query, string $outletId)
    {
        return $query->where('outlet_id', $outletId);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }
}
