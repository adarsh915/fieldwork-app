<?php

namespace App\Services;

use App\Models\FuelAllowanceRate;
use App\Models\User;
use App\Models\VehicleType;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FuelRateResolverService
{
    const CACHE_TTL_SECONDS = 3600; // 1 hour
    const CACHE_PREFIX = 'fuel_rate_resolution_';

    /**
     * Resolve effective rate using 3-Tier Priority Hierarchy
     * 
     * Tier 1 (Highest): Individual Employee Custom Rate Override
     * Tier 2 (Medium): Role-based Default Rate
     * Tier 3 (Fallback): Global Base Rate
     * Tier 4: Legacy vehicle rate_per_km / Default Zero
     *
     * @param int $userId
     * @param string|int $vehicleIdentifier Vehicle ID or slug (e.g. 'bike', 'car')
     * @param string|null $date Date in Y-m-d format (defaults to today)
     * @return array
     */
    public function getEffectiveRate(int $userId, string|int $vehicleIdentifier, ?string $date = null): array
    {
        $targetDate = $date ? Carbon::parse($date)->toDateString() : Carbon::today()->toDateString();
        $vehicle = $this->resolveVehicle($vehicleIdentifier);

        if (!$vehicle) {
            return [
                'rate_per_km' => 0.00,
                'source' => 'invalid_vehicle',
                'rate_id' => null,
                'vehicle_type_id' => null,
                'vehicle_slug' => is_string($vehicleIdentifier) ? $vehicleIdentifier : null,
                'vehicle_name' => 'Unknown',
                'scope_type' => null,
                'scope_id' => null,
                'effective_from' => null,
                'effective_to' => null,
                'date' => $targetDate,
            ];
        }

        $cacheKey = self::CACHE_PREFIX . "{$userId}_{$vehicle->id}_{$targetDate}";

        return Cache::remember($cacheKey, self::CACHE_TTL_SECONDS, function () use ($userId, $vehicle, $targetDate) {
            return $this->resolveRateHierarchy($userId, $vehicle, $targetDate);
        });
    }

    /**
     * Resolve rate without cache
     */
    protected function resolveRateHierarchy(int $userId, VehicleType $vehicle, string $targetDate): array
    {
        $user = User::with('roles')->find($userId);
        $userRoleIds = $user ? $user->roles->pluck('id')->toArray() : [];

        // -------------------------------------------------------------
        // TIER 1: Individual Employee Custom Rate Override
        // -------------------------------------------------------------
        $userOverride = FuelAllowanceRate::query()
            ->where('vehicle_type_id', $vehicle->id)
            ->where('scope_type', 'user')
            ->where('scope_id', $userId)
            ->active()
            ->effectiveOn($targetDate)
            ->orderByDesc('effective_from')
            ->orderByDesc('id')
            ->first();

        if ($userOverride) {
            return $this->formatRatePayload($userOverride, 'user_override', $vehicle, $targetDate);
        }

        // -------------------------------------------------------------
        // TIER 2: Role-based Default Rate
        // -------------------------------------------------------------
        if (!empty($userRoleIds)) {
            $roleRate = FuelAllowanceRate::query()
                ->where('vehicle_type_id', $vehicle->id)
                ->where('scope_type', 'role')
                ->whereIn('scope_id', $userRoleIds)
                ->active()
                ->effectiveOn($targetDate)
                ->orderByDesc('rate_per_km') // If user has multiple roles, pick the highest rate
                ->orderByDesc('effective_from')
                ->orderByDesc('id')
                ->first();

            if ($roleRate) {
                return $this->formatRatePayload($roleRate, 'role_default', $vehicle, $targetDate);
            }
        }

        // -------------------------------------------------------------
        // TIER 3: Global Base Rate
        // -------------------------------------------------------------
        $globalRate = FuelAllowanceRate::query()
            ->where('vehicle_type_id', $vehicle->id)
            ->where('scope_type', 'global')
            ->whereNull('scope_id')
            ->active()
            ->effectiveOn($targetDate)
            ->orderByDesc('effective_from')
            ->orderByDesc('id')
            ->first();

        if ($globalRate) {
            return $this->formatRatePayload($globalRate, 'global_base', $vehicle, $targetDate);
        }

        // -------------------------------------------------------------
        // TIER 4: Fallback to VehicleType base rate or 0.00
        // -------------------------------------------------------------
        $fallbackRate = (float) ($vehicle->rate_per_km ?? 0.00);

        return [
            'rate_per_km' => round($fallbackRate, 2),
            'source' => $fallbackRate > 0 ? 'vehicle_fallback' : 'default_zero',
            'rate_id' => null,
            'vehicle_type_id' => $vehicle->id,
            'vehicle_slug' => $vehicle->slug,
            'vehicle_name' => $vehicle->name,
            'scope_type' => 'global',
            'scope_id' => null,
            'effective_from' => null,
            'effective_to' => null,
            'date' => $targetDate,
        ];
    }

    /**
     * Calculate trip allowance based on distance and resolved rate
     */
    public function calculateTripAllowance(int $userId, string|int $vehicleIdentifier, float $distanceKm, ?string $date = null): array
    {
        $rateInfo = $this->getEffectiveRate($userId, $vehicleIdentifier, $date);
        $totalAmount = round($distanceKm * $rateInfo['rate_per_km'], 2);

        return array_merge($rateInfo, [
            'distance_km' => round($distanceKm, 2),
            'total_amount' => $totalAmount,
        ]);
    }

    /**
     * Create or update a fuel allowance rate with automatic safe date versioning.
     * Prevents retroactive corruption of past calculations.
     *
     * @param array $data
     * @return FuelAllowanceRate
     */
    public function setRate(array $data): FuelAllowanceRate
    {
        return DB::transaction(function () use ($data) {
            $vehicleTypeId = $data['vehicle_type_id'];
            $scopeType = $data['scope_type']; // 'global', 'role', 'user'
            $scopeId = ($scopeType === 'global') ? null : ($data['scope_id'] ?? null);
            $ratePerKm = (float) $data['rate_per_km'];
            $effectiveFrom = isset($data['effective_from'])
                ? Carbon::parse($data['effective_from'])->toDateString()
                : Carbon::today()->toDateString();
            $effectiveTo = isset($data['effective_to']) && !empty($data['effective_to'])
                ? Carbon::parse($data['effective_to'])->toDateString()
                : null;
            $isActive = isset($data['is_active']) ? (bool) $data['is_active'] : true;
            $notes = $data['notes'] ?? null;

            // If updating an existing specific record by ID
            if (!empty($data['id'])) {
                $existing = FuelAllowanceRate::find($data['id']);
                if ($existing) {
                    $existing->update([
                        'vehicle_type_id' => $vehicleTypeId,
                        'scope_type' => $scopeType,
                        'scope_id' => $scopeId,
                        'rate_per_km' => $ratePerKm,
                        'effective_from' => $effectiveFrom,
                        'effective_to' => $effectiveTo,
                        'is_active' => $isActive,
                        'notes' => $notes,
                    ]);
                    $this->clearCache();
                    return $existing;
                }
            }

            // Find currently active rate without effective_to for the same scope
            $existingRates = FuelAllowanceRate::query()
                ->where('vehicle_type_id', $vehicleTypeId)
                ->where('scope_type', $scopeType)
                ->where('scope_id', $scopeId)
                ->where('is_active', true)
                ->where(function ($q) use ($effectiveFrom) {
                    $q->whereNull('effective_to')
                        ->orWhere('effective_to', '>=', $effectiveFrom);
                })
                ->get();

            foreach ($existingRates as $existingRate) {
                $existingFrom = $existingRate->effective_from->toDateString();

                if ($effectiveFrom > $existingFrom) {
                    // Safe archiving: Close the previous rate on the day before new effective_from
                    $dayBefore = Carbon::parse($effectiveFrom)->subDay()->toDateString();
                    $existingRate->update([
                        'effective_to' => $dayBefore,
                    ]);
                    Log::info("Archived prior fuel rate ID {$existingRate->id}: effective_to set to {$dayBefore}");
                } elseif ($effectiveFrom === $existingFrom && !$effectiveTo && $existingRate->effective_to === null) {
                    // Same effective start and both open-ended: deactivate prior duplicate
                    $existingRate->update(['is_active' => false]);
                }
            }

            // Create the new rate version
            $newRate = FuelAllowanceRate::create([
                'vehicle_type_id' => $vehicleTypeId,
                'scope_type' => $scopeType,
                'scope_id' => $scopeId,
                'rate_per_km' => $ratePerKm,
                'effective_from' => $effectiveFrom,
                'effective_to' => $effectiveTo,
                'is_active' => $isActive,
                'notes' => $notes,
            ]);

            // Clear cache
            $this->clearCache();

            return $newRate;
        });
    }

    /**
     * Delete / Deactivate a rate override
     */
    public function deleteRate(int $rateId): bool
    {
        $rate = FuelAllowanceRate::findOrFail($rateId);
        $deleted = $rate->delete();
        $this->clearCache();
        return (bool) $deleted;
    }

    /**
     * Clear all cached rate resolutions
     */
    public function clearCache(): void
    {
        // Flush tags if cache driver supports tagging, otherwise clear keys or flush cache store
        try {
            Cache::flush();
        } catch (\Exception $e) {
            Log::warning('Failed to flush cache in FuelRateResolverService: ' . $e->getMessage());
        }
    }

    /**
     * Helper to resolve VehicleType instance
     */
    protected function resolveVehicle(string|int $vehicleIdentifier): ?VehicleType
    {
        return VehicleType::findByIdentifier($vehicleIdentifier);
    }

    /**
     * Helper to format the rate payload
     */
    protected function formatRatePayload(FuelAllowanceRate $rate, string $source, VehicleType $vehicle, string $targetDate): array
    {
        return [
            'rate_per_km' => (float) $rate->rate_per_km,
            'source' => $source,
            'rate_id' => $rate->id,
            'vehicle_type_id' => $vehicle->id,
            'vehicle_slug' => $vehicle->slug,
            'vehicle_name' => $vehicle->name,
            'scope_type' => $rate->scope_type,
            'scope_id' => $rate->scope_id,
            'effective_from' => $rate->effective_from?->toDateString(),
            'effective_to' => $rate->effective_to?->toDateString(),
            'date' => $targetDate,
        ];
    }
}
