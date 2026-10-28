<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\Auditable;

class ExpenseBill extends Model
{
    use Auditable;
    protected $fillable = [
        'user_id',
        'day_start_id',
        'category_id',
        'date',
        'amount',
        'bill_photo',
        'status',
        'temp_approved_by',
        'temp_approved_at',
        'temp_approved_note',
        'approved_by',
        'approved_at',
        'final_note',
        'rejected_by',
        'rejected_at',
        'rejection_reason',
        'is_system_generated',
        'is_after_day_end',
    ];

    protected $casts = [
        'date' => 'date',
        'temp_approved_at' => 'datetime',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'is_system_generated' => 'boolean',
        'is_after_day_end' => 'boolean',
    ];

    protected $appends = [
        'bill_photo_url',
    ];

    public function getBillPhotoUrlAttribute(): ?string
    {
        if ($this->bill_photo) {
            return asset('storage/' . $this->bill_photo);
        }
        if ($this->is_system_generated && $this->relationLoaded('dayStart') && $this->dayStart) {
            return $this->dayStart->closing_km_photo_url ?: $this->dayStart->opening_km_photo_url;
        }
        return null;
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function tempApprovedBy()
    {
        return $this->belongsTo(User::class, 'temp_approved_by');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function rejectedBy()
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    public function dayStart()
    {
        return $this->belongsTo(DayStart::class);
    }

    public function category()
    {
        return $this->belongsTo(ExpenseCategory::class);
    }

    public function items()
    {
        return $this->hasMany(ExpenseBillItem::class, 'expense_bill_id');
    }
}
