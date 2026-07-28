<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Deal extends Model {
    protected $fillable = ['party_id', 'user_id', 'deal_value', 'stage', 'expected_close_date'];
    protected $casts = ['expected_close_date' => 'date'];
    public function user() { return $this->belongsTo(User::class); }
    public function party() { return $this->belongsTo(Customer::class, 'party_id'); }
}