<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        'party_code',
        'name',
        'owner_name',
        'mobile',
        'email',
        'address',
        'city',
        'state',
        'pincode',
        'party_type',
        'category',
        'credit_limit',
        'credit_days',
        'opening_balance',
        'is_active',
        'user_id',
        'land_area',
        'latitude',
        'longitude',
        'approval_status',
        'submitted_by',
    ];

    protected $casts = [
        'credit_limit'    => 'decimal:2',
        'opening_balance' => 'decimal:2',
        'is_active'       => 'boolean',
        'latitude'        => 'decimal:7',
        'longitude'       => 'decimal:7',
    ];

    // ── Relationships ──────────────────────────────────────────────────────────

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /** User who submitted this party request (field worker) */
    public function submittedBy()
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    /** Currently active assignment from history table */
    public function activeAssignment()
    {
        return $this->hasOne(PartyEmployeeMap::class, 'party_id')
            ->where('is_active', true)
            ->latest('assigned_at');
    }

    /** Full assignment history */
    public function assignments()
    {
        return $this->hasMany(PartyEmployeeMap::class, 'party_id')
            ->orderByDesc('assigned_at');
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class, 'party_id');
    }

    public function visits()
    {
        return $this->hasMany(Visit::class, 'party_id');
    }

    public function orders()
    {
        return $this->hasMany(Order::class, 'party_id');
    }

    public function auditLogs()
    {
        return $this->morphMany(AuditLog::class, 'auditable');
    }

    // ── Scopes ─────────────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('customers.is_active', true);
    }

    public function scopeUnassigned($query)
    {
        return $query->whereDoesntHave('assignments', fn ($q) => $q->where('is_active', true));
    }

    public function scopeAssignedToEmployee($query, int $employeeId)
    {
        return $query->whereHas('assignments', fn ($q) =>
            $q->where('employee_id', $employeeId)->where('is_active', true)
        );
    }
}
