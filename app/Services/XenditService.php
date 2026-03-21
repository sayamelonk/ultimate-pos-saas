<?php

namespace App\Services;

use App\Models\Subscription;
use App\Models\SubscriptionInvoice;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use Exception;
use Illuminate\Support\Facades\Log;
use Xendit\Configuration;
use Xendit\Invoice\CreateInvoiceRequest;
use Xendit\Invoice\InvoiceApi;

class XenditService
{
    protected InvoiceApi $invoiceApi;

    public function __construct()
    {
        Configuration::setXenditKey(config('xendit.secret_key'));
        $this->invoiceApi = new InvoiceApi;
    }

    /**
     * Create a new invoice for subscription payment.
     */
    public function createSubscriptionInvoice(
        Tenant $tenant,
        SubscriptionPlan $plan,
        string $billingCycle = 'monthly',
        ?Subscription $subscription = null
    ): SubscriptionInvoice {
        $amount = $plan->getPrice($billingCycle);
        $invoiceNumber = SubscriptionInvoice::generateInvoiceNumber();

        $createInvoiceRequest = new CreateInvoiceRequest([
            'external_id' => $invoiceNumber,
            'amount' => $amount,
            'payer_email' => $tenant->email,
            'description' => "Subscription {$plan->name} - ".ucfirst($billingCycle),
            'invoice_duration' => config('xendit.invoice.invoice_duration', 86400),
            'currency' => config('xendit.invoice.currency', 'IDR'),
            'reminder_time' => config('xendit.invoice.reminder_time', 1),
            'success_redirect_url' => url(config('xendit.invoice.success_redirect_url')),
            'failure_redirect_url' => url(config('xendit.invoice.failure_redirect_url')),
            'customer' => [
                'given_names' => $tenant->name,
                'email' => $tenant->email,
                'mobile_number' => $tenant->phone,
            ],
            'items' => [
                [
                    'name' => "Subscription {$plan->name}",
                    'quantity' => 1,
                    'price' => $amount,
                ],
            ],
            'metadata' => [
                'tenant_id' => $tenant->id,
                'plan_id' => $plan->id,
                'billing_cycle' => $billingCycle,
                'subscription_id' => $subscription?->id,
            ],
        ]);

        try {
            $invoice = $this->invoiceApi->createInvoice($createInvoiceRequest);

            $subscriptionInvoice = SubscriptionInvoice::create([
                'tenant_id' => $tenant->id,
                'subscription_id' => $subscription?->id,
                'subscription_plan_id' => $plan->id,
                'invoice_number' => $invoiceNumber,
                'xendit_invoice_id' => $invoice->getId(),
                'xendit_invoice_url' => $invoice->getInvoiceUrl(),
                'amount' => $amount,
                'tax_amount' => 0,
                'total_amount' => $amount,
                'currency' => config('xendit.invoice.currency', 'IDR'),
                'billing_cycle' => $billingCycle,
                'status' => 'pending',
                'expired_at' => now()->addSeconds(config('xendit.invoice.invoice_duration', 86400)),
                'xendit_response' => json_decode(json_encode($invoice), true),
            ]);

            return $subscriptionInvoice;
        } catch (Exception $e) {
            Log::error('Xendit create invoice error', [
                'tenant_id' => $tenant->id,
                'plan_id' => $plan->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Get invoice details from Xendit.
     */
    public function getInvoice(string $invoiceId): array
    {
        try {
            $invoice = $this->invoiceApi->getInvoiceById($invoiceId);

            return json_decode(json_encode($invoice), true);
        } catch (Exception $e) {
            Log::error('Xendit get invoice error', [
                'invoice_id' => $invoiceId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Expire an invoice.
     */
    public function expireInvoice(string $invoiceId): bool
    {
        try {
            $this->invoiceApi->expireInvoice($invoiceId);

            return true;
        } catch (Exception $e) {
            Log::error('Xendit expire invoice error', [
                'invoice_id' => $invoiceId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Handle payment callback from Xendit.
     */
    public function handlePaymentCallback(array $payload): ?SubscriptionInvoice
    {
        $invoiceId = $payload['id'] ?? null;
        $status = $payload['status'] ?? null;

        if (! $invoiceId) {
            Log::warning('Xendit callback: missing invoice id', $payload);

            return null;
        }

        $invoice = SubscriptionInvoice::where('xendit_invoice_id', $invoiceId)->first();

        if (! $invoice) {
            Log::warning('Xendit callback: invoice not found', ['xendit_invoice_id' => $invoiceId]);

            return null;
        }

        switch ($status) {
            case 'PAID':
            case 'SETTLED':
                $this->processSuccessfulPayment($invoice, $payload);
                break;

            case 'EXPIRED':
                $invoice->markAsExpired();
                break;

            case 'FAILED':
                $invoice->markAsFailed();
                break;
        }

        return $invoice;
    }

    /**
     * Process successful payment.
     */
    protected function processSuccessfulPayment(SubscriptionInvoice $invoice, array $payload): void
    {
        $invoice->markAsPaid([
            'payment_method' => $payload['payment_method'] ?? null,
            'payment_channel' => $payload['payment_channel'] ?? null,
            'paid_amount' => $payload['paid_amount'] ?? $invoice->total_amount,
            'bank_code' => $payload['bank_code'] ?? null,
            'paid_at' => $payload['paid_at'] ?? now()->toISOString(),
        ]);

        $tenant = $invoice->tenant;
        $plan = $invoice->plan;
        $billingCycle = $invoice->billing_cycle;

        $periodDays = $billingCycle === 'yearly' ? 365 : 30;

        if ($invoice->subscription) {
            $subscription = $invoice->subscription;
            $newEndDate = $subscription->ends_at && $subscription->ends_at->isFuture()
                ? $subscription->ends_at->addDays($periodDays)
                : now()->addDays($periodDays);

            $subscription->update([
                'status' => 'active',
                'ends_at' => $newEndDate,
            ]);
            $subscription->syncToTenant();
        } else {
            $existingSubscription = $tenant->subscriptions()
                ->where('status', 'active')
                ->first();

            if ($existingSubscription) {
                $existingSubscription->cancel('Upgraded to new plan');
            }

            $subscription = Subscription::create([
                'tenant_id' => $tenant->id,
                'subscription_plan_id' => $plan->id,
                'billing_cycle' => $billingCycle,
                'status' => 'active',
                'starts_at' => now(),
                'ends_at' => now()->addDays($periodDays),
            ]);

            $invoice->update(['subscription_id' => $subscription->id]);
            $subscription->syncToTenant();
        }

        Log::info('Subscription activated', [
            'tenant_id' => $tenant->id,
            'plan' => $plan->slug,
            'billing_cycle' => $billingCycle,
            'ends_at' => $subscription->ends_at,
        ]);
    }

    /**
     * Verify webhook signature.
     */
    public function verifyWebhookSignature(string $callbackToken): bool
    {
        $webhookToken = config('xendit.webhook_token');

        if (empty($webhookToken)) {
            Log::warning('Xendit webhook token not configured');

            return true;
        }

        return hash_equals($webhookToken, $callbackToken);
    }
}
