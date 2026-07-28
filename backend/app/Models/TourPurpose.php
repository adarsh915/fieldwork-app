<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TourPurpose extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'requires_party',
        'is_active',
    ];

    protected $casts = [
        'requires_party' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function dayStarts()
    {
        return $this->hasMany(DayStart::class);
    }
}
