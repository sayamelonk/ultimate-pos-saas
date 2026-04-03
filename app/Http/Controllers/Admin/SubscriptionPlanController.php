<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionPlan;
use Illuminate\Http\Request;

class SubscriptionPlanController extends Controller
{
    public function index()
    {
        $plans = SubscriptionPlan::ordered()->get();

        return view('admin.subscription-plans.index', compact('plans'));
    }

    public function create()
    {
        return view('admin.subscription-plans.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:subscription_plans',
            'description' => 'nullable|string',
            'price_monthly' => 'required|numeric|min:0',
            'price_yearly' => 'required|numeric|min:0',
            'max_outlets' => 'required|integer|min:-1',
            'max_users' => 'required|integer|min:-1',
            'max_products' => 'required|integer|min:-1',
            'features' => 'nullable|array',
            'is_active' => 'boolean',
            'sort_order' => 'integer|min:0',
        ]);

        $validated['is_active'] = $request->boolean('is_active');
        $validated['features'] = $request->input('features', []);

        SubscriptionPlan::create($validated);

        return redirect()->route('admin.subscription-plans.index')
            ->with('success', __('admin.subscription_plan_created'));
    }

    public function show(SubscriptionPlan $subscriptionPlan)
    {
        $subscriptionPlan->load(['subscriptions' => function ($query) {
            $query->with('tenant')->latest()->limit(10);
        }]);

        $recentSubscriptions = $subscriptionPlan->subscriptions;
        $totalSubscriptionsCount = $subscriptionPlan->subscriptions()->count();
        $activeSubscriptionsCount = $subscriptionPlan->subscriptions()->where('status', 'active')->count();

        // Calculate revenue
        $monthlyRevenue = $subscriptionPlan->subscriptions()
            ->where('status', 'active')
            ->where('billing_cycle', 'monthly')
            ->count() * $subscriptionPlan->price_monthly;

        $yearlyRevenue = $subscriptionPlan->subscriptions()
            ->where('status', 'active')
            ->where('billing_cycle', 'yearly')
            ->count() * $subscriptionPlan->price_yearly;

        $totalRevenue = $monthlyRevenue + $yearlyRevenue;

        return view('admin.subscription-plans.show', compact(
            'subscriptionPlan',
            'recentSubscriptions',
            'totalSubscriptionsCount',
            'activeSubscriptionsCount',
            'monthlyRevenue',
            'yearlyRevenue',
            'totalRevenue'
        ));
    }

    public function edit(SubscriptionPlan $subscriptionPlan)
    {
        return view('admin.subscription-plans.edit', compact('subscriptionPlan'));
    }

    public function update(Request $request, SubscriptionPlan $subscriptionPlan)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:subscription_plans,slug,'.$subscriptionPlan->id,
            'description' => 'nullable|string',
            'price_monthly' => 'required|numeric|min:0',
            'price_yearly' => 'required|numeric|min:0',
            'max_outlets' => 'required|integer|min:-1',
            'max_users' => 'required|integer|min:-1',
            'max_products' => 'required|integer|min:-1',
            'features' => 'nullable|array',
            'is_active' => 'boolean',
            'sort_order' => 'integer|min:0',
        ]);

        $validated['is_active'] = $request->boolean('is_active');
        $validated['features'] = $request->input('features', []);

        $subscriptionPlan->update($validated);

        return redirect()->route('admin.subscription-plans.index')
            ->with('success', __('admin.subscription_plan_updated'));
    }

    public function destroy(SubscriptionPlan $subscriptionPlan)
    {
        if ($subscriptionPlan->subscriptions()->exists()) {
            return back()->with('error', __('admin.subscription_plan_has_subscriptions'));
        }

        $subscriptionPlan->delete();

        return redirect()->route('admin.subscription-plans.index')
            ->with('success', __('admin.subscription_plan_deleted'));
    }
}
