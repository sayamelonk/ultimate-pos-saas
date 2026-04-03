<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\SubscriptionInvoiceResource;
use App\Http\Resources\Api\V1\SubscriptionResource;
use App\Models\Product;
use App\Models\Subscription;
use App\Models\SubscriptionInvoice;
use App\Models\SubscriptionPlan;
use App\Services\XenditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SubscriptionController extends Controller
{
    public function __construct(
        private ?XenditService $xenditService = null
    ) {}

    /**
     * Get current subscription status.
     */
    public function show(): JsonResponse
    {
        $user = auth()->user();
        $subscription = Subscription::with('plan')
            ->where('tenant_id', $user->tenant_id)
            ->whereIn('status', [
                Subscription::STATUS_ACTIVE,
                Subscription::STATUS_TRIAL,
                Subscription::STATUS_FROZEN,
                Subscription::STATUS_PENDING,
            ])
            ->latest()
            ->first();

        if (! $subscription) {
            return response()->json(['data' => null]);
        }

        return response()->json([
            'data' => new SubscriptionResource($subscription),
        ]);
    }

    /**
     * Subscribe to a plan.
     */
    public function subscribe(Request $request): JsonResponse
    {
        $user = auth()->user();

        // Only owner can subscribe
        if (! $user->isTenantOwner()) {
            return response()->json([
                'message' => 'Only account owner can manage subscriptions.',
            ], 403);
        }

        $request->validate([
            'plan_id' => ['required', 'exists:subscription_plans,id'],
            'billing_cycle' => ['required', 'in:monthly,yearly'],
        ]);

        // Check if already has active subscription
        $existingSubscription = Subscription::where('tenant_id', $user->tenant_id)
            ->whereIn('status', [Subscription::STATUS_ACTIVE, Subscription::STATUS_TRIAL])
            ->first();

        if ($existingSubscription) {
            return response()->json([
                'message' => 'You already have an active subscription. Please upgrade or cancel first.',
            ], 400);
        }

        $plan = SubscriptionPlan::findOrFail($request->plan_id);
        $price = $plan->getPrice($request->billing_cycle);

        return DB::transaction(function () use ($user, $plan, $request, $price) {
            // Create subscription
            $subscription = Subscription::create([
                'tenant_id' => $user->tenant_id,
                'subscription_plan_id' => $plan->id,
                'billing_cycle' => $request->billing_cycle,
                'price' => $price,
                'status' => Subscription::STATUS_PENDING,
                'current_period_start' => now(),
                'current_period_end' => $request->billing_cycle === 'yearly'
                    ? now()->addYear()
                    : now()->addMonth(),
            ]);

            // Calculate tax (11% PPN)
            $taxAmount = $price * 0.11;
            $totalAmount = $price + $taxAmount;

            // Create invoice
            $invoice = SubscriptionInvoice::create([
                'tenant_id' => $user->tenant_id,
                'subscription_id' => $subscription->id,
                'subscription_plan_id' => $plan->id,
                'invoice_number' => SubscriptionInvoice::generateInvoiceNumber(),
                'amount' => $price,
                'tax_amount' => $taxAmount,
                'total_amount' => $totalAmount,
                'billing_cycle' => $request->billing_cycle,
                'period_start' => $subscription->current_period_start,
                'period_end' => $subscription->current_period_end,
                'status' => 'pending',
            ]);

            // Create Xendit invoice if service available
            $paymentUrl = null;
            if ($this->xenditService) {
                $xenditInvoice = $this->xenditService->createInvoice($invoice, $user->tenant);
                $invoice->update([
                    'xendit_invoice_id' => $xenditInvoice['id'] ?? null,
                    'xendit_invoice_url' => $xenditInvoice['invoice_url'] ?? null,
                ]);
                $paymentUrl = $xenditInvoice['invoice_url'] ?? null;
            }

            return response()->json([
                'data' => [
                    'subscription' => new SubscriptionResource($subscription->load('plan')),
                    'invoice' => [
                        'id' => $invoice->id,
                        'invoice_number' => $invoice->invoice_number,
                        'amount' => (int) $invoice->total_amount,
                        'payment_url' => $paymentUrl,
                    ],
                ],
                'message' => 'Subscription created. Please complete payment.',
            ], 201);
        });
    }

    /**
     * Start trial subscription.
     */
    public function startTrial(): JsonResponse
    {
        $user = auth()->user();

        // Only owner can start trial
        if (! $user->isTenantOwner()) {
            return response()->json([
                'message' => 'Only account owner can start trial.',
            ], 403);
        }

        // Check if trial already used
        $hasUsedTrial = Subscription::where('tenant_id', $user->tenant_id)
            ->whereNotNull('trial_ends_at')
            ->exists();

        if ($hasUsedTrial || $user->tenant->trial_used) {
            return response()->json([
                'message' => 'Trial period has already been used.',
            ], 400);
        }

        // Get Professional plan for trial
        $professionalPlan = SubscriptionPlan::where('slug', 'professional')->first();

        if (! $professionalPlan) {
            return response()->json([
                'message' => 'Trial plan not available.',
            ], 500);
        }

        $subscription = Subscription::createTrial($user->tenant);

        return response()->json([
            'data' => new SubscriptionResource($subscription->load('plan')),
            'message' => 'Trial started successfully. You have 14 days of full access.',
        ], 201);
    }

    /**
     * Upgrade subscription.
     */
    public function upgrade(Request $request): JsonResponse
    {
        $user = auth()->user();

        // Only owner can upgrade
        if (! $user->isTenantOwner()) {
            return response()->json([
                'message' => 'Only account owner can manage subscriptions.',
            ], 403);
        }

        $request->validate([
            'plan_id' => ['required', 'exists:subscription_plans,id'],
        ]);

        $currentSubscription = Subscription::where('tenant_id', $user->tenant_id)
            ->whereIn('status', [Subscription::STATUS_ACTIVE, Subscription::STATUS_TRIAL])
            ->with('plan')
            ->first();

        if (! $currentSubscription) {
            return response()->json([
                'message' => 'No active subscription found.',
            ], 400);
        }

        $newPlan = SubscriptionPlan::findOrFail($request->plan_id);

        // Check if same plan
        if ($currentSubscription->subscription_plan_id == $newPlan->id) {
            return response()->json([
                'message' => 'You are already subscribed to this plan.',
            ], 400);
        }

        // Check if downgrade (by price)
        $currentPrice = $currentSubscription->plan->price_monthly;
        $newPrice = $newPlan->price_monthly;

        if ($newPrice < $currentPrice) {
            return response()->json([
                'message' => 'Cannot upgrade to a lower tier plan. Please use downgrade instead.',
            ], 400);
        }

        // Calculate proration
        $remainingDays = $currentSubscription->daysRemaining();
        $totalDays = $currentSubscription->billing_cycle === 'yearly' ? 365 : 30;
        $creditAmount = ($currentSubscription->price / $totalDays) * $remainingDays;
        $newPlanPrice = $newPlan->getPrice($currentSubscription->billing_cycle);
        $amountDue = $newPlanPrice - $creditAmount;

        return DB::transaction(function () use ($user, $currentSubscription, $newPlan, $newPlanPrice, $creditAmount, $amountDue, $remainingDays) {
            // Update subscription
            $currentSubscription->update([
                'subscription_plan_id' => $newPlan->id,
                'price' => $newPlanPrice,
            ]);

            // Calculate tax
            $taxAmount = $amountDue * 0.11;
            $totalAmount = $amountDue + $taxAmount;

            // Create invoice for the difference
            $invoice = SubscriptionInvoice::create([
                'tenant_id' => $user->tenant_id,
                'subscription_id' => $currentSubscription->id,
                'subscription_plan_id' => $newPlan->id,
                'invoice_number' => SubscriptionInvoice::generateInvoiceNumber(),
                'amount' => $amountDue,
                'tax_amount' => $taxAmount,
                'total_amount' => $totalAmount,
                'billing_cycle' => $currentSubscription->billing_cycle,
                'period_start' => now(),
                'period_end' => $currentSubscription->current_period_end,
                'status' => 'pending',
                'notes' => 'Upgrade proration',
            ]);

            // Sync to tenant
            $currentSubscription->syncToTenant();

            return response()->json([
                'data' => [
                    'subscription' => new SubscriptionResource($currentSubscription->load('plan')),
                    'proration' => [
                        'remaining_days' => $remainingDays,
                        'credit_amount' => (int) $creditAmount,
                        'new_plan_price' => (int) $newPlanPrice,
                        'amount_due' => (int) $amountDue,
                    ],
                    'invoice' => new SubscriptionInvoiceResource($invoice->load('plan')),
                ],
                'message' => 'Subscription upgraded successfully.',
            ]);
        });
    }

    /**
     * Calculate upgrade proration.
     */
    public function calculateUpgrade(Request $request): JsonResponse
    {
        $user = auth()->user();

        $request->validate([
            'plan_id' => ['required', 'exists:subscription_plans,id'],
        ]);

        $currentSubscription = Subscription::where('tenant_id', $user->tenant_id)
            ->whereIn('status', [Subscription::STATUS_ACTIVE, Subscription::STATUS_TRIAL])
            ->with('plan')
            ->first();

        if (! $currentSubscription) {
            return response()->json([
                'message' => 'No active subscription found.',
            ], 400);
        }

        $newPlan = SubscriptionPlan::findOrFail($request->plan_id);

        // Calculate proration
        $remainingDays = $currentSubscription->daysRemaining();
        $totalDays = $currentSubscription->billing_cycle === 'yearly' ? 365 : 30;
        $creditAmount = ($currentSubscription->price / $totalDays) * $remainingDays;
        $newPlanPrice = $newPlan->getPrice($currentSubscription->billing_cycle);
        $amountDue = max(0, $newPlanPrice - $creditAmount);

        return response()->json([
            'data' => [
                'current_plan' => [
                    'name' => $currentSubscription->plan->name,
                    'price' => (int) $currentSubscription->price,
                ],
                'new_plan' => [
                    'name' => $newPlan->name,
                    'price' => (int) $newPlanPrice,
                ],
                'proration' => [
                    'remaining_days' => $remainingDays,
                    'total_days' => $totalDays,
                    'credit_amount' => (int) $creditAmount,
                    'amount_due' => (int) $amountDue,
                ],
            ],
        ]);
    }

    /**
     * Cancel subscription.
     */
    public function cancel(Request $request): JsonResponse
    {
        $user = auth()->user();

        // Only owner can cancel
        if (! $user->isTenantOwner()) {
            return response()->json([
                'message' => 'Only account owner can manage subscriptions.',
            ], 403);
        }

        $request->validate([
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $subscription = Subscription::where('tenant_id', $user->tenant_id)
            ->whereIn('status', [Subscription::STATUS_ACTIVE, Subscription::STATUS_TRIAL])
            ->first();

        if (! $subscription) {
            // Check if already cancelled
            $cancelledSubscription = Subscription::where('tenant_id', $user->tenant_id)
                ->where('status', Subscription::STATUS_CANCELLED)
                ->first();

            if ($cancelledSubscription) {
                return response()->json([
                    'message' => 'Subscription is already cancelled.',
                ], 400);
            }

            return response()->json([
                'message' => 'No active subscription found.',
            ], 400);
        }

        $subscription->cancel($request->reason);

        return response()->json([
            'data' => [
                'id' => $subscription->id,
                'status' => $subscription->status,
                'cancelled_at' => $subscription->cancelled_at?->toIso8601String(),
                'cancellation_reason' => $subscription->cancellation_reason,
                'access_until' => $subscription->current_period_end?->toIso8601String(),
            ],
            'message' => 'Subscription cancelled. You will have access until the end of your billing period.',
        ]);
    }

    /**
     * Reactivate frozen/expired subscription.
     */
    public function reactivate(): JsonResponse
    {
        $user = auth()->user();

        // Only owner can reactivate
        if (! $user->isTenantOwner()) {
            return response()->json([
                'message' => 'Only account owner can manage subscriptions.',
            ], 403);
        }

        $subscription = Subscription::where('tenant_id', $user->tenant_id)
            ->whereIn('status', [Subscription::STATUS_FROZEN, Subscription::STATUS_EXPIRED])
            ->with('plan')
            ->first();

        if (! $subscription) {
            return response()->json([
                'message' => 'Subscription is not frozen or expired.',
            ], 400);
        }

        $plan = $subscription->plan;
        $price = $plan->getPrice($subscription->billing_cycle);

        return DB::transaction(function () use ($user, $subscription, $plan, $price) {
            // Calculate tax
            $taxAmount = $price * 0.11;
            $totalAmount = $price + $taxAmount;

            // Create invoice
            $invoice = SubscriptionInvoice::create([
                'tenant_id' => $user->tenant_id,
                'subscription_id' => $subscription->id,
                'subscription_plan_id' => $plan->id,
                'invoice_number' => SubscriptionInvoice::generateInvoiceNumber(),
                'amount' => $price,
                'tax_amount' => $taxAmount,
                'total_amount' => $totalAmount,
                'billing_cycle' => $subscription->billing_cycle,
                'period_start' => now(),
                'period_end' => $subscription->billing_cycle === 'yearly'
                    ? now()->addYear()
                    : now()->addMonth(),
                'status' => 'pending',
                'notes' => 'Reactivation',
            ]);

            return response()->json([
                'data' => [
                    'subscription' => new SubscriptionResource($subscription),
                    'invoice' => new SubscriptionInvoiceResource($invoice->load('plan')),
                ],
                'message' => 'Please complete payment to reactivate your subscription.',
            ]);
        });
    }

    /**
     * Get invoice history.
     */
    public function invoices(Request $request): JsonResponse
    {
        $user = auth()->user();

        $query = SubscriptionInvoice::where('tenant_id', $user->tenant_id)
            ->with('plan')
            ->orderBy('created_at', 'desc');

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $invoices = $query->paginate(20);

        return response()->json([
            'data' => SubscriptionInvoiceResource::collection($invoices),
            'meta' => [
                'current_page' => $invoices->currentPage(),
                'total' => $invoices->total(),
                'per_page' => $invoices->perPage(),
                'last_page' => $invoices->lastPage(),
            ],
        ]);
    }

    /**
     * Get single invoice.
     */
    public function showInvoice(string $id): JsonResponse
    {
        $user = auth()->user();

        $invoice = SubscriptionInvoice::where('tenant_id', $user->tenant_id)
            ->with('plan')
            ->find($id);

        if (! $invoice) {
            return response()->json([
                'message' => 'Invoice not found.',
            ], 404);
        }

        return response()->json([
            'data' => new SubscriptionInvoiceResource($invoice),
        ]);
    }

    /**
     * Get feature access for current subscription.
     */
    public function features(): JsonResponse
    {
        $user = auth()->user();
        $tenant = $user->tenant;

        $subscription = Subscription::where('tenant_id', $tenant->id)
            ->whereIn('status', [Subscription::STATUS_ACTIVE, Subscription::STATUS_TRIAL])
            ->with('plan')
            ->first();

        if (! $subscription) {
            return response()->json([
                'data' => [
                    'plan_name' => 'No Plan',
                    'features' => [],
                    'limits' => [
                        'max_outlets' => 0,
                        'max_users' => 0,
                        'max_products' => 0,
                        'current_outlets' => 0,
                        'current_users' => 0,
                        'current_products' => 0,
                    ],
                ],
            ]);
        }

        $plan = $subscription->plan;

        return response()->json([
            'data' => [
                'plan_name' => $plan->name,
                'features' => $plan->features ?? [],
                'limits' => [
                    'max_outlets' => $plan->max_outlets,
                    'max_users' => $plan->max_users,
                    'max_products' => $plan->max_products,
                    'current_outlets' => $tenant->outlets()->count(),
                    'current_users' => $tenant->users()->count(),
                    'current_products' => Product::where('tenant_id', $tenant->id)->count(),
                ],
            ],
        ]);
    }

    /**
     * Check specific feature access.
     */
    public function checkFeature(string $feature): JsonResponse
    {
        $user = auth()->user();

        $subscription = Subscription::where('tenant_id', $user->tenant_id)
            ->whereIn('status', [Subscription::STATUS_ACTIVE, Subscription::STATUS_TRIAL])
            ->with('plan')
            ->first();

        if (! $subscription) {
            return response()->json([
                'data' => [
                    'feature' => $feature,
                    'has_access' => false,
                ],
            ]);
        }

        $features = $subscription->plan->features ?? [];
        $hasAccess = $features[$feature] ?? false;

        return response()->json([
            'data' => [
                'feature' => $feature,
                'has_access' => (bool) $hasAccess,
            ],
        ]);
    }
}
