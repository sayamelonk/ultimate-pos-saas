<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BatchSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'expiry_warning_days',
        'expiry_critical_days',
        'auto_mark_expired',
        'block_expired_sales',
        'enable_fefo',
        'notify_expiry_warning',
        'notify_expiry_critical',
        'daily_expiry_report',
        'auto_generate_batch',
        'batch_prefix',
        'batch_format',
    ];

    protected function casts(): array
    {
        return [
            'expiry_warning_days' => 'integer',
            'expiry_critical_days' => 'integer',
            'auto_mark_expired' => 'boolean',
            'block_expired_sales' => 'boolean',
            'enable_fefo' => 'boolean',
            'notify_expiry_warning' => 'boolean',
            'notify_expiry_critical' => 'boolean',
            'daily_expiry_report' => 'boolean',
            'auto_generate_batch' => 'boolean',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public static function getForTenant(string $tenantId): self
    {
        return self::firstOrCreate(
            ['tenant_id' => $tenantId],
            [
                'expiry_warning_days' => 30,
                'expiry_critical_days' => 7,
                'auto_mark_expired' => true,
                'block_expired_sales' => true,
                'enable_fefo' => true,
                'notify_expiry_warning' => true,
                'notify_expiry_critical' => true,
                'daily_expiry_report' => false,
                'auto_generate_batch' => true,
                'batch_prefix' => 'BTH',
                'batch_format' => 'PREFIX-YYYYMMDD-SEQ',
            ]
        );
    }
}
