<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Notifications\Notifiable;

class Tenant extends Model
{
    use HasFactory, HasUuid, Notifiable;

    protected $fillable = [
        'code',
        'name',
        'logo',
        'email',
        'phone',
        'currency',
        'timezone',
        'tax_percentage',
        'tax_enabled',
        'tax_mode',
        'service_charge_percentage',
        'service_charge_enabled',
        'subscription_plan',
        'subscription_expires_at',
        'trial_used',
        'trial_started_at',
        'trial_ended_at',
        'onboarding_completed_at',
        'max_outlets',
        'is_active',
        'subscription_reminder_h7_at',
        'subscription_reminder_h3_at',
        'subscription_reminder_h1_at',
        'data_deletion_warning_1_at',
        'data_deletion_warning_2_at',
        'is_deleted',
    ];

    protected function casts(): array
    {
        return [
            'tax_percentage' => 'decimal:2',
            'tax_enabled' => 'boolean',
            'service_charge_percentage' => 'decimal:2',
            'service_charge_enabled' => 'boolean',
            'subscription_expires_at' => 'datetime',
            'trial_used' => 'boolean',
            'trial_started_at' => 'datetime',
            'trial_ended_at' => 'datetime',
            'onboarding_completed_at' => 'datetime',
            'max_outlets' => 'integer',
            'is_active' => 'boolean',
            'subscription_reminder_h7_at' => 'datetime',
            'subscription_reminder_h3_at' => 'datetime',
            'subscription_reminder_h1_at' => 'datetime',
            'data_deletion_warning_1_at' => 'datetime',
            'data_deletion_warning_2_at' => 'datetime',
            'is_deleted' => 'boolean',
        ];
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function outlets(): HasMany
    {
        return $this->hasMany(Outlet::class);
    }

    public function roles(): HasMany
    {
        return $this->hasMany(Role::class);
    }

    public function units(): HasMany
    {
        return $this->hasMany(Unit::class);
    }

    public function suppliers(): HasMany
    {
        return $this->hasMany(Supplier::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function inventoryCategories(): HasMany
    {
        return $this->hasMany(InventoryCategory::class);
    }

    public function inventoryItems(): HasMany
    {
        return $this->hasMany(InventoryItem::class);
    }

    public function recipes(): HasMany
    {
        return $this->hasMany(Recipe::class);
    }

    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class);
    }

    public function isSubscriptionActive(): bool
    {
        if ($this->subscription_plan === 'free') {
            return true;
        }

        return $this->subscription_expires_at && $this->subscription_expires_at->isFuture();
    }

    public function canAddOutlet(): bool
    {
        $maxOutlets = $this->activeSubscription?->plan?->max_outlets ?? 1;

        // -1 means unlimited
        if ($maxOutlets === -1) {
            return true;
        }

        return $this->outlets()->count() < $maxOutlets;
    }

    public function getMaxOutlets(): int
    {
        return $this->activeSubscription?->plan?->max_outlets ?? 1;
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function activeSubscription(): HasOne
    {
        return $this->hasOne(Subscription::class)
            ->whereIn('status', ['active', 'trial'])
            ->latest();
    }

    public function subscriptionInvoices(): HasMany
    {
        return $this->hasMany(SubscriptionInvoice::class);
    }

    public function currentPlan(): ?SubscriptionPlan
    {
        return $this->activeSubscription?->plan;
    }

    public function canAddUser(): bool
    {
        $maxUsers = $this->activeSubscription?->plan?->max_users ?? 2;

        if ($maxUsers === -1) {
            return true;
        }

        return $this->users()->count() < $maxUsers;
    }

    public function hasFeature(string $feature): bool
    {
        $features = $this->activeSubscription?->plan?->features ?? [];

        return $features[$feature] ?? false;
    }

    public function canStartTrial(): bool
    {
        return ! $this->trial_used;
    }

    public function isTrial(): bool
    {
        return $this->activeSubscription?->isTrial() ?? false;
    }

    public function isFrozen(): bool
    {
        // Check legacy field
        if ($this->subscription_plan === 'frozen') {
            return true;
        }

        // Check latest subscription status (not activeSubscription since frozen isn't 'active')
        $latestSubscription = $this->subscriptions()->latest()->first();

        return $latestSubscription?->status === \App\Models\Subscription::STATUS_FROZEN;
    }

    public function canCreateTransactions(): bool
    {
        if ($this->isFrozen()) {
            return false;
        }

        return $this->isSubscriptionActive();
    }

    public function getMaxProducts(): int
    {
        $maxProducts = $this->activeSubscription?->plan?->max_products ?? 100;

        return $maxProducts === -1 ? PHP_INT_MAX : $maxProducts;
    }

    public function canAddProduct(): bool
    {
        // Count products for this tenant
        $currentCount = Product::where('tenant_id', $this->id)->count();

        return $currentCount < $this->getMaxProducts();
    }
}
