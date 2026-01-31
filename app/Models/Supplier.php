<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Supplier extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'tenant_id',
        'code',
        'name',
        'contact_person',
        'email',
        'phone',
        'address',
        'city',
        'tax_number',
        'payment_terms',
        'notes',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'payment_terms' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function supplierItems(): HasMany
    {
        return $this->hasMany(SupplierItem::class);
    }

    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class);
    }

    public function goodsReceives(): HasMany
    {
        return $this->hasMany(GoodsReceive::class);
    }

    public function inventoryItems(): HasMany
    {
        return $this->hasMany(InventoryItem::class)
            ->join('supplier_items', 'inventory_items.id', '=', 'supplier_items.inventory_item_id')
            ->where('supplier_items.supplier_id', $this->id);
    }
}
