<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TourType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'requires_vehicle',
        'is_active',
    ];

    protected $casts = [
        'requires_vehicle' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function dayStarts()
    {
        return $this->hasMany(DayStart::class);
    }
}
