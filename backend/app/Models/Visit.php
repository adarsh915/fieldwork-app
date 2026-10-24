<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Visit extends Model
{
    protected $fillable = ['user_id', 'party_id', 'day_start_id', 'purpose', 'check_in_time', 'check_in_lat', 'check_in_lng', 'check_out_time', 'check_out_lat', 'check_out_lng', 'check_in_photo', 'notes', 'status'];

    protected $casts = [
        'check_in_time' => 'datetime',
        'check_out_time' => 'datetime',
        'check_in_lat' => 'decimal:7',
        'check_in_lng' => 'decimal:7',
        'check_out_lat' => 'decimal:7',
        'check_out_lng' => 'decimal:7',
    ];

    protected $appends = ['check_in_at', 'check_out_at', 'remarks', 'check_in_photo_url'];

    public function getCheckInAtAttribute()
    {
        return $this->check_in_time?->toISOString() ?? ($this->attributes['check_in_time'] ?? null);
    }

    public function getCheckOutAtAttribute()
    {
        return $this->check_out_time?->toISOString() ?? ($this->attributes['check_out_time'] ?? null);
    }

    public function getRemarksAttribute()
    {
        return $this->notes;
    }

    public function getCheckInPhotoUrlAttribute(): ?string
    {
        if (!$this->check_in_photo) {
            return null;
        }

        if (str_starts_with($this->check_in_photo, 'http://') || str_starts_with($this->check_in_photo, 'https://')) {
            return $this->check_in_photo;
        }

        $cleanPath = ltrim($this->check_in_photo, '/');
        if (str_starts_with($cleanPath, 'storage/')) {
            return asset($cleanPath);
        }

        return asset('storage/' . $cleanPath);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function dayStart()
    {
        return $this->belongsTo(DayStart::class);
    }

    public function party()
    {
        return $this->belongsTo(Customer::class, 'party_id');
    }
}
