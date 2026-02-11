<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashDrawerLog extends Model
{
    use HasFactory, HasUuid;

    public const TYPE_CASH_IN = 'cash_in';

    public const TYPE_CASH_OUT = 'cash_out';

    public const TYPE_SALE = 'sale';

    public const TYPE_REFUND = 'refund';

    public const TYPE_OPENING = 'opening';

    public const TYPE_CLOSING = 'closing';

    protected $fillable = [
        'tenant_id',
        'outlet_id',
        'pos_session_id',
        'user_id',
        'transaction_id',
        'type',
        'amount',
        'balance_before',
        'balance_after',
        'reference',
        'reason',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'balance_before' => 'decimal:2',
            'balance_after' => 'decimal:2',
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

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function isInflow(): bool
    {
        return in_array($this->type, [self::TYPE_CASH_IN, self::TYPE_SALE, self::TYPE_OPENING]);
    }

    public function isOutflow(): bool
    {
        return in_array($this->type, [self::TYPE_CASH_OUT, self::TYPE_REFUND]);
    }

    public static function getTypes(): array
    {
        return [
            self::TYPE_CASH_IN => 'Cash In',
            self::TYPE_CASH_OUT => 'Cash Out',
            self::TYPE_SALE => 'Sale',
            self::TYPE_REFUND => 'Refund',
            self::TYPE_OPENING => 'Opening Balance',
            self::TYPE_CLOSING => 'Closing Balance',
        ];
    }

    public static function getTypeLabel(string $type): string
    {
        return self::getTypes()[$type] ?? $type;
    }
}
