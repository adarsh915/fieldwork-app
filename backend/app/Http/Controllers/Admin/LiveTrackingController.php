<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\LocationTrackingService;
use App\Services\DataScope;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * LiveTrackingController
 * 
 * Admin panel controller for live location tracking
 * Displays interactive map with field worker locations
 * 
 * Security:
 * - Requires authentication
 * - Respects DataScope permissions
 * - Only shows workers user has permission to view
 */
class LiveTrackingController extends Controller
{
    protected $locationService;

    public function __construct(LocationTrackingService $locationService)
    {
        $this->middleware('auth');
        $this->locationService = $locationService;
    }

    /**
     * GET /admin/tracking/live
     * 
     * Show live tracking map page
     */
    public function index()
    {
        // Check if user has permission to view tracking
        if (!DataScope::canView('visits')) {
            abort(403, 'You do not have permission to view live tracking');
        }

        return view('admin.tracking.live-map');
    }

    /**
     * GET /admin/tracking/api/latest-locations
     * 
     * AJAX endpoint: Get latest locations for all workers (JSON)
     */
    public function getLatestLocations(): JsonResponse
    {
        $locations = $this->locationService->getLatestLocations(auth()->user());

        return response()->json([
            'success' => true,
            'count' => $locations->count(),
            'locations' => $locations->values(),
            'timestamp' => now()->toISOString(),
        ]);
    }

    /**
     * GET /admin/tracking/api/active-workers
     * 
     * AJAX endpoint: Get currently active workers only (location < 5 min old)
     */
    public function getActiveWorkers(): JsonResponse
    {
        $workers = $this->locationService->getActiveWorkers(auth()->user());

        return response()->json([
            'success' => true,
            'count' => $workers->count(),
            'workers' => $workers->values(),
        ]);
    }

    /**
     * GET /admin/tracking/api/route-history/{userId}
     * 
     * AJAX endpoint: Get route history for specific user and date
     * 
     * Query params:
     * - date: YYYY-MM-DD (default: today)
     */
    public function getRouteHistory(Request $request, int $userId): JsonResponse
    {
        // Check if requesting user can view this worker
        $scopedUserIds = DataScope::visibleUserIds('visits', auth()->user());
        
        if ($scopedUserIds !== null && !in_array($userId, $scopedUserIds)) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to view this worker\'s location',
            ], 403);
        }

        $date = $request->input('date', now()->format('Y-m-d'));
        $history = $this->locationService->getRouteHistory($userId, $date);

        return response()->json([
            'success' => true,
            'data' => $history,
        ]);
    }

    /**
     * GET /admin/tracking/api/stats/{userId}
     * 
     * Get location statistics for a user
     */
    public function getUserStats(Request $request, int $userId): JsonResponse
    {
        // Check permissions
        $scopedUserIds = DataScope::visibleUserIds('visits', auth()->user());
        
        if ($scopedUserIds !== null && !in_array($userId, $scopedUserIds)) {
            return response()->json([
                'success' => false,
                'message' => 'Permission denied',
            ], 403);
        }

        $startDate = $request->input('start_date', now()->subDays(7)->format('Y-m-d'));
        $endDate = $request->input('end_date', now()->format('Y-m-d'));

        $stats = $this->locationService->getLocationStats($userId, $startDate, $endDate);

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }
}
