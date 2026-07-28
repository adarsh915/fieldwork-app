<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class ExpenseBill extends Model {
    protected $fillable = ['user_id', 'category_id', 'date', 'amount', 'bill_photo', 'status', 'approved_by'];
    protected $casts = ['date' => 'date'];
    public function user() { return $this->belongsTo(User::class); }
    public function approvedBy() { return $this->belongsTo(User::class, 'approved_by'); }
}