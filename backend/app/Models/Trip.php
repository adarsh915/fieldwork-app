<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Trip extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'day_start_id',
        'vehicle_type_id',
        'vehicle_no',
        'start_location',
        'end_location',
        'opening_km',
        'closing_km',
        'distance_km',
        'applied_rate_per_km',
        'applied_rate_source',
        'total_amount',
        'date',
        'status',
        'remarks',
    ];

    protected $casts = [
        'date'                => 'date:Y-m-d',
        'opening_km'          => 'decimal:2',
        'closing_km'          => 'decimal:2',
        'distance_km'         => 'decimal:2',
        'applied_rate_per_km' => 'decimal:2',
        'total_amount'        => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function vehicleType()
    {
        return $this->belongsTo(VehicleType::class);
    }

    public function dayStart()
    {
        return $this->belongsTo(DayStart::class);
    }
}
