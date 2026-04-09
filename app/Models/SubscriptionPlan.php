<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SubscriptionPlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'price_monthly',
        'price_yearly',
        'max_outlets',
        'max_users',
        'max_products',
        'features',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'price_monthly' => 'decimal:2',
            'price_yearly' => 'decimal:2',
            'max_outlets' => 'integer',
            'max_users' => 'integer',
            'max_products' => 'integer',
            'features' => 'array',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(SubscriptionInvoice::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }

    public function getPrice(string $billingCycle): float
    {
        return $billingCycle === 'yearly' ? $this->price_yearly : $this->price_monthly;
    }

    public function isUnlimited(): bool
    {
        return $this->max_outlets === -1 || $this->max_users === -1;
    }

    public function getFormattedPriceMonthly(): string
    {
        return 'Rp '.number_format($this->price_monthly, 0, ',', '.');
    }

    public function getFormattedPriceYearly(): string
    {
        return 'Rp '.number_format($this->price_yearly, 0, ',', '.');
    }
}
