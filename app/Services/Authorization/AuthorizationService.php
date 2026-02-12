<?php

namespace App\Services\Authorization;

use App\Models\AuthorizationLog;
use App\Models\AuthorizationSetting;
use App\Models\PinAttempt;
use App\Models\User;
use App\Models\UserPin;
use Illuminate\Support\Facades\Hash;

class AuthorizationService
{
    /**
     * Check if action requires authorization
     */
    public function requiresAuthorization(
        string $tenantId,
        string $action,
        ?float $discountPercent = null
    ): bool {
        $settings = AuthorizationSetting::getForTenant($tenantId);

        return $settings->requiresAuth($action, $discountPercent);
    }

    /**
     * Verify PIN and authorize action
     */
    public function verifyAndAuthorize(
        string $tenantId,
        string $outletId,
        string $requestedBy,
        string $action,
        string $pin,
        ?string $referenceType = null,
        ?string $referenceId = null,
        ?string $referenceNumber = null,
        ?float $amount = null,
        ?string $reason = null,
        ?array $metadata = null
    ): array {
        $settings = AuthorizationSetting::getForTenant($tenantId);

        // Check lockout
        if (PinAttempt::isLockedOut($tenantId, $outletId, $settings->max_pin_attempts, $settings->lockout_minutes)) {
            return [
                'success' => false,
                'message' => "Too many failed attempts. Please wait {$settings->lockout_minutes} minutes.",
                'locked_out' => true,
            ];
        }

        // Find authorizer by PIN
        $authorizer = $this->findAuthorizerByPin($tenantId, $pin);

        if (! $authorizer) {
            // Log failed attempt
            PinAttempt::log($tenantId, $outletId, $requestedBy, null, false, request()->ip());

            $remainingAttempts = $settings->max_pin_attempts -
                PinAttempt::getFailedAttemptCount($tenantId, $outletId, $settings->lockout_minutes);

            return [
                'success' => false,
                'message' => "Invalid PIN. {$remainingAttempts} attempts remaining.",
                'remaining_attempts' => $remainingAttempts,
            ];
        }

        // Check if authorizer can authorize
        if (! $authorizer->canAuthorize()) {
            PinAttempt::log($tenantId, $outletId, $requestedBy, $authorizer->id, false, request()->ip());

            return [
                'success' => false,
                'message' => 'This user does not have authorization privileges.',
            ];
        }

        // Log successful attempt
        PinAttempt::log($tenantId, $outletId, $requestedBy, $authorizer->id, true, request()->ip());

        // Create authorization log
        $log = AuthorizationLog::create([
            'tenant_id' => $tenantId,
            'outlet_id' => $outletId,
            'requested_by' => $requestedBy,
            'authorized_by' => $authorizer->id,
            'action_type' => $action,
            'status' => AuthorizationLog::STATUS_APPROVED,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'reference_number' => $referenceNumber,
            'amount' => $amount,
            'reason' => $reason,
            'metadata' => $metadata,
            'requested_at' => now(),
            'responded_at' => now(),
        ]);

        return [
            'success' => true,
            'message' => 'Authorization approved.',
            'authorization_log_id' => $log->id,
            'authorized_by' => [
                'id' => $authorizer->id,
                'name' => $authorizer->name,
            ],
        ];
    }

    /**
     * Find user who can authorize by PIN
     */
    public function findAuthorizerByPin(string $tenantId, string $pin): ?User
    {
        // Get all users with PIN in this tenant who can authorize
        $users = User::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->whereHas('userPin', function ($q) {
                $q->where('is_active', true);
            })
            ->with('userPin')
            ->get();

        foreach ($users as $user) {
            if ($user->verifyPin($pin) && $user->canAuthorize()) {
                return $user;
            }
        }

        return null;
    }

    /**
     * Set or update user PIN
     */
    public function setUserPin(string $userId, string $pin): UserPin
    {
        $userPin = UserPin::updateOrCreate(
            ['user_id' => $userId],
            [
                'pin_hash' => Hash::make($pin),
                'is_active' => true,
            ]
        );

        return $userPin;
    }

    /**
     * Deactivate user PIN
     */
    public function deactivateUserPin(string $userId): bool
    {
        return UserPin::where('user_id', $userId)->update(['is_active' => false]) > 0;
    }

    /**
     * Get authorization logs
     */
    public function getAuthorizationLogs(
        string $tenantId,
        ?string $outletId = null,
        ?string $action = null,
        ?string $status = null,
        int $limit = 50
    ) {
        $query = AuthorizationLog::where('tenant_id', $tenantId)
            ->with(['requestedBy', 'authorizedBy', 'outlet']);

        if ($outletId) {
            $query->where('outlet_id', $outletId);
        }

        if ($action) {
            $query->where('action_type', $action);
        }

        if ($status) {
            $query->where('status', $status);
        }

        return $query->orderBy('created_at', 'desc')->limit($limit)->get();
    }

    /**
     * Get users who can authorize (have PIN set)
     */
    public function getAuthorizers(string $tenantId): \Illuminate\Database\Eloquent\Collection
    {
        return User::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->whereHas('userPin', function ($q) {
                $q->where('is_active', true);
            })
            ->whereHas('roles', function ($q) {
                $q->whereIn('slug', ['supervisor', 'spv', 'manager', 'outlet-manager', 'admin', 'administrator', 'tenant-owner', 'super-admin']);
            })
            ->get();
    }
}
