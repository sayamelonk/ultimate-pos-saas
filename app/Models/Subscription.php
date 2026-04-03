<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subscription extends Model
{
    use HasFactory;

    public const STATUS_TRIAL = 'trial';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_FROZEN = 'frozen';

    public const STATUS_EXPIRED = 'expired';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_PENDING = 'pending';

    public const STATUS_PAST_DUE = 'past_due';

    public const TRIAL_DAYS = 14;

    public const GRACE_PERIOD_DAYS = 1;

    protected $fillable = [
        'tenant_id',
        'subscription_plan_id',
        'billing_cycle',
        'price',
        'is_trial',
        'trial_ends_at',
        'status',
        'starts_at',
        'ends_at',
        'current_period_start',
        'current_period_end',
        'grace_period_ends_at',
        'cancelled_at',
        'frozen_at',
        'cancellation_reason',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'is_trial' => 'boolean',
            'trial_ends_at' => 'datetime',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'current_period_start' => 'datetime',
            'current_period_end' => 'datetime',
            'grace_period_ends_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'frozen_at' => 'datetime',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class, 'subscription_plan_id');
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(SubscriptionInvoice::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeTrial($query)
    {
        return $query->where('status', self::STATUS_TRIAL);
    }

    public function scopeFrozen($query)
    {
        return $query->where('status', self::STATUS_FROZEN);
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeActiveOrTrial($query)
    {
        return $query->whereIn('status', [self::STATUS_ACTIVE, self::STATUS_TRIAL]);
    }

    // Status checks
    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE && $this->ends_at?->isFuture();
    }

    public function isTrial(): bool
    {
        return $this->status === self::STATUS_TRIAL && $this->trial_ends_at?->isFuture();
    }

    public function isFrozen(): bool
    {
        return $this->status === self::STATUS_FROZEN;
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isExpired(): bool
    {
        return $this->status === self::STATUS_EXPIRED ||
            ($this->ends_at && $this->ends_at->isPast() && ! $this->isFrozen());
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    public function isInGracePeriod(): bool
    {
        return $this->grace_period_ends_at?->isFuture() ?? false;
    }

    /**
     * Check if tenant can still use the system (trial, active, frozen, or in grace period)
     * Frozen users can access data (read-only) but cannot create transactions
     */
    public function canUseSystem(): bool
    {
        return $this->isTrial() || $this->isActive() || $this->isFrozen() || $this->isInGracePeriod();
    }

    /**
     * Check if tenant can create new transactions (not frozen)
     */
    public function canCreateTransactions(): bool
    {
        return ! $this->isFrozen() && $this->canUseSystem();
    }

    // Helper methods
    public function daysRemaining(): int
    {
        if ($this->isTrial()) {
            return max(0, (int) now()->diffInDays($this->trial_ends_at, false));
        }

        if (! $this->ends_at) {
            return 0;
        }

        return max(0, (int) now()->diffInDays($this->ends_at, false));
    }

    public function getEffectiveEndDate(): ?\Carbon\Carbon
    {
        if ($this->isTrial()) {
            return $this->trial_ends_at;
        }

        return $this->ends_at;
    }

    // State transitions
    public function startTrial(): void
    {
        $trialEndsAt = now()->addDays(self::TRIAL_DAYS);

        $this->update([
            'status' => self::STATUS_TRIAL,
            'is_trial' => true,
            'trial_ends_at' => $trialEndsAt,
            'starts_at' => now(),
        ]);

        $this->tenant->update([
            'trial_used' => true,
            'trial_started_at' => now(),
            'subscription_plan' => 'professional', // Trial gets Professional features
            'subscription_expires_at' => $trialEndsAt,
        ]);
    }

    public function activate(): void
    {
        $this->update([
            'status' => self::STATUS_ACTIVE,
            'is_trial' => false,
            'starts_at' => $this->starts_at ?? now(),
        ]);

        $this->syncToTenant();
    }

    public function freeze(): void
    {
        $this->update([
            'status' => self::STATUS_FROZEN,
            'frozen_at' => now(),
        ]);

        $this->tenant->update([
            'subscription_plan' => 'frozen',
        ]);
    }

    public function expire(): void
    {
        $this->update(['status' => self::STATUS_EXPIRED]);

        $this->tenant->update([
            'subscription_plan' => 'expired',
            'subscription_expires_at' => $this->ends_at,
            'trial_ended_at' => $this->isTrial() ? now() : null,
        ]);
    }

    public function cancel(?string $reason = null): void
    {
        $this->update([
            'status' => self::STATUS_CANCELLED,
            'cancelled_at' => now(),
            'cancellation_reason' => $reason,
        ]);
    }

    /**
     * Transition from trial/active to grace period before freeze
     */
    public function startGracePeriod(): void
    {
        $this->update([
            'grace_period_ends_at' => now()->addDays(self::GRACE_PERIOD_DAYS),
        ]);
    }

    public function syncToTenant(): void
    {
        $this->tenant->update([
            'subscription_plan' => $this->plan->slug,
            'subscription_expires_at' => $this->ends_at,
            'max_outlets' => $this->plan->max_outlets === -1 ? 999 : $this->plan->max_outlets,
        ]);
    }

    /**
     * Create a trial subscription for a tenant
     */
    public static function createTrial(Tenant $tenant): self
    {
        // Get Professional plan for trial (full features)
        $professionalPlan = SubscriptionPlan::where('slug', 'professional')->first();

        $subscription = self::create([
            'tenant_id' => $tenant->id,
            'subscription_plan_id' => $professionalPlan->id,
            'billing_cycle' => 'monthly',
            'is_trial' => true,
            'status' => self::STATUS_PENDING,
        ]);

        $subscription->startTrial();

        return $subscription;
    }
}
