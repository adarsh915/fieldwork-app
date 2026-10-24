<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\Auditable;

class Area extends Model
{
    use HasFactory, Auditable;

    protected $fillable = [
        'district_id',
        'name',
        'pincode',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function district()
    {
        return $this->belongsTo(District::class);
    }

    public function customers()
    {
        return $this->hasMany(Customer::class);
    }

    public function dayStarts()
    {
        return $this->hasMany(DayStart::class);
    }
}
