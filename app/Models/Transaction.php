<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Transaction extends Model
{
    use HasFactory, HasUuid;

    public const TYPE_SALE = 'sale';

    public const TYPE_REFUND = 'refund';

    public const TYPE_VOID = 'void';

    public const STATUS_PENDING = 'pending';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_VOIDED = 'voided';

    protected $fillable = [
        'tenant_id',
        'outlet_id',
        'pos_session_id',
        'customer_id',
        'user_id',
        'transaction_number',
        'type',
        'original_transaction_id',
        'subtotal',
        'discount_amount',
        'tax_amount',
        'service_charge_amount',
        'rounding',
        'grand_total',
        'payment_amount',
        'change_amount',
        'tax_percentage',
        'service_charge_percentage',
        'points_earned',
        'points_redeemed',
        'notes',
        'status',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'service_charge_amount' => 'decimal:2',
            'rounding' => 'decimal:2',
            'grand_total' => 'decimal:2',
            'payment_amount' => 'decimal:2',
            'change_amount' => 'decimal:2',
            'tax_percentage' => 'decimal:2',
            'service_charge_percentage' => 'decimal:2',
            'points_earned' => 'decimal:2',
            'points_redeemed' => 'decimal:2',
            'completed_at' => 'datetime',
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

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function originalTransaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class, 'original_transaction_id');
    }

    public function refundTransactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'original_transaction_id')
            ->where('type', self::TYPE_REFUND);
    }

    public function items(): HasMany
    {
        return $this->hasMany(TransactionItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(TransactionPayment::class);
    }

    public function discounts(): HasMany
    {
        return $this->hasMany(TransactionDiscount::class);
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function isVoided(): bool
    {
        return $this->status === self::STATUS_VOIDED;
    }

    public function canVoid(): bool
    {
        return $this->status === self::STATUS_COMPLETED && $this->type === self::TYPE_SALE;
    }

    public function canRefund(): bool
    {
        return $this->status === self::STATUS_COMPLETED && $this->type === self::TYPE_SALE;
    }

    public function getProfit(): float
    {
        $totalCost = $this->items->sum(fn ($item) => $item->cost_price * $item->quantity);

        return $this->subtotal - $this->discount_amount - $totalCost;
    }

    public function getRefundedAmount(): float
    {
        return $this->refundTransactions()
            ->where('status', self::STATUS_COMPLETED)
            ->sum('grand_total');
    }

    public function getRefundableAmount(): float
    {
        return $this->grand_total - $this->getRefundedAmount();
    }

    public function complete(): void
    {
        $this->update([
            'status' => self::STATUS_COMPLETED,
            'completed_at' => now(),
        ]);
    }

    public function void(): void
    {
        $this->update([
            'status' => self::STATUS_VOIDED,
        ]);
    }

    public static function getTypes(): array
    {
        return [
            self::TYPE_SALE => 'Sale',
            self::TYPE_REFUND => 'Refund',
            self::TYPE_VOID => 'Void',
        ];
    }

    public static function getStatuses(): array
    {
        return [
            self::STATUS_PENDING => 'Pending',
            self::STATUS_COMPLETED => 'Completed',
            self::STATUS_VOIDED => 'Voided',
        ];
    }
}
