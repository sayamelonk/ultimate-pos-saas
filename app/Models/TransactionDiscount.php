<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransactionDiscount extends Model
{
    use HasFactory, HasUuid;

    public const TYPE_PERCENTAGE = 'percentage';

    public const TYPE_FIXED_AMOUNT = 'fixed_amount';

    protected $fillable = [
        'transaction_id',
        'transaction_item_id',
        'discount_id',
        'discount_name',
        'type',
        'value',
        'amount',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'decimal:2',
            'amount' => 'decimal:2',
        ];
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function transactionItem(): BelongsTo
    {
        return $this->belongsTo(TransactionItem::class);
    }

    public function discount(): BelongsTo
    {
        return $this->belongsTo(Discount::class);
    }

    public function isOrderLevel(): bool
    {
        return $this->transaction_item_id === null;
    }

    public function isItemLevel(): bool
    {
        return $this->transaction_item_id !== null;
    }

    public static function getTypes(): array
    {
        return [
            self::TYPE_PERCENTAGE => 'Percentage',
            self::TYPE_FIXED_AMOUNT => 'Fixed Amount',
        ];
    }
}
