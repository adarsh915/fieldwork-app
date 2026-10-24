<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\Auditable;

class District extends Model
{
    use HasFactory, Auditable;

    protected $fillable = [
        'name',
        'state',
        'code',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function areas()
    {
        return $this->hasMany(Area::class);
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
