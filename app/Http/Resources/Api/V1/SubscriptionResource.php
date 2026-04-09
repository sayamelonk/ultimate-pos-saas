<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubscriptionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $statusLabels = [
            'trial' => 'Trial',
            'active' => 'Active',
            'frozen' => 'Frozen',
            'expired' => 'Expired',
            'cancelled' => 'Cancelled',
            'pending' => 'Pending',
            'past_due' => 'Past Due',
        ];

        return [
            'id' => $this->id,
            'status' => $this->status,
            'status_label' => $statusLabels[$this->status] ?? ucfirst($this->status),
            'plan' => $this->whenLoaded('plan', fn () => [
                'id' => $this->plan->id,
                'name' => $this->plan->name,
                'slug' => $this->plan->slug,
            ]),
            'billing_cycle' => $this->billing_cycle,
            'price' => (int) $this->price,
            'price_formatted' => 'Rp '.number_format($this->price, 0, ',', '.'),
            'trial_ends_at' => $this->trial_ends_at?->toIso8601String(),
            'current_period_start' => $this->current_period_start?->toIso8601String(),
            'current_period_end' => $this->current_period_end?->toIso8601String(),
            'days_remaining' => $this->daysRemaining(),
            'is_trial' => $this->isTrial(),
            'is_active' => $this->isActive() || $this->isTrial(),
            'can_use_system' => $this->canUseSystem(),
            'can_create_transactions' => $this->canCreateTransactions(),
            'cancelled_at' => $this->cancelled_at?->toIso8601String(),
            'cancellation_reason' => $this->cancellation_reason,
            'frozen_at' => $this->frozen_at?->toIso8601String(),
        ];
    }
}
