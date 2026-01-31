<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransactionPayment extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'transaction_id',
        'payment_method_id',
        'amount',
        'charge_amount',
        'reference_number',
        'approval_code',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'charge_amount' => 'decimal:2',
        ];
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function getNetAmount(): float
    {
        return $this->amount - $this->charge_amount;
    }
}
