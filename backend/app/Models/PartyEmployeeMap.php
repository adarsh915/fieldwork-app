<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PartyEmployeeMap extends Model
{
    use HasFactory;

    protected $table = 'party_employee_map';

    protected $fillable = [
        'party_id',
        'employee_id',
        'assigned_by',
        'assigned_at',
        'unassigned_at',
        'is_active',
    ];

    protected $casts = [
        'assigned_at'   => 'datetime',
        'unassigned_at' => 'datetime',
        'is_active'     => 'boolean',
    ];

    // ── Relationships ──────────────────────────────────────────────────────────

    public function party(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'party_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    // ── Scopes ─────────────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForParty($query, int $partyId)
    {
        return $query->where('party_id', $partyId);
    }

    public function scopeForEmployee($query, int $employeeId)
    {
        return $query->where('employee_id', $employeeId);
    }
}
