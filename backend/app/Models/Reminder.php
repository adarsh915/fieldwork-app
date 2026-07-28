<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Reminder extends Model {
    protected $fillable = ['user_id', 'party_id', 'title', 'remind_at', 'status'];
    protected $casts = ['remind_at' => 'datetime'];
    public function user() { return $this->belongsTo(User::class); }
    public function party() { return $this->belongsTo(Customer::class, 'party_id'); }
}