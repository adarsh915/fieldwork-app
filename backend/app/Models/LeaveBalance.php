<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\Auditable;

class LeaveBalance extends Model
{
    use Auditable;

    protected $fillable = [
        'user_id',
        'leave_type_id',
        'year',
        'allocated',
        'used',
        'carried_forward',
    ];

    protected $casts = [
        'allocated'       => 'float',
        'used'            => 'float',
        'carried_forward' => 'float',
        'year'            => 'integer',
    ];

    protected $appends = ['available', 'pending_days', 'effective_available'];

    // ─── Relationships ────────────────────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function leaveType(): BelongsTo
    {
        return $this->belongsTo(LeaveType::class);
    }

    // ─── Computed ─────────────────────────────────────────────────────────────

    public function getAvailableAttribute(): float
    {
        return max(0.0, $this->allocated + $this->carried_forward - $this->used);
    }

    public function getPendingDaysAttribute(): float
    {
        $pendingLeaves = Leave::where('user_id', $this->user_id)
            ->where('leave_type_id', $this->leave_type_id)
            ->pending()
            ->whereYear('from_date', $this->year)
            ->get();

        return (float) $pendingLeaves->sum(fn ($l) => $l->days_count);
    }

    public function getEffectiveAvailableAttribute(): float
    {
        return max(0.0, $this->available - $this->pending_days);
    }
}
