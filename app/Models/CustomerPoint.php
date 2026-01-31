<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerPoint extends Model
{
    use HasFactory, HasUuid;

    public const TYPE_EARNED = 'earned';

    public const TYPE_REDEEMED = 'redeemed';

    public const TYPE_EXPIRED = 'expired';

    public const TYPE_ADJUSTMENT = 'adjustment';

    protected $fillable = [
        'customer_id',
        'transaction_id',
        'type',
        'points',
        'balance_before',
        'balance_after',
        'description',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'points' => 'decimal:2',
            'balance_before' => 'decimal:2',
            'balance_after' => 'decimal:2',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public static function getTypes(): array
    {
        return [
            self::TYPE_EARNED => 'Earned',
            self::TYPE_REDEEMED => 'Redeemed',
            self::TYPE_EXPIRED => 'Expired',
            self::TYPE_ADJUSTMENT => 'Adjustment',
        ];
    }
}
