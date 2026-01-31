<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use HasFactory, HasUuid, SoftDeletes;

    public const LEVEL_REGULAR = 'regular';

    public const LEVEL_SILVER = 'silver';

    public const LEVEL_GOLD = 'gold';

    public const LEVEL_PLATINUM = 'platinum';

    protected $fillable = [
        'tenant_id',
        'code',
        'name',
        'email',
        'phone',
        'address',
        'birth_date',
        'gender',
        'membership_level',
        'total_points',
        'total_spent',
        'total_visits',
        'joined_at',
        'membership_expires_at',
        'notes',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'joined_at' => 'date',
            'membership_expires_at' => 'date',
            'total_points' => 'decimal:2',
            'total_spent' => 'decimal:2',
            'total_visits' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function points(): HasMany
    {
        return $this->hasMany(CustomerPoint::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function addPoints(float $points, ?string $transactionId, string $userId, ?string $description = null): CustomerPoint
    {
        $balanceBefore = $this->total_points;
        $balanceAfter = $balanceBefore + $points;

        $this->update(['total_points' => $balanceAfter]);

        return $this->points()->create([
            'transaction_id' => $transactionId,
            'type' => 'earned',
            'points' => $points,
            'balance_before' => $balanceBefore,
            'balance_after' => $balanceAfter,
            'description' => $description,
            'created_by' => $userId,
        ]);
    }

    public function redeemPoints(float $points, ?string $transactionId, string $userId, ?string $description = null): CustomerPoint
    {
        $balanceBefore = $this->total_points;
        $balanceAfter = $balanceBefore - $points;

        $this->update(['total_points' => $balanceAfter]);

        return $this->points()->create([
            'transaction_id' => $transactionId,
            'type' => 'redeemed',
            'points' => -$points,
            'balance_before' => $balanceBefore,
            'balance_after' => $balanceAfter,
            'description' => $description,
            'created_by' => $userId,
        ]);
    }

    public function adjustPoints(float $points, string $userId, ?string $description = null): CustomerPoint
    {
        $balanceBefore = $this->total_points;
        $balanceAfter = $balanceBefore + $points;

        $this->update(['total_points' => $balanceAfter]);

        return $this->points()->create([
            'type' => 'adjustment',
            'points' => $points,
            'balance_before' => $balanceBefore,
            'balance_after' => $balanceAfter,
            'description' => $description,
            'created_by' => $userId,
        ]);
    }

    public function getPointsValue(): float
    {
        return $this->total_points * 100;
    }

    public function isMember(): bool
    {
        return $this->membership_level !== self::LEVEL_REGULAR;
    }

    public function isMembershipActive(): bool
    {
        if (! $this->membership_expires_at) {
            return true;
        }

        return $this->membership_expires_at->isFuture();
    }

    public static function getMembershipLevels(): array
    {
        return [
            self::LEVEL_REGULAR => 'Regular',
            self::LEVEL_SILVER => 'Silver',
            self::LEVEL_GOLD => 'Gold',
            self::LEVEL_PLATINUM => 'Platinum',
        ];
    }
}
