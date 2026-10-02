<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttendanceAdjustment extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'date',
        'original_status',
        'new_status',
        'original_start_time',
        'new_start_time',
        'original_end_time',
        'new_end_time',
        'original_km',
        'new_km',
        'reason',
        'adjusted_by',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function adjustedBy()
    {
        return $this->belongsTo(User::class, 'adjusted_by');
    }
}
