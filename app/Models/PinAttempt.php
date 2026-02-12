<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PinAttempt extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'tenant_id',
        'outlet_id',
        'user_id',
        'attempted_for',
        'success',
        'ip_address',
        'attempted_at',
    ];

    protected function casts(): array
    {
        return [
            'success' => 'boolean',
            'attempted_at' => 'datetime',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function outlet(): BelongsTo
    {
        return $this->belongsTo(Outlet::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function log(
        string $tenantId,
        string $outletId,
        ?string $userId,
        ?string $attemptedFor,
        bool $success,
        ?string $ipAddress = null
    ): self {
        return self::create([
            'tenant_id' => $tenantId,
            'outlet_id' => $outletId,
            'user_id' => $userId,
            'attempted_for' => $attemptedFor,
            'success' => $success,
            'ip_address' => $ipAddress,
            'attempted_at' => now(),
        ]);
    }

    public static function getFailedAttemptCount(
        string $tenantId,
        string $outletId,
        int $withinMinutes = 5
    ): int {
        return self::where('tenant_id', $tenantId)
            ->where('outlet_id', $outletId)
            ->where('success', false)
            ->where('attempted_at', '>=', now()->subMinutes($withinMinutes))
            ->count();
    }

    public static function isLockedOut(
        string $tenantId,
        string $outletId,
        int $maxAttempts = 3,
        int $lockoutMinutes = 5
    ): bool {
        return self::getFailedAttemptCount($tenantId, $outletId, $lockoutMinutes) >= $maxAttempts;
    }
}
