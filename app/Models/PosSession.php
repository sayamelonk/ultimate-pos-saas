<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PosSession extends Model
{
    use HasFactory, HasUuid;

    public const STATUS_OPEN = 'open';

    public const STATUS_CLOSED = 'closed';

    protected $fillable = [
        'outlet_id',
        'user_id',
        'session_number',
        'opening_cash',
        'closing_cash',
        'expected_cash',
        'cash_difference',
        'opening_notes',
        'closing_notes',
        'opened_at',
        'closed_at',
        'closed_by',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'opening_cash' => 'decimal:2',
            'closing_cash' => 'decimal:2',
            'expected_cash' => 'decimal:2',
            'cash_difference' => 'decimal:2',
            'opened_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    public function outlet(): BelongsTo
    {
        return $this->belongsTo(Outlet::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function closedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function isOpen(): bool
    {
        return $this->status === self::STATUS_OPEN;
    }

    public function getCashSales(): float
    {
        return $this->transactions()
            ->where('status', Transaction::STATUS_COMPLETED)
            ->whereHas('payments', function ($query) {
                $query->whereHas('paymentMethod', function ($q) {
                    $q->where('type', PaymentMethod::TYPE_CASH);
                });
            })
            ->with(['payments' => function ($query) {
                $query->whereHas('paymentMethod', function ($q) {
                    $q->where('type', PaymentMethod::TYPE_CASH);
                });
            }])
            ->get()
            ->sum(fn ($transaction) => $transaction->payments->sum('amount'));
    }

    public function getTotalSales(): float
    {
        return $this->transactions()
            ->where('status', Transaction::STATUS_COMPLETED)
            ->sum('grand_total');
    }

    public function getTransactionCount(): int
    {
        return $this->transactions()
            ->where('status', Transaction::STATUS_COMPLETED)
            ->count();
    }

    public function getExpectedCash(): float
    {
        return $this->opening_cash + $this->getCashSales() - $this->getCashRefunds();
    }

    public function getCashRefunds(): float
    {
        return $this->transactions()
            ->where('type', Transaction::TYPE_REFUND)
            ->where('status', Transaction::STATUS_COMPLETED)
            ->whereHas('payments', function ($query) {
                $query->whereHas('paymentMethod', function ($q) {
                    $q->where('type', PaymentMethod::TYPE_CASH);
                });
            })
            ->with(['payments' => function ($query) {
                $query->whereHas('paymentMethod', function ($q) {
                    $q->where('type', PaymentMethod::TYPE_CASH);
                });
            }])
            ->get()
            ->sum(fn ($transaction) => $transaction->payments->sum('amount'));
    }

    public function close(float $closingCash, string $closedBy, ?string $notes = null): void
    {
        $expectedCash = $this->getExpectedCash();

        $this->update([
            'closing_cash' => $closingCash,
            'expected_cash' => $expectedCash,
            'cash_difference' => $closingCash - $expectedCash,
            'closing_notes' => $notes,
            'closed_at' => now(),
            'closed_by' => $closedBy,
            'status' => self::STATUS_CLOSED,
        ]);
    }

    public static function getStatuses(): array
    {
        return [
            self::STATUS_OPEN => 'Open',
            self::STATUS_CLOSED => 'Closed',
        ];
    }
}
