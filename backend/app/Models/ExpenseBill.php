<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class ExpenseBill extends Model {
    protected $fillable = ['user_id', 'day_start_id', 'category_id', 'date', 'amount', 'bill_photo', 'status', 'approved_by', 'is_system_generated'];
    protected $casts = ['date' => 'date', 'is_system_generated' => 'boolean'];
    
    public function user() { return $this->belongsTo(User::class); }
    public function approvedBy() { return $this->belongsTo(User::class, 'approved_by'); }
    public function dayStart() { return $this->belongsTo(DayStart::class); }
    public function category() { return $this->belongsTo(ExpenseCategory::class); }
}