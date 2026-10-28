<?php

namespace App\Models;

use App\Traits\HasRoleBasedScope;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DayStart extends Model
{
    use HasFactory, HasRoleBasedScope, Auditable;

    protected $fillable = [
        'user_id',
        'date',
        'tour_purpose_id',
        'tour_type_id',
        'vehicle_type_id',
        'vehicle_no',
        'opening_km',
        'opening_km_photo',
        'closing_km',
        'closing_km_photo',
        'applied_rate_per_km',
        'applied_rate_source',
        'total_amount',
        'gps_calculated_km',
        'flagged_reason',
        'start_latitude',
        'start_longitude',
        'start_time',
        'end_time',
        'status',
        'approved_by',
        'remarks',
        'end_remarks',
        'party_id',
        'district_id',
        'area_id',
        'area_name_custom',
        'visit_place',
    ];

    protected $casts = [
        'date'                => 'date',
        'opening_km'          => 'decimal:2',
        'closing_km'          => 'decimal:2',
        'applied_rate_per_km' => 'decimal:2',
        'total_amount'        => 'decimal:2',
        'gps_calculated_km'   => 'decimal:2',
        'start_lat'           => 'decimal:7',
        'start_lng'           => 'decimal:7',
    ];

    protected $appends = [
        'opening_km_photo_url',
        'closing_km_photo_url',
    ];

    public function getOpeningKmPhotoUrlAttribute(): ?string
    {
        return $this->opening_km_photo ? asset('storage/' . $this->opening_km_photo) : null;
    }

    public function getClosingKmPhotoUrlAttribute(): ?string
    {
        return $this->closing_km_photo ? asset('storage/' . $this->closing_km_photo) : null;
    }

    public function trips()
    {
        return $this->hasMany(Trip::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function district()
    {
        return $this->belongsTo(District::class);
    }

    public function area()
    {
        return $this->belongsTo(Area::class);
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

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}