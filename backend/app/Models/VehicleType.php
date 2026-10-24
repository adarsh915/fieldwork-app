<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use App\Traits\Auditable;

class VehicleType extends Model
{
    use HasFactory, Auditable;

    protected $fillable = [
        'name',
        'slug',
        'rate_per_km',
        'requires_vehicle_no',
        'is_active',
    ];

    protected $casts = [
        'rate_per_km'         => 'decimal:2',
        'requires_vehicle_no' => 'boolean',
        'is_active'           => 'boolean',
    ];

    public static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->slug)) {
                $model->slug = Str::slug($model->name);
            }
        });
    }

    public function dayStarts()
    {
        return $this->hasMany(DayStart::class);
    }

    public function trips()
    {
        return $this->hasMany(Trip::class);
    }

    public function fuelAllowanceRates()
    {
        return $this->hasMany(FuelAllowanceRate::class);
    }

    public function activeFuelRates()
    {
        return $this->hasMany(FuelAllowanceRate::class)->where('is_active', true);
    }

    public function globalRate(?string $date = null)
    {
        return $this->hasOne(FuelAllowanceRate::class)
            ->where('scope_type', 'global')
            ->where('is_active', true)
            ->where('effective_from', '<=', $date ?: now()->toDateString())
            ->where(function ($q) use ($date) {
                $target = $date ?: now()->toDateString();
                $q->whereNull('effective_to')->orWhere('effective_to', '>=', $target);
            })
            ->latest('effective_from');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Find by ID or Slug or Name
     */
    public static function findByIdentifier(string|int $identifier): ?self
    {
        if (is_numeric($identifier)) {
            return self::find($identifier);
        }

        $slug = Str::slug($identifier);
        return self::where('slug', $slug)
            ->orWhere('slug', strtolower($identifier))
            ->orWhere('name', $identifier)
            ->first();
    }
}
