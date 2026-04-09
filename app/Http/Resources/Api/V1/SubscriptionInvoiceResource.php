<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubscriptionInvoiceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $statusLabels = [
            'pending' => 'Pending',
            'paid' => 'Paid',
            'expired' => 'Expired',
            'cancelled' => 'Cancelled',
            'failed' => 'Failed',
        ];

        return [
            'id' => $this->id,
            'invoice_number' => $this->invoice_number,
            'amount' => (int) $this->amount,
            'tax_amount' => (int) $this->tax_amount,
            'total_amount' => (int) $this->total_amount,
            'total_amount_formatted' => $this->getFormattedAmount(),
            'status' => $this->status,
            'status_label' => $statusLabels[$this->status] ?? ucfirst($this->status),
            'billing_cycle' => $this->billing_cycle,
            'period_start' => $this->period_start?->toIso8601String(),
            'period_end' => $this->period_end?->toIso8601String(),
            'plan_name' => $this->whenLoaded('plan', fn () => $this->plan->name),
            'plan' => $this->whenLoaded('plan', fn () => [
                'id' => $this->plan->id,
                'name' => $this->plan->name,
                'slug' => $this->plan->slug,
            ]),
            'paid_at' => $this->paid_at?->toIso8601String(),
            'payment_url' => $this->xendit_invoice_url,
            'payment_method' => $this->payment_method,
            'payment_channel' => $this->payment_channel,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
