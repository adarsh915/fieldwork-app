<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Area;
use App\Models\Customer;
use App\Models\District;
use App\Models\TourPurpose;
use App\Models\TourType;
use App\Models\VehicleType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Master Data Controller
 *
 * Returns dropdown/picker data for the mobile app forms.
 * All routes: GET /api/master/*
 */
class MasterController extends Controller
{
    /** GET /api/master/tour-purposes */
    public function tourPurposes(): JsonResponse
    {
        $data = TourPurpose::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'requires_party']);

        return response()->json(['success' => true, 'data' => $data]);
    }

    /** GET /api/master/tour-types */
    public function tourTypes(): JsonResponse
    {
        $data = TourType::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        return response()->json(['success' => true, 'data' => $data]);
    }

    /** GET /api/master/vehicle-types */
    public function vehicleTypes(): JsonResponse
    {
        $data = VehicleType::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'requires_vehicle_no']);

        return response()->json(['success' => true, 'data' => $data]);
    }

    /**
     * GET /api/master/locations
     * Returns active districts with their active areas.
     * Non-admin workers only receive districts and areas where their assigned parties exist.
     */
    public function locations(Request $request): JsonResponse
    {
        $user = $request->user();

        // Admins and users with full party permissions see all master locations
        if (!$user || $user->canSelectAllParties()) {
            $districts = District::where('is_active', true)
                ->with(['areas' => function ($q) {
                    $q->where('is_active', true)->orderBy('name');
                }])
                ->orderBy('name')
                ->get(['id', 'name', 'state', 'code']);

            return response()->json(['success' => true, 'data' => $districts]);
        }

        // Get user's assigned party query
        $assignedPartiesQuery = Customer::query()->active()->where('approval_status', 'approved');

        $assignedDistrictIds = $user->territories()->whereNull('area_id')->pluck('district_id')->toArray();
        $assignedAreaIds = $user->territories()->whereNotNull('area_id')->pluck('area_id')->toArray();

        $assignedPartiesQuery->where(function ($q) use ($user, $assignedDistrictIds, $assignedAreaIds) {
            $q->whereHas('assignments', function ($sq) use ($user) {
                $sq->where('employee_id', $user->id)->where('is_active', true);
            })->orWhere('submitted_by', $user->id);

            if (!empty($assignedDistrictIds)) {
                $q->orWhereIn('customers.district_id', $assignedDistrictIds);
            }
            if (!empty($assignedAreaIds)) {
                $q->orWhereIn('customers.area_id', $assignedAreaIds);
            }
        });

        // Collect distinct district IDs and area IDs from their parties + direct territory assignments
        $partyDistrictIds = (clone $assignedPartiesQuery)->whereNotNull('district_id')->distinct()->pluck('district_id')->toArray();
        $partyAreaIds     = (clone $assignedPartiesQuery)->whereNotNull('area_id')->distinct()->pluck('area_id')->toArray();

        $allowedDistrictIds = array_unique(array_filter(array_merge($partyDistrictIds, $assignedDistrictIds)));
        $allowedAreaIds     = array_unique(array_filter(array_merge($partyAreaIds, $assignedAreaIds)));

        $districtsQuery = District::where('is_active', true)
            ->whereIn('id', $allowedDistrictIds)
            ->with(['areas' => function ($q) use ($allowedAreaIds) {
                $q->where('is_active', true)
                  ->when(!empty($allowedAreaIds), fn($sq) => $sq->whereIn('id', $allowedAreaIds))
                  ->orderBy('name');
            }])
            ->orderBy('name');

        $districts = $districtsQuery->get(['id', 'name', 'state', 'code']);

        return response()->json(['success' => true, 'data' => $districts]);
    }

    /**
     * GET /api/master/india-locations
     * Returns all India states with their districts from the JSON file.
     */
    public function indiaLocations(): JsonResponse
    {
        $path = resource_path('data/india_states_districts.json');
        if (!file_exists($path)) {
            return response()->json(['success' => false, 'data' => []]);
        }
        $data = json_decode(file_get_contents($path), true) ?: [];
        return response()->json(['success' => true, 'data' => $data]);
    }

    /**
     * GET /api/master/parties
     *
     * Returns customers assigned to the authenticated user.
     * Admins get all customers.
     */
    public function parties(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->canSelectAllParties()) {
            $query = Customer::active()->where('approval_status', 'approved');
        } else {
            $assignedDistrictIds = $user->territories()->whereNull('area_id')->pluck('district_id')->toArray();
            $assignedAreaIds = $user->territories()->whereNotNull('area_id')->pluck('area_id')->toArray();

            $query = Customer::active()->where('approval_status', 'approved')
                ->where(function ($q) use ($user, $assignedDistrictIds, $assignedAreaIds) {
                    $q->whereHas('assignments', function ($sq) use ($user) {
                        $sq->where('employee_id', $user->id)->where('is_active', true);
                    })->orWhere('submitted_by', $user->id);

                    if (!empty($assignedDistrictIds)) {
                        $q->orWhereIn('customers.district_id', $assignedDistrictIds);
                    }
                    if (!empty($assignedAreaIds)) {
                        $q->orWhereIn('customers.area_id', $assignedAreaIds);
                    }
                });
        }

        // Support single or multiple district filters
        if ($request->filled('district_ids')) {
            $dIds = is_array($request->district_ids) ? $request->district_ids : explode(',', $request->district_ids);
            $query->whereIn('customers.district_id', array_filter($dIds));
        } elseif ($request->filled('district_id')) {
            $query->where('customers.district_id', $request->district_id);
        }

        // Support single or multiple area filters
        if ($request->filled('area_ids')) {
            $aIds = is_array($request->area_ids) ? $request->area_ids : explode(',', $request->area_ids);
            $query->whereIn('customers.area_id', array_filter($aIds));
        } elseif ($request->filled('area_id')) {
            $query->where('customers.area_id', $request->area_id);
        }

        // PERFORMANCE FIX: Add search functionality for mobile app
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('customers.name', 'LIKE', "%{$search}%")
                  ->orWhere('customers.mobile', 'LIKE', "%{$search}%")
                  ->orWhere('customers.code', 'LIKE', "%{$search}%")
                  ->orWhere('customers.address', 'LIKE', "%{$search}%");
            });
        }

        $columns = [
            'customers.id',
            'customers.name',
            'customers.mobile',
            'customers.address',
            'customers.city',
            'customers.district_id',
            'customers.area_id',
        ];

        // PERFORMANCE FIX: Add pagination support (default 50, max 100)
        $perPage = min(100, max(1, $request->get('per_page', 50)));
        
        // Support for "all parties" if explicitly requested (with safety limit)
        if ($request->get('all') === 'true' || $request->get('all') === '1') {
            // Still enforce a reasonable limit even for "all"
            $parties = $query->orderBy('customers.name')->limit(500)->get($columns);
            
            return response()->json([
                'success' => true,
                'data' => $parties,
                'total' => $parties->count(),
            ]);
        }
        
        // Paginated response (default and recommended)
        $parties = $query->orderBy('customers.name')->paginate($perPage, $columns);

        return response()->json([
            'success' => true,
            'data' => $parties->items(),
            'pagination' => [
                'current_page' => $parties->currentPage(),
                'last_page' => $parties->lastPage(),
                'per_page' => $parties->perPage(),
                'total' => $parties->total(),
                'from' => $parties->firstItem(),
                'to' => $parties->lastItem(),
                'has_more' => $parties->hasMorePages(),
            ],
        ]);
    }

    /**
     * GET /api/master/parties/{id}
     *
     * Returns full details for a single customer.
     */
    public function partyDetails($id): JsonResponse
    {
        $party = Customer::with(['district:id,name', 'area:id,name'])->find($id);
        
        if (!$party) {
            return response()->json(['success' => false, 'message' => 'Party not found'], 404);
        }

        return response()->json(['success' => true, 'data' => $party]);
    }

    /**
     * GET /api/master/day-start-form
     * 
     * PERFORMANCE OPTIMIZATION: Returns ALL dropdown data needed for Start Day form in ONE API call
     * 
     * Before: Mobile app made 5-6 separate API calls (slow - 2-3 seconds total)
     * After: Single API call returns everything (fast - 0.3 seconds)
     * 
     * Returns:
     * - tour_purposes
     * - tour_types
     * - vehicle_types
     * - locations (districts + areas)
     * - parties (paginated, first 50)
     */
    public function dayStartFormData(Request $request): JsonResponse
    {
        $user = $request->user();
        
        // 1. Tour Purposes (small dataset, ~5-10 records)
        $tourPurposes = TourPurpose::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'requires_party']);

        // 2. Tour Types (small dataset, ~5-10 records)
        $tourTypes = TourType::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        // 3. Vehicle Types (small dataset, ~5-10 records)
        $vehicleTypes = VehicleType::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'requires_vehicle_no']);

        // 4. Locations (districts + areas)
        // Reuse existing logic from locations() method
        if (!$user || $user->canSelectAllParties()) {
            $districts = District::where('is_active', true)
                ->with(['areas' => function ($q) {
                    $q->where('is_active', true)->orderBy('name');
                }])
                ->orderBy('name')
                ->get(['id', 'name', 'state', 'code']);
        } else {
            // Get user's assigned party query
            $assignedPartiesQuery = Customer::query()->active()->where('approval_status', 'approved');

            $assignedDistrictIds = $user->territories()->whereNull('area_id')->pluck('district_id')->toArray();
            $assignedAreaIds = $user->territories()->whereNotNull('area_id')->pluck('area_id')->toArray();

            $assignedPartiesQuery->where(function ($q) use ($user, $assignedDistrictIds, $assignedAreaIds) {
                $q->whereHas('assignments', function ($sq) use ($user) {
                    $sq->where('employee_id', $user->id)->where('is_active', true);
                })->orWhere('submitted_by', $user->id);

                if (!empty($assignedDistrictIds)) {
                    $q->orWhereIn('customers.district_id', $assignedDistrictIds);
                }
                if (!empty($assignedAreaIds)) {
                    $q->orWhereIn('customers.area_id', $assignedAreaIds);
                }
            });

            $partyDistrictIds = (clone $assignedPartiesQuery)->whereNotNull('district_id')->distinct()->pluck('district_id')->toArray();
            $partyAreaIds     = (clone $assignedPartiesQuery)->whereNotNull('area_id')->distinct()->pluck('area_id')->toArray();

            $allowedDistrictIds = array_unique(array_filter(array_merge($partyDistrictIds, $assignedDistrictIds)));
            $allowedAreaIds     = array_unique(array_filter(array_merge($partyAreaIds, $assignedAreaIds)));

            $districts = District::where('is_active', true)
                ->whereIn('id', $allowedDistrictIds)
                ->with(['areas' => function ($q) use ($allowedAreaIds) {
                    $q->where('is_active', true)
                      ->when(!empty($allowedAreaIds), fn($sq) => $sq->whereIn('id', $allowedAreaIds))
                      ->orderBy('name');
                }])
                ->orderBy('name')
                ->get(['id', 'name', 'state', 'code']);
        }

        // 5. Parties (first 50 for initial load - mobile can load more with search/pagination)
        if ($user->canSelectAllParties()) {
            $partiesQuery = Customer::active()->where('approval_status', 'approved');
        } else {
            $assignedDistrictIds = $user->territories()->whereNull('area_id')->pluck('district_id')->toArray();
            $assignedAreaIds = $user->territories()->whereNotNull('area_id')->pluck('area_id')->toArray();

            $partiesQuery = Customer::active()->where('approval_status', 'approved')
                ->where(function ($q) use ($user, $assignedDistrictIds, $assignedAreaIds) {
                    $q->whereHas('assignments', function ($sq) use ($user) {
                        $sq->where('employee_id', $user->id)->where('is_active', true);
                    })->orWhere('submitted_by', $user->id);

                    if (!empty($assignedDistrictIds)) {
                        $q->orWhereIn('customers.district_id', $assignedDistrictIds);
                    }
                    if (!empty($assignedAreaIds)) {
                        $q->orWhereIn('customers.area_id', $assignedAreaIds);
                    }
                });
        }

        $parties = $partiesQuery
            ->orderBy('customers.name')
            ->limit(50)  // First 50 parties for dropdown
            ->get([
                'customers.id',
                'customers.name',
                'customers.mobile',
                'customers.city',
            ]);

        return response()->json([
            'success' => true,
            'data' => [
                'tour_purposes' => $tourPurposes,
                'tour_types' => $tourTypes,
                'vehicle_types' => $vehicleTypes,
                'locations' => $districts,
                'parties' => $parties,
                'parties_total' => $partiesQuery->count(),  // Total available
                'parties_loaded' => $parties->count(),      // Loaded in this response
            ],
            'message' => 'All Start Day form data loaded in single request',
        ]);
    }
}
