<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Visit extends Model
{
    protected $fillable = ['user_id', 'party_id', 'day_start_id', 'purpose', 'check_in_time', 'check_in_lat', 'check_in_lng', 'check_out_time', 'check_out_lat', 'check_out_lng', 'check_in_photo', 'notes', 'status'];

    protected $casts = [
        'check_in_time' => 'datetime',
        'check_out_time' => 'datetime',
        'check_in_lat' => 'decimal:7',
        'check_in_lng' => 'decimal:7',
        'check_out_lat' => 'decimal:7',
        'check_out_lng' => 'decimal:7',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function dayStart()
    {
        return $this->belongsTo(DayStart::class);
    }

    public function party()
    {
        return $this->belongsTo(Customer::class, 'party_id');
    }
}
