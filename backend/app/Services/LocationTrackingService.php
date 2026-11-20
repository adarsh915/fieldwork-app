<?php

namespace App\Services;

use App\Models\GpsTrackingLog;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * LocationTrackingService
 * 
 * Handles all location tracking operations:
 * - Batch location updates from mobile app
 * - Retrieve latest locations for workers
 * - Calculate route history and distance traveled
 * - Validate location accuracy
 */
class LocationTrackingService
{
    /**
     * Store bulk location updates from mobile app
     * 
     * @param int $userId
     * @param array $locations Array of location points
     * @return array Result with counts
     */
    public function storeBulkLocations(int $userId, array $locations): array
    {
        $inserted = 0;
        $skipped = 0;
        $errors = [];

        DB::beginTransaction();
        try {
            foreach ($locations as $location) {
                // Validate required fields
                if (!isset($location['latitude'], $location['longitude'], $location['recorded_at'])) {
                    $skipped++;
                    continue;
                }

                // Validate coordinates
                if (!$this->isValidCoordinate($location['latitude'], $location['longitude'])) {
                    $skipped++;
                    $errors[] = "Invalid coordinates: {$location['latitude']}, {$location['longitude']}";
                    continue;
                }

                // Check for duplicate (same location within 10 seconds)
                $recordedAt = Carbon::parse($location['recorded_at']);
                $exists = GpsTrackingLog::where('user_id', $userId)
                    ->where('latitude', $location['latitude'])
                    ->where('longitude', $location['longitude'])
                    ->whereBetween('recorded_at', [
                        $recordedAt->copy()->subSeconds(10),
                        $recordedAt->copy()->addSeconds(10)
                    ])
                    ->exists();

                if ($exists) {
                    $skipped++;
                    continue;
                }

                // Insert location
                GpsTrackingLog::create([
                    'user_id' => $userId,
                    'latitude' => $location['latitude'],
                    'longitude' => $location['longitude'],
                    'recorded_at' => $recordedAt,
                    'battery_level' => $location['battery_level'] ?? null,
                    'accuracy' => $location['accuracy'] ?? null,
                ]);

                $inserted++;
            }

            DB::commit();

            return [
                'success' => true,
                'inserted' => $inserted,
                'skipped' => $skipped,
                'errors' => $errors,
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('LocationTrackingService: Bulk insert failed', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Failed to store locations',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get latest location for all active workers
     * Respects DataScope permissions
     * 
     * @param User|null $requestingUser
     * @return Collection
     */
    public function getLatestLocations(?User $requestingUser = null): Collection
    {
        $requestingUser = $requestingUser ?? auth()->user();

        // Get scoped user IDs
        $scopedUserIds = DataScope::visibleUserIds('visits', $requestingUser);

        // Subquery to get latest location ID for each user
        $latestLocationIds = GpsTrackingLog::select('id')
            ->whereIn('user_id', function ($query) use ($scopedUserIds) {
                $query->select('user_id')
                    ->from('gps_tracking_logs');
                
                if ($scopedUserIds !== null) {
                    $query->whereIn('user_id', $scopedUserIds);
                }
            })
            ->whereRaw('recorded_at = (
                SELECT MAX(recorded_at) 
                FROM gps_tracking_logs AS gl2 
                WHERE gl2.user_id = gps_tracking_logs.user_id
            )');

        // Get locations with user details
        $locations = GpsTrackingLog::with(['user:id,name,employee_id,phone'])
            ->whereIn('id', $latestLocationIds)
            ->where('recorded_at', '>=', Carbon::now()->subHours(12)) // Only last 12 hours
            ->get()
            ->map(function ($log) {
                return [
                    'user_id' => $log->user_id,
                    'name' => $log->user->name ?? 'Unknown',
                    'employee_id' => $log->user->employee_id ?? null,
                    'phone' => $log->user->phone ?? null,
                    'latitude' => (float) $log->latitude,
                    'longitude' => (float) $log->longitude,
                    'recorded_at' => $log->recorded_at->toISOString(),
                    'battery_level' => $log->battery_level ? (float) $log->battery_level : null,
                    'accuracy' => $log->accuracy ? (float) $log->accuracy : null,
                    'minutes_ago' => $log->recorded_at->diffInMinutes(Carbon::now()),
                    'is_recent' => $log->recorded_at->diffInMinutes(Carbon::now()) <= 5,
                    'status' => $this->getWorkerStatus($log->recorded_at),
                ];
            });

        return $locations;
    }

    /**
     * Get location history for a specific user and date
     * 
     * @param int $userId
     * @param string $date
     * @return array
     */
    public function getRouteHistory(int $userId, string $date): array
    {
        $startDate = Carbon::parse($date)->startOfDay();
        $endDate = Carbon::parse($date)->endOfDay();

        $locations = GpsTrackingLog::where('user_id', $userId)
            ->whereBetween('recorded_at', [$startDate, $endDate])
            ->orderBy('recorded_at', 'asc')
            ->get()
            ->map(function ($log) {
                return [
                    'latitude' => (float) $log->latitude,
                    'longitude' => (float) $log->longitude,
                    'recorded_at' => $log->recorded_at->toISOString(),
                    'time' => $log->recorded_at->format('H:i:s'),
                    'battery_level' => $log->battery_level ? (float) $log->battery_level : null,
                    'accuracy' => $log->accuracy ? (float) $log->accuracy : null,
                ];
            });

        // Calculate total distance traveled
        $totalDistance = $this->calculateTotalDistance($locations->toArray());

        return [
            'user_id' => $userId,
            'date' => $date,
            'total_points' => $locations->count(),
            'total_distance_km' => round($totalDistance, 2),
            'route' => $locations->toArray(),
        ];
    }

    /**
     * Get currently active workers (location updated in last 5 minutes)
     * 
     * @param User|null $requestingUser
     * @return Collection
     */
    public function getActiveWorkers(?User $requestingUser = null): Collection
    {
        $allLocations = $this->getLatestLocations($requestingUser);
        
        return $allLocations->filter(function ($location) {
            return $location['is_recent'];
        });
    }

    /**
     * Calculate total distance traveled from location points
     * Uses Haversine formula
     * 
     * @param array $locations
     * @return float Distance in kilometers
     */
    protected function calculateTotalDistance(array $locations): float
    {
        if (count($locations) < 2) {
            return 0;
        }

        $totalDistance = 0;

        for ($i = 0; $i < count($locations) - 1; $i++) {
            $point1 = $locations[$i];
            $point2 = $locations[$i + 1];

            $distance = $this->haversineDistance(
                $point1['latitude'],
                $point1['longitude'],
                $point2['latitude'],
                $point2['longitude']
            );

            // Ignore unrealistic jumps (> 100 km between points)
            if ($distance < 100) {
                $totalDistance += $distance;
            }
        }

        return $totalDistance;
    }

    /**
     * Calculate distance between two coordinates using Haversine formula
     * 
     * @param float $lat1
     * @param float $lon1
     * @param float $lat2
     * @param float $lon2
     * @return float Distance in kilometers
     */
    protected function haversineDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371; // Earth's radius in kilometers

        $latDelta = deg2rad($lat2 - $lat1);
        $lonDelta = deg2rad($lon2 - $lon1);

        $a = sin($latDelta / 2) * sin($latDelta / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($lonDelta / 2) * sin($lonDelta / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    /**
     * Validate if coordinates are valid
     * 
     * @param float $latitude
     * @param float $longitude
     * @return bool
     */
    protected function isValidCoordinate(float $latitude, float $longitude): bool
    {
        return $latitude >= -90 && $latitude <= 90 &&
               $longitude >= -180 && $longitude <= 180 &&
               !($latitude == 0 && $longitude == 0); // Reject null island
    }

    /**
     * Determine worker status based on last location time
     * 
     * @param Carbon $lastSeen
     * @return string
     */
    protected function getWorkerStatus(Carbon $lastSeen): string
    {
        $minutesAgo = $lastSeen->diffInMinutes(Carbon::now());

        if ($minutesAgo <= 5) {
            return 'active';
        } elseif ($minutesAgo <= 30) {
            return 'idle';
        } else {
            return 'offline';
        }
    }

    /**
     * Clean up old location records (older than 90 days)
     * Should be run as scheduled task
     * 
     * @return int Number of records deleted
     */
    public function cleanupOldRecords(): int
    {
        $cutoffDate = Carbon::now()->subDays(90);

        return GpsTrackingLog::where('recorded_at', '<', $cutoffDate)->delete();
    }

    /**
     * Get location statistics for a user
     * 
     * @param int $userId
     * @param string $startDate
     * @param string $endDate
     * @return array
     */
    public function getLocationStats(int $userId, string $startDate, string $endDate): array
    {
        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->endOfDay();

        $totalPoints = GpsTrackingLog::where('user_id', $userId)
            ->whereBetween('recorded_at', [$start, $end])
            ->count();

        $daysTracked = GpsTrackingLog::where('user_id', $userId)
            ->whereBetween('recorded_at', [$start, $end])
            ->selectRaw('DATE(recorded_at) as date')
            ->groupBy('date')
            ->get()
            ->count();

        return [
            'user_id' => $userId,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'total_location_points' => $totalPoints,
            'days_tracked' => $daysTracked,
            'average_points_per_day' => $daysTracked > 0 ? round($totalPoints / $daysTracked, 2) : 0,
        ];
    }
}
