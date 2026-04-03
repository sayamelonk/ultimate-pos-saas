<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Outlet extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'tenant_id',
        'code',
        'name',
        'address',
        'city',
        'province',
        'postal_code',
        'latitude',
        'longitude',
        'phone',
        'email',
        'opening_time',
        'closing_time',
        'tax_percentage',
        'tax_enabled',
        'tax_mode',
        'service_charge_percentage',
        'service_charge_enabled',
        'receipt_header',
        'receipt_footer',
        'receipt_show_logo',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:8',
            'longitude' => 'decimal:8',
            'opening_time' => 'string',
            'closing_time' => 'string',
            'tax_percentage' => 'decimal:2',
            'tax_enabled' => 'boolean',
            'service_charge_percentage' => 'decimal:2',
            'service_charge_enabled' => 'boolean',
            'receipt_show_logo' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_outlets')
            ->withPivot('is_default');
    }

    public function getTaxPercentageAttribute($value): float
    {
        return $value ?? $this->tenant->tax_percentage ?? 11.00;
    }

    public function getServiceChargePercentageAttribute($value): float
    {
        return $value ?? $this->tenant->service_charge_percentage ?? 0;
    }

    /**
     * Check if tax is enabled for this outlet.
     * If outlet tax_enabled is null, inherit from tenant.
     */
    public function isTaxEnabled(): bool
    {
        if ($this->getRawOriginal('tax_enabled') !== null) {
            return (bool) $this->getRawOriginal('tax_enabled');
        }

        return $this->tenant->tax_enabled ?? true;
    }

    /**
     * Check if service charge is enabled for this outlet.
     * If outlet service_charge_enabled is null, inherit from tenant.
     */
    public function isServiceChargeEnabled(): bool
    {
        if ($this->getRawOriginal('service_charge_enabled') !== null) {
            return (bool) $this->getRawOriginal('service_charge_enabled');
        }

        return $this->tenant->service_charge_enabled ?? false;
    }

    /**
     * Get the effective tax percentage considering enable/disable setting.
     * Returns 0 if tax is disabled, otherwise returns the tax percentage.
     */
    public function getEffectiveTaxPercentage(): float
    {
        if (! $this->isTaxEnabled()) {
            return 0;
        }

        // If outlet has its own tax_percentage, use it
        if ($this->getRawOriginal('tax_percentage') !== null) {
            return (float) $this->getRawOriginal('tax_percentage');
        }

        // Otherwise inherit from tenant
        return $this->tenant->tax_percentage ?? 11.00;
    }

    /**
     * Get the effective service charge percentage considering enable/disable setting.
     * Returns 0 if service charge is disabled, otherwise returns the percentage.
     */
    public function getEffectiveServiceChargePercentage(): float
    {
        if (! $this->isServiceChargeEnabled()) {
            return 0;
        }

        // If outlet has its own service_charge_percentage, use it
        if ($this->getRawOriginal('service_charge_percentage') !== null) {
            return (float) $this->getRawOriginal('service_charge_percentage');
        }

        // Otherwise inherit from tenant
        return $this->tenant->service_charge_percentage ?? 0;
    }

    public function getFullAddressAttribute(): string
    {
        $parts = array_filter([
            $this->address,
            $this->city,
            $this->province,
            $this->postal_code,
        ]);

        return implode(', ', $parts);
    }

    public function isOpen(): bool
    {
        $now = now()->setTimezone($this->tenant->timezone ?? 'Asia/Jakarta');
        $currentTime = $now->format('H:i');

        return $currentTime >= $this->opening_time && $currentTime <= $this->closing_time;
    }

    /**
     * Get the tax mode for this outlet.
     * If outlet tax_mode is null, inherit from tenant.
     *
     * @return string 'exclusive' or 'inclusive'
     */
    public function getTaxMode(): string
    {
        if ($this->getRawOriginal('tax_mode') !== null) {
            return $this->getRawOriginal('tax_mode');
        }

        return $this->tenant->tax_mode ?? 'exclusive';
    }

    /**
     * Check if this outlet uses inclusive tax mode.
     */
    public function isTaxInclusive(): bool
    {
        return $this->getTaxMode() === 'inclusive';
    }
}
