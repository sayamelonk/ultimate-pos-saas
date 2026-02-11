<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuthorizationLog extends Model
{
    use HasUuid;

    public const ACTION_VOID = 'void';

    public const ACTION_REFUND = 'refund';

    public const ACTION_DISCOUNT = 'discount';

    public const ACTION_PRICE_OVERRIDE = 'price_override';

    public const ACTION_NO_SALE = 'no_sale';

    public const ACTION_REPRINT = 'reprint';

    public const ACTION_CANCEL_ORDER = 'cancel_order';

    public const ACTION_OTHER = 'other';

    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_DENIED = 'denied';

    public const STATUS_EXPIRED = 'expired';

    protected $fillable = [
        'tenant_id',
        'outlet_id',
        'requested_by',
        'authorized_by',
        'action_type',
        'status',
        'reference_type',
        'reference_id',
        'reference_number',
        'amount',
        'reason',
        'notes',
        'metadata',
        'requested_at',
        'responded_at',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'metadata' => 'array',
            'requested_at' => 'datetime',
            'responded_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    // ==================== Relationships ====================

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function outlet(): BelongsTo
    {
        return $this->belongsTo(Outlet::class);
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function authorizedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'authorized_by');
    }

    // ==================== Scopes ====================

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    public function scopeDenied($query)
    {
        return $query->where('status', self::STATUS_DENIED);
    }

    public function scopeForAction($query, string $action)
    {
        return $query->where('action_type', $action);
    }

    // ==================== Helpers ====================

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function isDenied(): bool
    {
        return $this->status === self::STATUS_DENIED;
    }

    public function isExpired(): bool
    {
        return $this->status === self::STATUS_EXPIRED ||
            ($this->expires_at && $this->expires_at->isPast());
    }

    public function approve(string $authorizedBy, ?string $notes = null): bool
    {
        if (! $this->isPending() || $this->isExpired()) {
            return false;
        }

        $this->update([
            'status' => self::STATUS_APPROVED,
            'authorized_by' => $authorizedBy,
            'notes' => $notes,
            'responded_at' => now(),
        ]);

        return true;
    }

    public function deny(string $authorizedBy, ?string $notes = null): bool
    {
        if (! $this->isPending()) {
            return false;
        }

        $this->update([
            'status' => self::STATUS_DENIED,
            'authorized_by' => $authorizedBy,
            'notes' => $notes,
            'responded_at' => now(),
        ]);

        return true;
    }

    public static function getActionLabel(string $action): string
    {
        return match ($action) {
            self::ACTION_VOID => 'Void Transaction',
            self::ACTION_REFUND => 'Refund',
            self::ACTION_DISCOUNT => 'Manual Discount',
            self::ACTION_PRICE_OVERRIDE => 'Price Override',
            self::ACTION_NO_SALE => 'No Sale (Open Drawer)',
            self::ACTION_REPRINT => 'Reprint Receipt',
            self::ACTION_CANCEL_ORDER => 'Cancel Order',
            self::ACTION_OTHER => 'Other',
            default => ucfirst(str_replace('_', ' ', $action)),
        };
    }

    public static function getStatusBadgeType(string $status): string
    {
        return match ($status) {
            self::STATUS_PENDING => 'warning',
            self::STATUS_APPROVED => 'success',
            self::STATUS_DENIED => 'danger',
            self::STATUS_EXPIRED => 'secondary',
            default => 'secondary',
        };
    }
}
