<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Table extends Model
{
    use HasFactory, HasUuid;

    public const STATUS_AVAILABLE = 'available';

    public const STATUS_OCCUPIED = 'occupied';

    public const STATUS_RESERVED = 'reserved';

    public const STATUS_DIRTY = 'dirty';

    public const SHAPE_RECTANGLE = 'rectangle';

    public const SHAPE_CIRCLE = 'circle';

    public const SHAPE_SQUARE = 'square';

    protected $fillable = [
        'tenant_id',
        'outlet_id',
        'floor_id',
        'number',
        'name',
        'capacity',
        'position_x',
        'position_y',
        'width',
        'height',
        'shape',
        'status',
        'is_active',
        'qr_token',
        'qr_generated_at',
    ];

    protected function casts(): array
    {
        return [
            'capacity' => 'integer',
            'position_x' => 'integer',
            'position_y' => 'integer',
            'width' => 'integer',
            'height' => 'integer',
            'is_active' => 'boolean',
            'qr_generated_at' => 'datetime',
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

    public function floor(): BelongsTo
    {
        return $this->belongsTo(Floor::class);
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(TableSession::class);
    }

    public function currentSession(): HasOne
    {
        return $this->hasOne(TableSession::class)
            ->where('status', 'active')
            ->latest('opened_at');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function qrOrders(): HasMany
    {
        return $this->hasMany(QrOrder::class);
    }

    public function generateQrToken(): string
    {
        $token = bin2hex(random_bytes(32));
        $this->update([
            'qr_token' => $token,
            'qr_generated_at' => now(),
        ]);

        return $token;
    }

    public function revokeQrToken(): void
    {
        $this->update([
            'qr_token' => null,
            'qr_generated_at' => null,
        ]);
    }

    public function hasQrCode(): bool
    {
        return ! empty($this->qr_token);
    }

    public function getQrMenuUrl(): ?string
    {
        if (! $this->qr_token) {
            return null;
        }

        return url("/qr/{$this->qr_token}");
    }

    public function getDisplayNameAttribute(): string
    {
        return $this->name ?? 'Meja '.$this->number;
    }

    public function isAvailable(): bool
    {
        return $this->status === self::STATUS_AVAILABLE;
    }

    public function isOccupied(): bool
    {
        return $this->status === self::STATUS_OCCUPIED;
    }

    public function isReserved(): bool
    {
        return $this->status === self::STATUS_RESERVED;
    }

    public function hasStatusDirty(): bool
    {
        return $this->status === self::STATUS_DIRTY;
    }

    public function markAsAvailable(): void
    {
        $this->update(['status' => self::STATUS_AVAILABLE]);
    }

    public function markAsOccupied(): void
    {
        $this->update(['status' => self::STATUS_OCCUPIED]);
    }

    public function markAsReserved(): void
    {
        $this->update(['status' => self::STATUS_RESERVED]);
    }

    public function markAsDirty(): void
    {
        $this->update(['status' => self::STATUS_DIRTY]);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeAvailable($query)
    {
        return $query->where('status', self::STATUS_AVAILABLE);
    }

    public function scopeOccupied($query)
    {
        return $query->where('status', self::STATUS_OCCUPIED);
    }

    public function scopeForOutlet($query, string $outletId)
    {
        return $query->where('outlet_id', $outletId);
    }

    public function scopeForFloor($query, string $floorId)
    {
        return $query->where('floor_id', $floorId);
    }

    public static function getStatuses(): array
    {
        return [
            self::STATUS_AVAILABLE => 'Available',
            self::STATUS_OCCUPIED => 'Occupied',
            self::STATUS_RESERVED => 'Reserved',
            self::STATUS_DIRTY => 'Dirty',
        ];
    }

    public static function getShapes(): array
    {
        return [
            self::SHAPE_RECTANGLE => 'Rectangle',
            self::SHAPE_CIRCLE => 'Circle',
            self::SHAPE_SQUARE => 'Square',
        ];
    }
}
