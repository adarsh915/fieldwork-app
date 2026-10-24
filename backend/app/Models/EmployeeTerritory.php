<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeTerritory extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'district_id',
        'area_id',
    ];

    public function employee()
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    public function district()
    {
        return $this->belongsTo(District::class, 'district_id');
    }

    public function area()
    {
        return $this->belongsTo(Area::class, 'area_id');
    }
}
