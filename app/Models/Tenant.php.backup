<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tenant extends Model
{
    use HasFactory, HasUuid;

    /**
     * Mass Assignment
     * Field yang boleh diisi via create() atau fill()
     */
    protected $fillable = [
        'code',
        'name',
        'logo',
        'email',
        'phone',
        'currency',
        'timezone',
        'tax_percentage',
        'service_charge_percentage',
        'subscription_plan',
        'subscription_expires_at',
        'max_outlets',
        'is_active',
    ];

    /**
     * Type Casting
     * Cast tipe data otomatis saat diakses
     */
    protected function casts(): array
    {
        return [
            'tax_percentage' => 'decimal:2',
            'service_charge_percentage' => 'decimal:2',
            'subscription_expires_at' => 'datetime',
            'max_outlets' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Relationship: Tenant has many Users
     * Contoh: $tenant->users
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Relationship: Tenant has many Outlets
     * Contoh: $tenant->outlets
     */
    public function outlets(): HasMany
    {
        return $this->hasMany(Outlet::class);
    }

    /**
     * Relationship: Tenant has many Roles
     * Contoh: $tenant->roles
     */
    public function roles(): HasMany
    {
        return $this->hasMany(Role::class);
    }

    /**
     * Helper: Cek apakah subscription masih aktif
     */
    public function isSubscriptionActive(): bool
    {
        // Free plan selalu aktif
        if ($this->subscription_plan === 'free') {
            return true;
        }

        // Cek apakah subscription_expires_at masih di masa depan
        return $this->subscription_expires_at && $this->subscription_expires_at->isFuture();
    }

    /**
     * Helper: Cek apakah tenant bisa menambah outlet baru
     */
    public function canAddOutlet(): bool
    {
        // Hitung jumlah outlet saat ini
        // Bandingkan dengan max_outlets
        return $this->outlets()->count() < $this->max_outlets;
    }
}
