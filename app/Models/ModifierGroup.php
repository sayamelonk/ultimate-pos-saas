<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ModifierGroup extends Model
{
    use HasFactory, HasUuid;

    public const SELECTION_SINGLE = 'single';

    public const SELECTION_MULTIPLE = 'multiple';

    protected $fillable = [
        'tenant_id',
        'name',
        'display_name',
        'description',
        'selection_type',
        'min_selections',
        'max_selections',
        'is_required',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'min_selections' => 'integer',
            'max_selections' => 'integer',
            'is_required' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function modifiers(): HasMany
    {
        return $this->hasMany(Modifier::class)->orderBy('sort_order');
    }

    public function activeModifiers(): HasMany
    {
        return $this->modifiers()->where('is_active', true);
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_modifier_groups')
            ->withPivot(['is_required', 'min_selections', 'max_selections', 'sort_order'])
            ->withTimestamps();
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function isSingleSelect(): bool
    {
        return $this->selection_type === self::SELECTION_SINGLE;
    }

    public function isMultipleSelect(): bool
    {
        return $this->selection_type === self::SELECTION_MULTIPLE;
    }

    public function getDisplayNameOrNameAttribute(): string
    {
        return $this->display_name ?? $this->name;
    }
}
