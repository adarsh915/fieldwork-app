<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class DayStartPlace extends Model {
    protected $fillable = ['day_start_id', 'place_name'];
    public function dayStart() { return $this->belongsTo(DayStart::class); }
}