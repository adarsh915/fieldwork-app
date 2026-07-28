<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Meeting extends Model {
    protected $fillable = ['user_id', 'title', 'date', 'time', 'attendees', 'location'];
    protected $casts = ['date' => 'date'];
    public function user() { return $this->belongsTo(User::class); }
}