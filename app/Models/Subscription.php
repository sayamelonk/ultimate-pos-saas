<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subscription extends Model
{
    protected $fillable = [
        'tenant_id',
        'subscription_plan_id',
        'billing_cycle',
        'status',
        'starts_at',
        'ends_at',
        'cancelled_at',
        'cancellation_reason',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'cancelled_at' => 'datetime',
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

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function isActive(): bool
    {
        return $this->status === 'active' && $this->ends_at?->isFuture();
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isExpired(): bool
    {
        return $this->status === 'expired' || ($this->ends_at && $this->ends_at->isPast());
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    public function daysRemaining(): int
    {
        if (! $this->ends_at) {
            return 0;
        }

        return max(0, now()->diffInDays($this->ends_at, false));
    }

    public function activate(): void
    {
        $this->update([
            'status' => 'active',
            'starts_at' => $this->starts_at ?? now(),
        ]);

        $this->syncToTenant();
    }

    public function expire(): void
    {
        $this->update(['status' => 'expired']);
        $this->tenant->update([
            'subscription_plan' => 'expired',
            'subscription_expires_at' => $this->ends_at,
        ]);
    }

    public function cancel(?string $reason = null): void
    {
        $this->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
            'cancellation_reason' => $reason,
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
}
