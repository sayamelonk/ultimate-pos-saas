<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, HasUuid, Notifiable;

    protected $fillable = [
        'tenant_id',
        'email',
        'password',
        'pin',
        'name',
        'phone',
        'avatar',
        'is_active',
        'email_verified_at',
        'last_login_at',
        'locale',
    ];

    protected $hidden = [
        'password',
        'pin',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'user_roles');
    }

    public function outlets(): BelongsToMany
    {
        return $this->belongsToMany(Outlet::class, 'user_outlets')
            ->withPivot('is_default');
    }

    public function defaultOutlet(): ?Outlet
    {
        return $this->outlets()
            ->wherePivot('is_default', true)
            ->first();
    }

    public function hasRole(string $slug): bool
    {
        return $this->roles()->where('slug', $slug)->exists();
    }

    public function hasAnyRole(array $slugs): bool
    {
        return $this->roles()->whereIn('slug', $slugs)->exists();
    }

    public function hasPermission(string $permissionSlug): bool
    {
        return $this->roles()
            ->whereHas('permissions', function ($query) use ($permissionSlug) {
                $query->where('slug', $permissionSlug);
            })
            ->exists();
    }

    public function isSuperAdmin(): bool
    {
        return $this->hasRole('super-admin');
    }

    public function isTenantOwner(): bool
    {
        return $this->hasRole('tenant-owner');
    }

    public function canAccessOutlet(string $outletId): bool
    {
        if ($this->isSuperAdmin() || $this->isTenantOwner()) {
            return true;
        }

        return $this->outlets()->where('outlets.id', $outletId)->exists();
    }

    public function getInitialsAttribute(): string
    {
        $words = explode(' ', $this->name);
        $initials = '';

        foreach (array_slice($words, 0, 2) as $word) {
            $initials .= strtoupper(substr($word, 0, 1));
        }

        return $initials;
    }

    // ==================== PIN & Authorization ====================

    public function userPin(): HasOne
    {
        return $this->hasOne(UserPin::class);
    }

    public function hasPin(): bool
    {
        return $this->userPin()->where('is_active', true)->exists();
    }

    public function verifyPin(string $pin): bool
    {
        $userPin = $this->userPin;

        if (! $userPin || ! $userPin->is_active) {
            return false;
        }

        return $userPin->verifyPin($pin);
    }

    public function canAuthorize(): bool
    {
        // SPV, Manager, Admin, Tenant Owner can authorize
        return $this->hasAnyRole(['supervisor', 'spv', 'manager', 'outlet-manager', 'admin', 'administrator', 'tenant-owner', 'super-admin']);
    }

    public function isManager(): bool
    {
        return $this->hasAnyRole(['manager', 'outlet-manager', 'admin', 'administrator', 'tenant-owner', 'super-admin']);
    }

    public function isSupervisor(): bool
    {
        return $this->hasAnyRole(['supervisor', 'spv', 'manager', 'outlet-manager', 'admin', 'administrator', 'tenant-owner', 'super-admin']);
    }
}
