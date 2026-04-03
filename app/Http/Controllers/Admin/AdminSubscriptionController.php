<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use Illuminate\Http\Request;

class AdminSubscriptionController extends Controller
{
    public function index(Request $request)
    {
        $query = Subscription::with(['tenant', 'plan'])
            ->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('plan')) {
            $query->where('subscription_plan_id', $request->plan);
        }

        if ($request->filled('tenant')) {
            $query->where('tenant_id', $request->tenant);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('tenant', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        $subscriptions = $query->paginate(20)->withQueryString();
        $plans = SubscriptionPlan::ordered()->get();
        $tenants = Tenant::orderBy('name')->get();

        return view('admin.subscriptions.index', compact('subscriptions', 'plans', 'tenants'));
    }

    public function show(Subscription $subscription)
    {
        $subscription->load(['tenant', 'plan', 'invoices' => function ($query) {
            $query->latest()->limit(10);
        }]);

        return view('admin.subscriptions.show', compact('subscription'));
    }

    public function updateStatus(Request $request, Subscription $subscription)
    {
        $validated = $request->validate([
            'status' => 'required|in:active,cancelled,expired,frozen',
        ]);

        $subscription->update($validated);

        return back()->with('success', __('admin.subscription_status_updated'));
    }
}
