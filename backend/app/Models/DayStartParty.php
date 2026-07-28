<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class DayStartParty extends Model {
    protected $fillable = ['day_start_id', 'party_id', 'visit_status'];
    public function dayStart() { return $this->belongsTo(DayStart::class); }
    public function party() { return $this->belongsTo(Customer::class, 'party_id'); }
}