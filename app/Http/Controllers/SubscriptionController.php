<?php

namespace App\Http\Controllers;

use App\Models\Subscription;
use App\Models\SubscriptionInvoice;
use App\Models\SubscriptionPlan;
use App\Services\XenditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SubscriptionController extends Controller
{
    public function __construct(protected XenditService $xenditService) {}

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
}
