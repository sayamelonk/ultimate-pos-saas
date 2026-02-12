<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Hash;

class UserPin extends Model
{
    protected $fillable = [
        'user_id',
        'pin_hash',
        'is_active',
        'last_used_at',
    ];

    protected $hidden = [
        'pin_hash',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'last_used_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function setPin(string $pin): void
    {
        $this->pin_hash = Hash::make($pin);
        $this->save();
    }

    public function verifyPin(string $pin): bool
    {
        if (! $this->is_active) {
            return false;
        }

        $verified = Hash::check($pin, $this->pin_hash);

        if ($verified) {
            $this->update(['last_used_at' => now()]);
        }

        return $verified;
    }

    public static function createForUser(string $userId, string $pin): self
    {
        return self::create([
            'user_id' => $userId,
            'pin_hash' => Hash::make($pin),
            'is_active' => true,
        ]);
    }
}
