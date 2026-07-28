<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Activity extends Model {
    protected $fillable = ['user_id', 'party_id', 'activity_type', 'description', 'date'];
    protected $casts = ['date' => 'date'];
    public function user() { return $this->belongsTo(User::class); }
    public function party() { return $this->belongsTo(Customer::class, 'party_id'); }
}