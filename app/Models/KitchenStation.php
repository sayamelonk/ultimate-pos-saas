<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class KitchenStation extends Model
{
    use HasFactory, HasUuid, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'outlet_id',
        'name',
        'code',
        'color',
        'description',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
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

    public function kitchenOrders(): HasMany
    {
        return $this->hasMany(KitchenOrder::class, 'station_id');
    }

    public function kitchenOrderItems(): HasMany
    {
        return $this->hasMany(KitchenOrderItem::class, 'station_id');
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'kitchen_station_id');
    }

    public function getPendingOrdersCountAttribute(): int
    {
        return $this->kitchenOrders()
            ->whereIn('status', ['pending', 'preparing'])
            ->count();
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForOutlet($query, string $outletId)
    {
        return $query->where('outlet_id', $outletId);
    }
}
