<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LeaveType extends Model
{
    protected $fillable = [
        'name',
        'code',
        'days_per_year',
        'accrual_frequency',
        'days_per_month',
        'carry_forward',
        'is_active',
    ];

    protected $casts = [
        'carry_forward'  => 'boolean',
        'is_active'      => 'boolean',
        'days_per_year'  => 'integer',
        'days_per_month' => 'float',
    ];

    // ─── Relationships ────────────────────────────────────────────────────────

    public function leaves(): HasMany
    {
        return $this->hasMany(Leave::class);
    }

    public function balances(): HasMany
    {
        return $this->hasMany(LeaveBalance::class);
    }

    // ─── Scopes ───────────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
