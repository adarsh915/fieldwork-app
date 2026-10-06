<?php

namespace App\Models;

use App\Traits\HasRoleBasedScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DayStart extends Model
{
    use HasFactory, HasRoleBasedScope;

    protected $fillable = ['user_id', 'date', 'tour_purpose_id', 'tour_type_id', 'vehicle_type_id', 'vehicle_no', 'opening_km', 'opening_km_photo', 'closing_km', 'closing_km_photo', 'gps_calculated_km', 'flagged_reason', 'start_latitude', 'start_longitude', 'start_time', 'end_time', 'status', 'approved_by', 'remarks', 'party_id', 'visit_place'];

    protected $casts = [
        'date' => 'date',
        'opening_km' => 'decimal:2',
        'start_lat' => 'decimal:7',
        'start_lng' => 'decimal:7',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function tourPurpose()
    {
        return $this->belongsTo(TourPurpose::class);
    }

    public function tourType()
    {
        return $this->belongsTo(TourType::class);
    }

    public function vehicleType()
    {
        return $this->belongsTo(VehicleType::class);
    }

    public function party()
    {
        return $this->belongsTo(Customer::class, 'party_id');
    }

    public function places() { return $this->hasMany(DayStartPlace::class); }
    public function parties() { return $this->hasMany(DayStartParty::class); }

    public function expenses()
    {
        return $this->hasMany(ExpenseBill::class);
    }

    public function visits()
    {
        return $this->hasMany(Visit::class);
    }

}