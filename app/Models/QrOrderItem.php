<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QrOrderItem extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'qr_order_id',
        'product_id',
        'product_variant_id',
        'item_name',
        'item_sku',
        'quantity',
        'unit_price',
        'modifiers_total',
        'subtotal',
        'modifiers',
        'item_notes',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'unit_price' => 'decimal:2',
            'modifiers_total' => 'decimal:2',
            'subtotal' => 'decimal:2',
            'modifiers' => 'array',
        ];
    }

    public function qrOrder(): BelongsTo
    {
        return $this->belongsTo(QrOrder::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class);
    }
}
