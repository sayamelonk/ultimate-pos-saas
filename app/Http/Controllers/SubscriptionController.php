<?php

namespace App\Http\Controllers;

use App\Models\Subscription;
use App\Models\SubscriptionInvoice;
use App\Models\SubscriptionPlan;
use App\Services\ProrationService;
use App\Services\XenditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SubscriptionController extends Controller
{
    public function __construct(
        protected XenditService $xenditService,
        protected ProrationService $prorationService
    ) {}

    /**
     * Display subscription plans.
     */
    public function plans(): View
    {
        $plans = SubscriptionPlan::active()->ordered()->get();
        $tenant = auth()->user()->tenant;
        $currentSubscription = $tenant?->activeSubscription;

        return view('subscription.plans', compact('plans', 'tenant', 'currentSubscription'));
    }

    /**
     * Display choose plan page (for trial/frozen users).
     */
    public function choosePlan(): View
    {
        $plans = SubscriptionPlan::active()->ordered()->get();
        $tenant = auth()->user()->tenant;
        $subscription = $tenant?->activeSubscription;
        $currentPlan = $subscription?->plan;

        return view('subscription.choose-plan', compact('plans', 'subscription', 'currentPlan'));
    }

    /**
     * Show current subscription status.
     */
    public function index(): View
    {
        $tenant = auth()->user()->tenant;
        $currentSubscription = $tenant?->activeSubscription()->with('plan')->first();
        $invoices = $tenant?->subscriptionInvoices()
            ->with('plan')
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('subscription.index', compact('tenant', 'currentSubscription', 'invoices'));
    }

    /**
     * Subscribe to a plan.
     */
    public function subscribe(Request $request, SubscriptionPlan $plan): RedirectResponse
    {
        $validated = $request->validate([
            'billing_cycle' => 'required|in:monthly,yearly',
        ]);

        $tenant = auth()->user()->tenant;

        if (! $tenant) {
            return back()->with('error', 'Tenant tidak ditemukan.');
        }

        try {
            $invoice = $this->xenditService->createSubscriptionInvoice(
                $tenant,
                $plan,
                $validated['billing_cycle']
            );

            return redirect($invoice->xendit_invoice_url);
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal membuat invoice: '.$e->getMessage());
        }
    }

    /**
     * Renew current subscription.
     */
    public function renew(): RedirectResponse
    {
        $tenant = auth()->user()->tenant;
        $subscription = $tenant?->activeSubscription;

        if (! $subscription) {
            return redirect()->route('subscription.plans')
                ->with('error', 'Tidak ada subscription aktif untuk diperpanjang.');
        }

        try {
            $invoice = $this->xenditService->createSubscriptionInvoice(
                $tenant,
                $subscription->plan,
                $subscription->billing_cycle,
                $subscription
            );

            return redirect($invoice->xendit_invoice_url);
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal membuat invoice: '.$e->getMessage());
        }
    }

    /**
     * Cancel subscription.
     */
    public function cancel(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        $tenant = auth()->user()->tenant;
        $subscription = $tenant?->activeSubscription;

        if (! $subscription) {
            return back()->with('error', 'Tidak ada subscription aktif.');
        }

        $subscription->cancel($validated['reason'] ?? null);

        return back()->with('success', 'Subscription akan dibatalkan pada akhir periode.');
    }

    /**
     * Payment success callback page.
     */
    public function paymentSuccess(Request $request): View
    {
        return view('subscription.payment-success');
    }

    /**
     * Payment failed callback page.
     */
    public function paymentFailed(Request $request): View
    {
        return view('subscription.payment-failed');
    }

    /**
     * Show invoice detail.
     */
    public function showInvoice(SubscriptionInvoice $invoice): View
    {
        $tenant = auth()->user()->tenant;

        if ($invoice->tenant_id !== $tenant->id) {
            abort(403);
        }

        return view('subscription.invoice', compact('invoice'));
    }

    /**
     * Show upgrade preview with proration calculation.
     */
    public function upgradePreview(Request $request, SubscriptionPlan $plan): View|RedirectResponse
    {
        $tenant = auth()->user()->tenant;
        $currentSubscription = $tenant?->activeSubscription;

        if (! $currentSubscription) {
            return redirect()->route('subscription.choose-plan')
                ->with('error', 'Tidak ada subscription aktif.');
        }

        $billingCycle = $request->get('billing_cycle', $currentSubscription->billing_cycle ?? 'monthly');

        // Check if it's actually an upgrade
        $currentPlan = $currentSubscription->plan;
        $isUpgrade = $this->prorationService->isUpgrade($currentPlan, $plan);
        $isDowngrade = $this->prorationService->isDowngrade($currentPlan, $plan);

        // Calculate proration
        $proration = $this->prorationService->calculateUpgradeProration(
            $currentSubscription,
            $plan,
            $billingCycle
        );

        $formattedProration = $this->prorationService->formatForDisplay($proration);

        return view('subscription.upgrade-preview', compact(
            'plan',
            'currentSubscription',
            'currentPlan',
            'billingCycle',
            'isUpgrade',
            'isDowngrade',
            'proration',
            'formattedProration'
        ));
    }

    /**
     * Process upgrade with proration.
     */
    public function upgrade(Request $request, SubscriptionPlan $plan): RedirectResponse
    {
        $validated = $request->validate([
            'billing_cycle' => 'required|in:monthly,yearly',
        ]);

        $tenant = auth()->user()->tenant;
        $currentSubscription = $tenant?->activeSubscription;

        if (! $tenant) {
            return back()->with('error', 'Tenant tidak ditemukan.');
        }

        // Calculate proration
        $proration = null;
        if ($currentSubscription && ! $currentSubscription->isTrial() && ! $currentSubscription->isFrozen()) {
            $proration = $this->prorationService->calculateUpgradeProration(
                $currentSubscription,
                $plan,
                $validated['billing_cycle']
            );
        }

        try {
            $invoice = $this->xenditService->createUpgradeInvoice(
                $tenant,
                $plan,
                $validated['billing_cycle'],
                $currentSubscription,
                $proration
            );

            return redirect($invoice->xendit_invoice_url);
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal membuat invoice: '.$e->getMessage());
        }
    }
}
