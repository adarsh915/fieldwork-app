<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Leave extends Model {
    protected $fillable = ['user_id', 'leave_type_id', 'from_date', 'to_date', 'reason', 'status', 'approved_by'];
    protected $casts = ['from_date' => 'date', 'to_date' => 'date'];
    public function user() { return $this->belongsTo(User::class); }
    public function approvedBy() { return $this->belongsTo(User::class, 'approved_by'); }
}