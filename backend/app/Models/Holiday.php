<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\Auditable;

class Holiday extends Model
{
    use Auditable;

    protected $fillable = ['date', 'name', 'type', 'created_by'];

    protected $casts = [
        'date' => 'date',
    ];

    // ─── Relationships ────────────────────────────────────────────────────────

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ─── Scopes ───────────────────────────────────────────────────────────────

    public function scopeForYear($query, int $year)
    {
        return $query->whereYear('date', $year);
    }
}
