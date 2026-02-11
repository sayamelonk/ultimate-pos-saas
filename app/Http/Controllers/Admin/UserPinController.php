<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuthorizationSetting;
use App\Models\User;
use App\Services\Authorization\AuthorizationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserPinController extends Controller
{
    public function __construct(private AuthorizationService $authService) {}

    /**
     * Show form to set/update PIN for a user
     */
    public function edit(User $user): View
    {
        $authUser = auth()->user();

        // Only allow editing PIN for users in same tenant
        if ($user->tenant_id !== $authUser->tenant_id && ! $authUser->isSuperAdmin()) {
            abort(403);
        }

        $settings = AuthorizationSetting::getForTenant($user->tenant_id);
        $hasPin = $user->hasPin();

        return view('admin.users.pin', compact('user', 'settings', 'hasPin'));
    }

    /**
     * Set or update user PIN
     */
    public function update(Request $request, User $user): RedirectResponse
    {
        $authUser = auth()->user();
        $settings = AuthorizationSetting::getForTenant($user->tenant_id);

        // Only allow editing PIN for users in same tenant
        if ($user->tenant_id !== $authUser->tenant_id && ! $authUser->isSuperAdmin()) {
            abort(403);
        }

        $request->validate([
            'pin' => [
                'required',
                'string',
                'confirmed',
                'regex:/^[0-9]+$/',
                'size:'.$settings->pin_length,
            ],
        ], [
            'pin.size' => "PIN must be exactly {$settings->pin_length} digits.",
            'pin.regex' => 'PIN must contain only numbers.',
        ]);

        $this->authService->setUserPin($user->id, $request->pin);

        return redirect()->route('admin.users.show', $user)
            ->with('success', 'PIN has been set successfully.');
    }

    /**
     * Deactivate user PIN
     */
    public function destroy(User $user): RedirectResponse
    {
        $authUser = auth()->user();

        // Only allow editing PIN for users in same tenant
        if ($user->tenant_id !== $authUser->tenant_id && ! $authUser->isSuperAdmin()) {
            abort(403);
        }

        $this->authService->deactivateUserPin($user->id);

        return redirect()->route('admin.users.show', $user)
            ->with('success', 'PIN has been deactivated.');
    }

    /**
     * Set own PIN (for current user)
     */
    public function editOwn(): View
    {
        $user = auth()->user();
        $settings = AuthorizationSetting::getForTenant($user->tenant_id);
        $hasPin = $user->hasPin();

        return view('admin.users.pin-self', compact('user', 'settings', 'hasPin'));
    }

    /**
     * Update own PIN
     */
    public function updateOwn(Request $request): RedirectResponse
    {
        $user = auth()->user();
        $settings = AuthorizationSetting::getForTenant($user->tenant_id);

        $rules = [
            'pin' => [
                'required',
                'string',
                'confirmed',
                'regex:/^[0-9]+$/',
                'size:'.$settings->pin_length,
            ],
        ];

        // If user already has PIN, require current PIN
        if ($user->hasPin()) {
            $rules['current_pin'] = ['required', 'string'];
        }

        $request->validate($rules, [
            'pin.size' => "PIN must be exactly {$settings->pin_length} digits.",
            'pin.regex' => 'PIN must contain only numbers.',
        ]);

        // Verify current PIN if exists
        if ($user->hasPin() && ! $user->verifyPin($request->current_pin)) {
            return back()->withErrors(['current_pin' => 'Current PIN is incorrect.']);
        }

        $this->authService->setUserPin($user->id, $request->pin);

        return back()->with('success', 'Your PIN has been updated successfully.');
    }
}
