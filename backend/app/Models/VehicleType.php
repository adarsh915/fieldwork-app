<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VehicleType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'requires_vehicle_no',
        'is_active',
    ];

    protected $casts = [
        'requires_vehicle_no' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function dayStarts()
    {
        return $this->hasMany(DayStart::class);
    }
}
