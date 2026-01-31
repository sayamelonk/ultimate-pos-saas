<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PaymentMethod extends Model
{
    use HasFactory, HasUuid;

    public const TYPE_CASH = 'cash';

    public const TYPE_CARD = 'card';

    public const TYPE_DIGITAL_WALLET = 'digital_wallet';

    public const TYPE_TRANSFER = 'transfer';

    public const TYPE_OTHER = 'other';

    protected $fillable = [
        'tenant_id',
        'code',
        'name',
        'type',
        'provider',
        'icon',
        'charge_percentage',
        'charge_fixed',
        'requires_reference',
        'opens_cash_drawer',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'charge_percentage' => 'decimal:2',
            'charge_fixed' => 'decimal:2',
            'requires_reference' => 'boolean',
            'opens_cash_drawer' => 'boolean',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function transactionPayments(): HasMany
    {
        return $this->hasMany(TransactionPayment::class);
    }

    public function calculateCharge(float $amount): float
    {
        $percentageCharge = ($amount * $this->charge_percentage) / 100;

        return $percentageCharge + $this->charge_fixed;
    }

    public static function getTypes(): array
    {
        return [
            self::TYPE_CASH => 'Cash',
            self::TYPE_CARD => 'Card',
            self::TYPE_DIGITAL_WALLET => 'Digital Wallet',
            self::TYPE_TRANSFER => 'Transfer',
            self::TYPE_OTHER => 'Other',
        ];
    }
}
