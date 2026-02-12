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
        'service_charge_percentage',
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
            'service_charge_percentage' => 'decimal:2',
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
}
