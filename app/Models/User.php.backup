<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
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
    ];

    /**
     * Hidden fields
     * Field ini tidak akan muncul saat di-serialize (misal: JSON response)
     */
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

    /**
     * Relationship: User belongs to Tenant
     * Contoh: $user->tenant
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Relationship: User belongs to many Roles (Many-to-Many)
     * Contoh: $user->roles
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'user_roles');
    }

    /**
     * Relationship: User belongs to many Outlets (Many-to-Many)
     * Contoh: $user->outlets
     */
    public function outlets(): BelongsToMany
    {
        return $this->belongsToMany(Outlet::class, 'user_outlets')
            ->withPivot('is_default');
    }

    /**
     * Helper: Get default outlet
     */
    public function defaultOutlet(): ?Outlet
    {
        return $this->outlets()
            ->wherePivot('is_default', true)
            ->first();
    }

    /**
     * Helper: Cek apakah user punya role tertentu
     */
    public function hasRole(string $slug): bool
    {
        return $this->roles()->where('slug', $slug)->exists();
    }

    /**
     * Helper: Cek apakah user punya permission tertentu
     */
    public function hasPermission(string $permissionSlug): bool
    {
        return $this->roles()
            ->whereHas('permissions', function ($query) use ($permissionSlug) {
                $query->where('slug', $permissionSlug);
            })
            ->exists();
    }

    /**
     * Helper: Cek apakah user Super Admin
     */
    public function isSuperAdmin(): bool
    {
        return $this->hasRole('super-admin');
    }

    /**
     * Helper: Cek apakah user bisa akses outlet tertentu
     */
    public function canAccessOutlet(string $outletId): bool
    {
        // Super admin bisa akses semua outlet
        if ($this->isSuperAdmin()) {
            return true;
        }

        // Cek apakah outlet ada di list outlet user
        return $this->outlets()->where('outlets.id', $outletId)->exists();
    }

    /**
     * Accessor: Get initials dari nama
     * Contoh: "John Doe" -> "JD"
     */
    public function getInitialsAttribute(): string
    {
        $words = explode(' ', $this->name);
        $initials = '';

        foreach (array_slice($words, 0, 2) as $word) {
            $initials .= strtoupper(substr($word, 0, 1));
        }

        return $initials;
    }
}
