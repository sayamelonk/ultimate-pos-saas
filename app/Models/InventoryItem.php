<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class InventoryItem extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'tenant_id',
        'category_id',
        'unit_id',
        'purchase_unit_id',
        'sku',
        'barcode',
        'name',
        'type',
        'description',
        'image',
        'purchase_unit_conversion',
        'cost_price',
        'min_stock',
        'max_stock',
        'reorder_point',
        'reorder_qty',
        'shelf_life_days',
        'storage_location',
        'is_perishable',
        'track_batches',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'purchase_unit_conversion' => 'decimal:4',
            'cost_price' => 'decimal:2',
            'min_stock' => 'decimal:4',
            'max_stock' => 'decimal:4',
            'reorder_point' => 'decimal:4',
            'reorder_qty' => 'decimal:4',
            'shelf_life_days' => 'integer',
            'is_perishable' => 'boolean',
            'track_batches' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(InventoryCategory::class, 'category_id');
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function purchaseUnit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'purchase_unit_id');
    }

    public function supplierItems(): HasMany
    {
        return $this->hasMany(SupplierItem::class);
    }

    public function preferredSupplier(): HasOne
    {
        return $this->hasOne(SupplierItem::class)->where('is_preferred', true);
    }

    public function stocks(): HasMany
    {
        return $this->hasMany(InventoryStock::class);
    }

    public function batches(): HasMany
    {
        return $this->hasMany(StockBatch::class);
    }

    public function stockBatches(): HasMany
    {
        return $this->hasMany(StockBatch::class);
    }

    public function movements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function recipeItems(): HasMany
    {
        return $this->hasMany(RecipeItem::class);
    }

    public function getStockForOutlet(string $outletId): ?InventoryStock
    {
        return $this->stocks()->where('outlet_id', $outletId)->first();
    }

    public function getTotalStock(): float
    {
        return $this->stocks()->sum('quantity');
    }

    public function isLowStock(string $outletId): bool
    {
        $stock = $this->getStockForOutlet($outletId);

        return $stock && $stock->quantity <= $this->reorder_point;
    }
}
