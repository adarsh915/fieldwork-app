<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\LocationTrackingService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

/**
 * LocationTrackingController
 * 
 * API endpoints for mobile app location tracking
 * 
 * Security:
 * - All endpoints require Sanctum authentication
 * - Users can only upload their own location
 * - Respects DataScope for viewing others' locations
 */
class LocationTrackingController extends Controller
{
    protected $locationService;

    public function __construct(LocationTrackingService $locationService)
    {
        $this->middleware('auth:sanctum');
        $this->locationService = $locationService;
    }

    /**
     * POST /api/location/bulk-update
     * 
     * Receive bulk location updates from mobile app
     * Mobile app sends batch of locations collected while working
     * 
     * Request body:
     * {
     *   "locations": [
     *     {
     *       "latitude": 28.6139,
     *       "longitude": 77.2090,
     *       "recorded_at": "2024-01-15T10:30:00Z",
     *       "battery_level": 85.5,
     *       "accuracy": 10.5
     *     }
     *   ]
     * }
     */
    public function bulkUpdate(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'locations' => 'required|array|min:1|max:500',
            'locations.*.latitude' => 'required|numeric|between:-90,90',
            'locations.*.longitude' => 'required|numeric|between:-180,180',
            'locations.*.recorded_at' => 'required|date',
            'locations.*.battery_level' => 'nullable|numeric|between:0,100',
            'locations.*.accuracy' => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $userId = auth()->id();
        $locations = $request->input('locations');

        $result = $this->locationService->storeBulkLocations($userId, $locations);

        if ($result['success']) {
            return response()->json([
                'success' => true,
                'message' => 'Locations stored successfully',
                'inserted' => $result['inserted'],
                'skipped' => $result['skipped'],
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => $result['message'] ?? 'Failed to store locations',
        ], 500);
    }

    /**
     * GET /api/location/my-latest
     * 
     * Get my own latest location
     */
    public function getMyLatest(): JsonResponse
    {
        $userId = auth()->id();

        $location = \App\Models\GpsTrackingLog::where('user_id', $userId)
            ->orderBy('recorded_at', 'desc')
            ->first();

        if (!$location) {
            return response()->json([
                'success' => false,
                'message' => 'No location found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'location' => [
                'latitude' => (float) $location->latitude,
                'longitude' => (float) $location->longitude,
                'recorded_at' => $location->recorded_at->toISOString(),
                'battery_level' => $location->battery_level ? (float) $location->battery_level : null,
                'accuracy' => $location->accuracy ? (float) $location->accuracy : null,
            ],
        ]);
    }

    /**
     * GET /api/location/my-history
     * 
     * Get my location history for a specific date
     * 
     * Query params:
     * - date: YYYY-MM-DD (default: today)
     */
    public function getMyHistory(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'date' => 'nullable|date_format:Y-m-d',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid date format',
                'errors' => $validator->errors(),
            ], 422);
        }

        $userId = auth()->id();
        $date = $request->input('date', now()->format('Y-m-d'));

        $history = $this->locationService->getRouteHistory($userId, $date);

        return response()->json([
            'success' => true,
            'data' => $history,
        ]);
    }

    /**
     * POST /api/location/single
     * 
     * Store single location point (for immediate tracking)
     * 
     * Request body:
     * {
     *   "latitude": 28.6139,
     *   "longitude": 77.2090,
     *   "battery_level": 85.5,
     *   "accuracy": 10.5
     * }
     */
    public function storeSingle(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'battery_level' => 'nullable|numeric|between:0,100',
            'accuracy' => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $location = \App\Models\GpsTrackingLog::create([
                'user_id' => auth()->id(),
                'latitude' => $request->input('latitude'),
                'longitude' => $request->input('longitude'),
                'recorded_at' => now(),
                'battery_level' => $request->input('battery_level'),
                'accuracy' => $request->input('accuracy'),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Location stored successfully',
                'location' => [
                    'id' => $location->id,
                    'recorded_at' => $location->recorded_at->toISOString(),
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to store location',
            ], 500);
        }
    }

    /**
     * GET /api/location/tracking-settings
     * 
     * Get location tracking configuration from admin settings
     */
    public function getTrackingSettings(): JsonResponse
    {
        $settings = app(\App\Services\SettingsService::class);

        return response()->json([
            'success' => true,
            'settings' => [
                'enabled' => (bool) ($settings->get('location_tracking_enabled', '1') === '1'),
                'time_interval' => (int) $settings->get('location_tracking_time_interval', '60'),
                'distance_interval' => (int) $settings->get('location_tracking_distance_interval', '50'),
                'accuracy' => $settings->get('location_tracking_accuracy', 'high'),
            ],
        ]);
    }
}
