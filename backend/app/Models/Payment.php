<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Payment extends Model {
    protected $fillable = ['reference_no', 'party_id', 'user_id', 'visit_id', 'amount', 'payment_mode', 'status', 'collected_at'];
    protected $casts = ['collected_at' => 'datetime'];
    public function party() { return $this->belongsTo(Customer::class, 'party_id'); }
    public function user() { return $this->belongsTo(User::class); }
    public function visit() { return $this->belongsTo(Visit::class); }
}