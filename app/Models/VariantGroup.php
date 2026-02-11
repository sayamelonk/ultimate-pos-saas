<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VariantGroup extends Model
{
    use HasFactory, HasUuid;

    public const DISPLAY_BUTTON = 'button';

    public const DISPLAY_DROPDOWN = 'dropdown';

    public const DISPLAY_COLOR = 'color';

    public const DISPLAY_IMAGE = 'image';

    protected $fillable = [
        'tenant_id',
        'name',
        'display_name',
        'description',
        'display_type',
        'is_required',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_required' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function options(): HasMany
    {
        return $this->hasMany(VariantOption::class)->orderBy('sort_order');
    }

    public function activeOptions(): HasMany
    {
        return $this->options()->where('is_active', true);
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_variant_groups')
            ->withPivot(['is_required', 'sort_order'])
            ->withTimestamps();
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function getDisplayNameOrNameAttribute(): string
    {
        return $this->display_name ?? $this->name;
    }
}
