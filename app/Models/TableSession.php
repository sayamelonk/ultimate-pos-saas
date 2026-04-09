<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TableSession extends Model
{
    use HasFactory, HasUuid;

    public const STATUS_ACTIVE = 'active';

    public const STATUS_CLOSED = 'closed';

    protected $fillable = [
        'tenant_id',
        'table_id',
        'opened_by',
        'closed_by',
        'opened_at',
        'closed_at',
        'guest_count',
        'notes',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'opened_at' => 'datetime',
            'closed_at' => 'datetime',
            'guest_count' => 'integer',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function table(): BelongsTo
    {
        return $this->belongsTo(Table::class);
    }

    public function openedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'opened_by');
    }

    public function closedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function isClosed(): bool
    {
        return $this->status === self::STATUS_CLOSED;
    }

    public function getDurationMinutesAttribute(): int
    {
        $endTime = $this->closed_at ?? now();

        return $this->opened_at->diffInMinutes($endTime);
    }

    public function getDurationFormattedAttribute(): string
    {
        $minutes = $this->duration_minutes;
        $hours = floor($minutes / 60);
        $mins = $minutes % 60;

        if ($hours > 0) {
            return sprintf('%d jam %d menit', $hours, $mins);
        }

        return sprintf('%d menit', $mins);
    }

    public function getTotalAmountAttribute(): float
    {
        return $this->transactions()->sum('grand_total');
    }

    public function close(?string $closedBy = null): void
    {
        $this->update([
            'status' => self::STATUS_CLOSED,
            'closed_at' => now(),
            'closed_by' => $closedBy,
        ]);

        $table = $this->table()->first();
        if ($table) {
            $table->markAsDirty();
        }
    }

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeClosed($query)
    {
        return $query->where('status', self::STATUS_CLOSED);
    }

    public function scopeForTable($query, string $tableId)
    {
        return $query->where('table_id', $tableId);
    }

    public static function openTable(Table $table, int $guestCount = 1, ?string $openedBy = null): self
    {
        $session = self::create([
            'tenant_id' => $table->tenant_id,
            'table_id' => $table->id,
            'opened_by' => $openedBy,
            'opened_at' => now(),
            'guest_count' => $guestCount,
            'status' => self::STATUS_ACTIVE,
        ]);

        $table->markAsOccupied();

        return $session;
    }
}
