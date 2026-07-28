<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class GpsTrackingLog extends Model {
    protected $fillable = ['user_id', 'latitude', 'longitude', 'recorded_at', 'battery_level', 'accuracy'];
    protected $casts = ['recorded_at' => 'datetime'];
    public function user() { return $this->belongsTo(User::class); }
}