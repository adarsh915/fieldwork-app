<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'event',
        'auditable_type',
        'auditable_id',
        'causer_id',
        'old_values',
        'new_values',
        'reason',
        'ip_address',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
    ];

    public function causer()
    {
        return $this->belongsTo(User::class, 'causer_id');
    }

    /**
     * Static helper to log an audit event.
     */
    public static function logEvent(
        string $event,
        ?Model $auditable = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?string $reason = null,
        ?int $causerUserId = null
    ): self {
        return self::create([
            'event'          => $event,
            'auditable_type' => $auditable ? get_class($auditable) : 'System',
            'auditable_id'   => $auditable?->getKey() ?? 0,
            'causer_id'      => $causerUserId ?? auth()->id(),
            'old_values'     => $oldValues,
            'new_values'     => $newValues,
            'reason'         => $reason,
            'ip_address'     => request()->ip(),
        ]);
    }
}
