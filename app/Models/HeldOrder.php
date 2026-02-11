<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HeldOrder extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'tenant_id',
        'outlet_id',
        'pos_session_id',
        'user_id',
        'customer_id',
        'hold_number',
        'reference',
        'table_number',
        'items',
        'discounts',
        'subtotal',
        'discount_amount',
        'tax_amount',
        'service_charge_amount',
        'grand_total',
        'notes',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'items' => 'array',
            'discounts' => 'array',
            'subtotal' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'service_charge_amount' => 'decimal:2',
            'grand_total' => 'decimal:2',
            'expires_at' => 'datetime',
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

    public function posSession(): BelongsTo
    {
        return $this->belongsTo(PosSession::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function getItemCount(): int
    {
        return collect($this->items)->sum('quantity');
    }

    public function getDisplayName(): string
    {
        if ($this->reference) {
            return $this->reference;
        }

        if ($this->table_number) {
            return "Table {$this->table_number}";
        }

        return $this->hold_number;
    }

    public static function generateHoldNumber(int $outletId): string
    {
        $today = now()->format('Ymd');
        $count = self::where('outlet_id', $outletId)
            ->whereDate('created_at', today())
            ->count() + 1;

        return "HLD-{$today}-".str_pad($count, 4, '0', STR_PAD_LEFT);
    }
}
