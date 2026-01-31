<?php

namespace App\Traits;

use Illuminate\Support\Str;

trait HasUuid
{
    /**
     * Boot method dari trait
     * Dipanggil otomatis saat model dibuat
     */
    protected static function bootHasUuid(): void
    {
        // Event "creating": dipanggil SEBELUM data disimpan
        static::creating(function ($model) {
            // Cek jika primary key masih kosong
            if (empty($model->{$model->getKeyName()})) {
                // Generate UUID secara otomatis
                $model->{$model->getKeyName()} = (string) Str::uuid();
            }
        });
    }

    /**
     * Override method getIncrementing()
     * Memberitahu Laravel bahwa ini BUKAN auto-increment
     */
    public function getIncrementing(): bool
    {
        return false;
    }

    /**
     * Override method getKeyType()
     * Memberitahu Laravel bahwa tipe primary key adalah string (bukan integer)
     */
    public function getKeyType(): string
    {
        return 'string';
    }
}
