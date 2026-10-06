<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VehicleType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'rate_per_km',
        'requires_vehicle_no',
        'is_active',
    ];

    protected $casts = [
        'rate_per_km' => 'decimal:2',
        'requires_vehicle_no' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function dayStarts()
    {
        return $this->hasMany(DayStart::class);
    }
}
