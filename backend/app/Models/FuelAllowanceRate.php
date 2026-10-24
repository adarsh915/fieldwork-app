<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Models\Role;
use App\Traits\Auditable;

class FuelAllowanceRate extends Model
{
    use HasFactory, Auditable;

    protected $fillable = [
        'vehicle_type_id',
        'scope_type',
        'scope_id',
        'rate_per_km',
        'effective_from',
        'effective_to',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'rate_per_km'    => 'decimal:2',
        'effective_from' => 'date:Y-m-d',
        'effective_to'   => 'date:Y-m-d',
        'is_active'      => 'boolean',
    ];

    /**
     * Vehicle Type relation
     */
    public function vehicleType()
    {
        return $this->belongsTo(VehicleType::class);
    }

    /**
     * User relation when scope_type === 'user'
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'scope_id');
    }

    /**
     * Role relation when scope_type === 'role'
     */
    public function role()
    {
        return $this->belongsTo(Role::class, 'scope_id');
    }

    /**
     * Scope: Active rates
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Effective on a given date (defaults to today)
     */
    public function scopeEffectiveOn(Builder $query, ?string $date = null): Builder
    {
        $targetDate = $date ?: now()->toDateString();

        return $query->where('effective_from', '<=', $targetDate)
                     ->where(function (Builder $q) use ($targetDate) {
                         $q->whereNull('effective_to')
                           ->orWhere('effective_to', '>=', $targetDate);
                     });
    }

    /**
     * Scope: Global rates
     */
    public function scopeGlobal(Builder $query): Builder
    {
        return $query->where('scope_type', 'global')->whereNull('scope_id');
    }

    /**
     * Scope: Role rates
     */
    public function scopeForRoles(Builder $query, array|int $roleIds): Builder
    {
        $ids = is_array($roleIds) ? $roleIds : [$roleIds];
        return $query->where('scope_type', 'role')->whereIn('scope_id', $ids);
    }

    /**
     * Scope: User override rates
     */
    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('scope_type', 'user')->where('scope_id', $userId);
    }
}
