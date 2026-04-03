<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\SubscriptionPlanResource;
use App\Models\SubscriptionPlan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SubscriptionPlanController extends Controller
{
    /**
     * List all active subscription plans.
     */
    public function index(): AnonymousResourceCollection
    {
        $plans = SubscriptionPlan::query()
            ->active()
            ->ordered()
            ->get();

        return SubscriptionPlanResource::collection($plans);
    }

    /**
     * Show a single subscription plan by ID.
     */
    public function show(string $id): SubscriptionPlanResource|JsonResponse
    {
        $plan = SubscriptionPlan::query()
            ->active()
            ->find($id);

        if (! $plan) {
            return response()->json([
                'message' => 'Subscription plan not found.',
            ], 404);
        }

        return new SubscriptionPlanResource($plan);
    }

    /**
     * Show a single subscription plan by slug.
     */
    public function showBySlug(string $slug): SubscriptionPlanResource|JsonResponse
    {
        $plan = SubscriptionPlan::query()
            ->active()
            ->where('slug', $slug)
            ->first();

        if (! $plan) {
            return response()->json([
                'message' => 'Subscription plan not found.',
            ], 404);
        }

        return new SubscriptionPlanResource($plan);
    }

    /**
     * Compare multiple subscription plans.
     */
    public function compare(Request $request): JsonResponse
    {
        $planSlugs = explode(',', $request->query('plans', ''));
        $planSlugs = array_filter(array_map('trim', $planSlugs));

        if (empty($planSlugs)) {
            return response()->json([
                'message' => 'Please provide plan slugs to compare.',
            ], 400);
        }

        $plans = SubscriptionPlan::query()
            ->active()
            ->whereIn('slug', $planSlugs)
            ->ordered()
            ->get();

        // Collect all unique features from all plans
        $allFeatures = [];
        foreach ($plans as $plan) {
            if (is_array($plan->features)) {
                $allFeatures = array_merge($allFeatures, array_keys($plan->features));
            }
        }
        $allFeatures = array_unique($allFeatures);

        return response()->json([
            'data' => SubscriptionPlanResource::collection($plans),
            'feature_list' => $allFeatures,
        ]);
    }
}
