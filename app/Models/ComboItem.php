<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ComboItem extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'combo_id',
        'product_id',
        'category_id',
        'group_name',
        'quantity',
        'is_required',
        'allow_variant_selection',
        'price_adjustment',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'is_required' => 'boolean',
            'allow_variant_selection' => 'boolean',
            'price_adjustment' => 'decimal:2',
            'sort_order' => 'integer',
        ];
    }

    public function combo(): BelongsTo
    {
        return $this->belongsTo(Combo::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'category_id');
    }

    public function getAvailableProducts()
    {
        if ($this->product_id) {
            return collect([$this->product]);
        }

        if ($this->category_id) {
            return Product::where('category_id', $this->category_id)
                ->where('is_active', true)
                ->where('product_type', '!=', Product::TYPE_COMBO)
                ->get();
        }

        return collect();
    }
}
