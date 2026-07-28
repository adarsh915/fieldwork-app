<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    protected $fillable = [
        'event',
        'auditable_type',
        'auditable_id',
        'causer_id',
        'old_values',
        'new_values',
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
}
