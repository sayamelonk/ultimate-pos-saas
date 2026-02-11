<?php

namespace App\Http\Controllers\POS;

use App\Http\Controllers\Controller;
use App\Models\AuthorizationLog;
use App\Models\AuthorizationSetting;
use App\Services\Authorization\AuthorizationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuthorizationController extends Controller
{
    public function __construct(private AuthorizationService $authService) {}

    /**
     * Check if action requires authorization
     */
    public function checkRequired(Request $request): JsonResponse
    {
        $request->validate([
            'action' => ['required', 'string'],
            'discount_percent' => ['nullable', 'numeric'],
        ]);

        $user = auth()->user();
        $required = $this->authService->requiresAuthorization(
            $user->tenant_id,
            $request->action,
            $request->discount_percent
        );

        // If user can authorize themselves, they don't need authorization
        if ($required && $user->canAuthorize() && $user->hasPin()) {
            $required = false;
        }

        return response()->json([
            'required' => $required,
            'action' => $request->action,
        ]);
    }

    /**
     * Verify PIN and authorize action
     */
    public function verify(Request $request): JsonResponse
    {
        $request->validate([
            'pin' => ['required', 'string', 'min:4', 'max:6'],
            'action' => ['required', 'string'],
            'outlet_id' => ['required', 'exists:outlets,id'],
            'reference_type' => ['nullable', 'string'],
            'reference_id' => ['nullable', 'string'],
            'reference_number' => ['nullable', 'string'],
            'amount' => ['nullable', 'numeric'],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $user = auth()->user();

        $result = $this->authService->verifyAndAuthorize(
            tenantId: $user->tenant_id,
            outletId: $request->outlet_id,
            requestedBy: $user->id,
            action: $request->action,
            pin: $request->pin,
            referenceType: $request->reference_type,
            referenceId: $request->reference_id,
            referenceNumber: $request->reference_number,
            amount: $request->amount,
            reason: $request->reason,
            metadata: $request->metadata
        );

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    /**
     * Get authorization settings
     */
    public function settings(): View
    {
        $user = auth()->user();
        $settings = AuthorizationSetting::getForTenant($user->tenant_id);
        $authorizers = $this->authService->getAuthorizers($user->tenant_id);

        return view('admin.authorization.settings', compact('settings', 'authorizers'));
    }

    /**
     * Update authorization settings
     */
    public function updateSettings(Request $request)
    {
        $request->validate([
            'require_auth_void' => ['boolean'],
            'require_auth_refund' => ['boolean'],
            'require_auth_discount' => ['boolean'],
            'discount_threshold_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'require_auth_price_override' => ['boolean'],
            'require_auth_no_sale' => ['boolean'],
            'require_auth_reprint' => ['boolean'],
            'require_auth_cancel_order' => ['boolean'],
            'pin_length' => ['required', 'integer', 'in:4,6'],
            'max_pin_attempts' => ['required', 'integer', 'min:1', 'max:10'],
            'lockout_minutes' => ['required', 'integer', 'min:1', 'max:60'],
        ]);

        $user = auth()->user();
        $settings = AuthorizationSetting::getForTenant($user->tenant_id);

        $settings->update([
            'require_auth_void' => $request->boolean('require_auth_void'),
            'require_auth_refund' => $request->boolean('require_auth_refund'),
            'require_auth_discount' => $request->boolean('require_auth_discount'),
            'discount_threshold_percent' => $request->discount_threshold_percent,
            'require_auth_price_override' => $request->boolean('require_auth_price_override'),
            'require_auth_no_sale' => $request->boolean('require_auth_no_sale'),
            'require_auth_reprint' => $request->boolean('require_auth_reprint'),
            'require_auth_cancel_order' => $request->boolean('require_auth_cancel_order'),
            'pin_length' => $request->pin_length,
            'max_pin_attempts' => $request->max_pin_attempts,
            'lockout_minutes' => $request->lockout_minutes,
        ]);

        return back()->with('success', 'Authorization settings updated successfully.');
    }

    /**
     * Authorization logs
     */
    public function logs(Request $request): View
    {
        $user = auth()->user();

        $query = AuthorizationLog::where('tenant_id', $user->tenant_id)
            ->with(['requestedBy', 'authorizedBy', 'outlet']);

        if ($request->filled('outlet_id')) {
            $query->where('outlet_id', $request->outlet_id);
        }

        if ($request->filled('action_type')) {
            $query->where('action_type', $request->action_type);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $logs = $query->orderBy('created_at', 'desc')
            ->paginate(25)
            ->withQueryString();

        return view('admin.authorization.logs', compact('logs'));
    }
}
