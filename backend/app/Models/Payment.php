<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'receipt_no',
        'party_id',
        'collected_by',
        'visit_id',
        'amount',
        'payment_mode',
        'reference_number',
        'proof_photo_url',
        'collection_latitude',
        'collection_longitude',
        'collection_datetime',
        'allocated_invoice_id',
        'notes',
        'status',
        'confirmed_by',
        'confirmed_at',
        'rejection_reason',
        'idempotency_key',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'collection_latitude' => 'decimal:7',
        'collection_longitude' => 'decimal:7',
        'collection_datetime' => 'datetime',
        'confirmed_at' => 'datetime',
    ];

    // ── Relationships ──────────────────────────────────────────────────────────

    public function party()
    {
        return $this->belongsTo(Customer::class, 'party_id');
    }

    public function collectedBy()
    {
        return $this->belongsTo(User::class, 'collected_by');
    }

    public function confirmedBy()
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }

    public function visit()
    {
        return $this->belongsTo(Visit::class, 'visit_id');
    }

    // ── Scopes ─────────────────────────────────────────────────────────────────

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeConfirmed($query)
    {
        return $query->where('status', 'confirmed');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }
}