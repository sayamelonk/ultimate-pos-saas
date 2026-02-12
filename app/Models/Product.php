<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Product extends Model
{
    use HasFactory, HasUuid, SoftDeletes;

    public const TYPE_SINGLE = 'single';

    public const TYPE_VARIANT = 'variant';

    public const TYPE_COMBO = 'combo';

    protected $fillable = [
        'tenant_id',
        'category_id',
        'recipe_id',
        'sku',
        'barcode',
        'name',
        'slug',
        'description',
        'image',
        'base_price',
        'cost_price',
        'product_type',
        'track_stock',
        'inventory_item_id',
        'is_active',
        'is_featured',
        'show_in_pos',
        'show_in_menu',
        'allow_notes',
        'prep_time_minutes',
        'sort_order',
        'tags',
        'allergens',
        'nutritional_info',
    ];

    protected function casts(): array
    {
        return [
            'base_price' => 'decimal:2',
            'cost_price' => 'decimal:2',
            'track_stock' => 'boolean',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'show_in_pos' => 'boolean',
            'show_in_menu' => 'boolean',
            'allow_notes' => 'boolean',
            'prep_time_minutes' => 'integer',
            'sort_order' => 'integer',
            'tags' => 'array',
            'allergens' => 'array',
            'nutritional_info' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function ($product) {
            if (empty($product->slug)) {
                $product->slug = Str::slug($product->name);
            }
        });
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'category_id');
    }

    public function recipe(): BelongsTo
    {
        return $this->belongsTo(Recipe::class);
    }

    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class);
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class)->orderBy('sort_order');
    }

    public function activeVariants(): HasMany
    {
        return $this->variants()->where('is_active', true);
    }

    public function variantGroups(): BelongsToMany
    {
        return $this->belongsToMany(VariantGroup::class, 'product_variant_groups')
            ->withPivot(['is_required', 'sort_order'])
            ->orderByPivot('sort_order');
    }

    public function modifierGroups(): BelongsToMany
    {
        return $this->belongsToMany(ModifierGroup::class, 'product_modifier_groups')
            ->withPivot(['is_required', 'min_selections', 'max_selections', 'sort_order'])
            ->orderByPivot('sort_order');
    }

    public function combo(): HasOne
    {
        return $this->hasOne(Combo::class);
    }

    public function outlets(): BelongsToMany
    {
        return $this->belongsToMany(Outlet::class, 'product_outlets')
            ->withPivot(['is_available', 'custom_price', 'is_featured', 'sort_order'])
            ->withTimestamps();
    }

    public function productOutlets(): HasMany
    {
        return $this->hasMany(ProductOutlet::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForPos($query)
    {
        return $query->where('show_in_pos', true);
    }

    public function scopeForMenu($query)
    {
        return $query->where('show_in_menu', true);
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('product_type', $type);
    }

    public function isSingle(): bool
    {
        return $this->product_type === self::TYPE_SINGLE;
    }

    public function isVariant(): bool
    {
        return $this->product_type === self::TYPE_VARIANT;
    }

    public function isCombo(): bool
    {
        return $this->product_type === self::TYPE_COMBO;
    }

    public function getPriceForOutlet(?string $outletId = null): float
    {
        if ($outletId) {
            $productOutlet = $this->productOutlets()->where('outlet_id', $outletId)->first();
            if ($productOutlet && $productOutlet->custom_price !== null) {
                return (float) $productOutlet->custom_price;
            }
        }

        return (float) $this->base_price;
    }

    public function isAvailableAtOutlet(string $outletId): bool
    {
        $productOutlet = $this->productOutlets()->where('outlet_id', $outletId)->first();

        return $productOutlet ? $productOutlet->is_available : true;
    }

    public function getMarginAttribute(): float
    {
        if ($this->cost_price <= 0) {
            return 100;
        }

        return (($this->base_price - $this->cost_price) / $this->base_price) * 100;
    }
}
