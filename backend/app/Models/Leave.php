<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\Auditable;
use App\Services\DataScope;

class Leave extends Model
{
    use Auditable;

    protected $fillable = [
        'user_id',
        'leave_type_id',
        'from_date',
        'to_date',
        'duration',
        'reason',
        'status',
        'approved_by',
        'approved_at',
        'rejected_reason',
        'has_conflict',
        'conflict_date',
        'conflict_status',
        'resolved_by',
        'resolved_action',
        'resolved_at',
        'resolution_note',
    ];

    protected $casts = [
        'from_date'     => 'date',
        'to_date'       => 'date',
        'approved_at'   => 'datetime',
        'has_conflict'  => 'boolean',
        'conflict_date' => 'date',
        'resolved_at'   => 'datetime',
    ];

    protected $appends = [
        'days_count',
    ];

    // ─── Relationships ────────────────────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function leaveType(): BelongsTo
    {
        return $this->belongsTo(LeaveType::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    // ─── Computed Attributes ──────────────────────────────────────────────────

    /**
     * Consumed leave count.
     * Half-day (half_am / half_pm) = 0.5; full = working calendar days (excluding Sundays & declared Holidays).
     */
    public function getDaysCountAttribute(): float
    {
        if ($this->duration !== 'full') {
            return 0.5;
        }

        if (!$this->from_date || !$this->to_date) {
            return 1.0;
        }

        $start = $this->from_date->copy();
        $end   = $this->to_date->copy();

        static $holidayDates = null;
        if ($holidayDates === null) {
            $holidayDates = Holiday::all()
                ->map(fn ($h) => Carbon::parse($h->date)->toDateString())
                ->toArray();
        }

        $count = 0.0;
        $current = $start->copy();

        while ($current->lte($end)) {
            // Exclude Sundays AND declared company holidays
            if (! $current->isSunday() && ! in_array($current->toDateString(), $holidayDates, true)) {
                $count += 1.0;
            }
            $current->addDay();
        }

        return $count;
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    /**
     * Returns true if this leave covers the given date string (YYYY-MM-DD).
     */
    public function coversDate(string $date): bool
    {
        $d = Carbon::parse($date);
        return $this->from_date->lte($d) && $this->to_date->gte($d);
    }

    // ─── Scopes ───────────────────────────────────────────────────────────────

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeForDate($query, string $date)
    {
        return $query->where('from_date', '<=', $date)
                     ->where('to_date', '>=', $date);
    }

    // ────────────────────────────────────────────────────────────────────────────
    // QUERY SCOPES - Automatic DataScope Integration
    // ────────────────────────────────────────────────────────────────────────────

    /**
     * Scope query to visible users based on DataScope permissions
     * 
     * Replaces repetitive pattern:
     * $visibleIds = DataScope::visibleUserIds('leaves');
     * if ($visibleIds !== null) { $query->whereIn('user_id', $visibleIds); }
     * 
     * Usage: Leave::visibleToUser()->get();
     */
    public function scopeVisibleToUser($query, ?User $user = null)
    {
        return DataScope::apply($query, 'leaves', 'user_id');
    }

    /**
     * Scope query to actionable subordinates only (for approval/rejection)
     * Excludes own records (conflict of interest prevention)
     * 
     * Usage: Leave::actionableByUser()->where('status', 'pending')->get();
     */
    public function scopeActionableByUser($query, ?User $user = null)
    {
        $user = $user ?? auth()->user();
        $actionableIds = DataScope::actionableSubordinateIds('leaves', $user);
        
        if ($actionableIds !== null) {
            return $query->whereIn('user_id', $actionableIds);
        }
        
        return $query;
    }
}