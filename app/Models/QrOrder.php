<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QrOrder extends Model
{
    use HasFactory, HasUuid;

    public const STATUS_PENDING = 'pending';

    public const STATUS_WAITING_PAYMENT = 'waiting_payment';

    public const STATUS_PAID = 'paid';

    public const STATUS_PAY_AT_COUNTER = 'pay_at_counter';

    public const STATUS_PROCESSING = 'processing';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_EXPIRED = 'expired';

    public const PAYMENT_QRIS = 'qris';

    public const PAYMENT_PAY_AT_COUNTER = 'pay_at_counter';

    protected $fillable = [
        'tenant_id',
        'outlet_id',
        'table_id',
        'transaction_id',
        'order_number',
        'customer_name',
        'customer_phone',
        'notes',
        'status',
        'payment_method',
        'xendit_invoice_id',
        'xendit_invoice_url',
        'xendit_response',
        'xendit_expired_at',
        'subtotal',
        'tax_amount',
        'service_charge_amount',
        'grand_total',
        'tax_mode',
        'tax_percentage',
        'service_charge_percentage',
    ];

    protected function casts(): array
    {
        return [
            'xendit_response' => 'array',
            'xendit_expired_at' => 'datetime',
            'subtotal' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'service_charge_amount' => 'decimal:2',
            'grand_total' => 'decimal:2',
            'tax_percentage' => 'decimal:2',
            'service_charge_percentage' => 'decimal:2',
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

    public function table(): BelongsTo
    {
        return $this->belongsTo(Table::class);
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(QrOrderItem::class);
    }

    public static function generateOrderNumber(string $outletId): string
    {
        $outlet = Outlet::find($outletId);
        $outletCode = $outlet ? strtoupper(substr($outlet->code ?? $outlet->name, 0, 5)) : 'QR';
        $date = now()->format('Ymd');

        $todayCount = static::where('outlet_id', $outletId)
            ->whereDate('created_at', now()->toDateString())
            ->count();

        $sequence = str_pad($todayCount + 1, 4, '0', STR_PAD_LEFT);

        return "QR-{$outletCode}-{$date}-{$sequence}";
    }

    public function markAsWaitingPayment(): void
    {
        $this->update(['status' => self::STATUS_WAITING_PAYMENT]);
    }

    public function markAsPaid(): void
    {
        $this->update(['status' => self::STATUS_PAID]);
    }

    public function markAsPayAtCounter(): void
    {
        $this->update([
            'status' => self::STATUS_PAY_AT_COUNTER,
            'payment_method' => self::PAYMENT_PAY_AT_COUNTER,
        ]);
    }

    public function markAsProcessing(): void
    {
        $this->update(['status' => self::STATUS_PROCESSING]);
    }

    public function markAsCompleted(): void
    {
        $this->update(['status' => self::STATUS_COMPLETED]);
    }

    public function markAsCancelled(): void
    {
        $this->update(['status' => self::STATUS_CANCELLED]);
    }

    public function markAsExpired(): void
    {
        $this->update(['status' => self::STATUS_EXPIRED]);
    }

    public function isActive(): bool
    {
        return in_array($this->status, [
            self::STATUS_PENDING,
            self::STATUS_WAITING_PAYMENT,
            self::STATUS_PAID,
            self::STATUS_PAY_AT_COUNTER,
            self::STATUS_PROCESSING,
        ]);
    }

    public function canCancel(): bool
    {
        return in_array($this->status, [
            self::STATUS_PENDING,
            self::STATUS_WAITING_PAYMENT,
            self::STATUS_PAY_AT_COUNTER,
        ]);
    }

    public function scopeForOutlet($query, string $outletId)
    {
        return $query->where('outlet_id', $outletId);
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', [
            self::STATUS_PENDING,
            self::STATUS_WAITING_PAYMENT,
            self::STATUS_PAID,
            self::STATUS_PAY_AT_COUNTER,
            self::STATUS_PROCESSING,
        ]);
    }
}
