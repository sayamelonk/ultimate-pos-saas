<?php

namespace App\Services;

use App\Models\QrOrder;
use App\Models\Subscription;
use App\Models\SubscriptionInvoice;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use Carbon\Carbon;
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
     * Create a Xendit invoice for QR order payment (QRIS only).
     *
     * @return array{xendit_invoice_id: string, xendit_invoice_url: string, xendit_response: array, xendit_expired_at: Carbon}
     */
    public function createQrOrderInvoice(QrOrder $qrOrder): array
    {
        $qrOrder->load(['outlet', 'items']);

        $itemsForInvoice = $qrOrder->items->map(fn ($item) => [
            'name' => $item->item_name,
            'quantity' => $item->quantity,
            'price' => (float) $item->unit_price,
        ])->toArray();

        $createInvoiceRequest = new CreateInvoiceRequest([
            'external_id' => $qrOrder->order_number,
            'amount' => (float) $qrOrder->grand_total,
            'description' => "QR Order {$qrOrder->order_number} - Table {$qrOrder->table?->display_name}",
            'invoice_duration' => config('xendit.invoice.invoice_duration', 86400),
            'currency' => config('xendit.invoice.currency', 'IDR'),
            'payment_methods' => ['QRIS'],
            'success_redirect_url' => url("/qr/order/{$qrOrder->id}/status"),
            'failure_redirect_url' => url("/qr/order/{$qrOrder->id}/status"),
            'items' => $itemsForInvoice,
            'metadata' => [
                'type' => 'qr_order',
                'qr_order_id' => $qrOrder->id,
                'tenant_id' => $qrOrder->tenant_id,
                'outlet_id' => $qrOrder->outlet_id,
            ],
        ]);

        try {
            $invoice = $this->invoiceApi->createInvoice($createInvoiceRequest);

            return [
                'xendit_invoice_id' => $invoice->getId(),
                'xendit_invoice_url' => $invoice->getInvoiceUrl(),
                'xendit_response' => json_decode(json_encode($invoice), true),
                'xendit_expired_at' => now()->addSeconds(config('xendit.invoice.invoice_duration', 86400)),
            ];
        } catch (Exception $e) {
            Log::error('Xendit create QR order invoice error', [
                'qr_order_id' => $qrOrder->id,
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
    public function handlePaymentCallback(array $payload): SubscriptionInvoice|QrOrder|null
    {
        $invoiceId = $payload['id'] ?? null;
        $status = $payload['status'] ?? null;
        $metadata = $payload['metadata'] ?? [];
        $externalId = $payload['external_id'] ?? null;

        if (! $invoiceId) {
            Log::warning('Xendit callback: missing invoice id', $payload);

            return null;
        }

        // Check if this is a QR order payment
        // 1. Via metadata (if Xendit returns it)
        // 2. Via external_id prefix (QR-) as fallback
        // 3. Via xendit_invoice_id match in qr_orders table
        if (($metadata['type'] ?? null) === 'qr_order') {
            return $this->handleQrOrderCallback($payload, $status, $metadata);
        }

        if ($externalId && str_starts_with($externalId, 'QR-')) {
            return $this->handleQrOrderCallbackByExternalId($payload, $status, $externalId);
        }

        $qrOrder = QrOrder::where('xendit_invoice_id', $invoiceId)->first();
        if ($qrOrder) {
            return $this->handleQrOrderCallbackByModel($payload, $status, $qrOrder);
        }

        // Default: subscription payment
        $invoice = SubscriptionInvoice::where('xendit_invoice_id', $invoiceId)->first();

        if (! $invoice) {
            Log::warning('Xendit callback: invoice not found', [
                'xendit_invoice_id' => $invoiceId,
                'external_id' => $externalId,
            ]);

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
     * Handle QR order payment callback.
     */
    protected function handleQrOrderCallback(array $payload, ?string $status, array $metadata): ?QrOrder
    {
        $qrOrderId = $metadata['qr_order_id'] ?? null;

        if (! $qrOrderId) {
            Log::warning('Xendit QR callback: missing qr_order_id', $payload);

            return null;
        }

        $qrOrder = QrOrder::find($qrOrderId);

        if (! $qrOrder) {
            Log::warning('Xendit QR callback: QR order not found', ['qr_order_id' => $qrOrderId]);

            return null;
        }

        switch ($status) {
            case 'PAID':
            case 'SETTLED':
                $qrOrderService = app(QrOrderService::class);
                $qrOrderService->handlePaymentSuccess($qrOrder, $payload);
                break;

            case 'EXPIRED':
                $qrOrder->markAsExpired();
                break;

            case 'FAILED':
                $qrOrder->markAsCancelled();
                break;
        }

        return $qrOrder;
    }

    /**
     * Handle QR order callback by external_id (order_number).
     * Fallback when Xendit doesn't return metadata in webhook.
     */
    protected function handleQrOrderCallbackByExternalId(array $payload, ?string $status, string $externalId): ?QrOrder
    {
        $qrOrder = QrOrder::where('order_number', $externalId)->first();

        if (! $qrOrder) {
            Log::warning('Xendit QR callback: QR order not found by external_id', ['external_id' => $externalId]);

            return null;
        }

        return $this->handleQrOrderCallbackByModel($payload, $status, $qrOrder);
    }

    /**
     * Process QR order callback with a resolved QrOrder model.
     */
    protected function handleQrOrderCallbackByModel(array $payload, ?string $status, QrOrder $qrOrder): QrOrder
    {
        switch ($status) {
            case 'PAID':
            case 'SETTLED':
                $qrOrderService = app(QrOrderService::class);
                $qrOrderService->handlePaymentSuccess($qrOrder, $payload);
                break;

            case 'EXPIRED':
                $qrOrder->markAsExpired();
                break;

            case 'FAILED':
                $qrOrder->markAsCancelled();
                break;
        }

        return $qrOrder;
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

    /**
     * Create an upgrade invoice with proration.
     */
    public function createUpgradeInvoice(
        Tenant $tenant,
        SubscriptionPlan $plan,
        string $billingCycle = 'monthly',
        ?Subscription $currentSubscription = null,
        ?array $proration = null
    ): SubscriptionInvoice {
        // Use proration amount if available, otherwise full price
        $amount = $proration['total_to_pay'] ?? $plan->getPrice($billingCycle);

        // Minimum amount for Xendit is 1000 IDR
        if ($amount < 1000) {
            $amount = 1000;
        }

        $invoiceNumber = SubscriptionInvoice::generateInvoiceNumber();

        $description = "Upgrade to {$plan->name} - ".ucfirst($billingCycle);
        if ($proration && ($proration['credit_amount'] ?? 0) > 0) {
            $description .= ' (Proration dengan kredit Rp '.number_format($proration['credit_amount'], 0, ',', '.').')';
        }

        $createInvoiceRequest = new CreateInvoiceRequest([
            'external_id' => $invoiceNumber,
            'amount' => $amount,
            'payer_email' => $tenant->email,
            'description' => $description,
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
                    'name' => "Upgrade to {$plan->name}",
                    'quantity' => 1,
                    'price' => $amount,
                ],
            ],
            'metadata' => [
                'tenant_id' => $tenant->id,
                'plan_id' => $plan->id,
                'billing_cycle' => $billingCycle,
                'subscription_id' => $currentSubscription?->id,
                'is_upgrade' => true,
                'proration' => $proration,
            ],
        ]);

        try {
            $invoice = $this->invoiceApi->createInvoice($createInvoiceRequest);

            $subscriptionInvoice = SubscriptionInvoice::create([
                'tenant_id' => $tenant->id,
                'subscription_id' => $currentSubscription?->id,
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
                'notes' => $proration ? json_encode($proration) : null,
            ]);

            return $subscriptionInvoice;
        } catch (Exception $e) {
            Log::error('Xendit create upgrade invoice error', [
                'tenant_id' => $tenant->id,
                'plan_id' => $plan->id,
                'proration' => $proration,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
