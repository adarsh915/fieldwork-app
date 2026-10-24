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
     */
    public function locations(): JsonResponse
    {
        $districts = District::where('is_active', true)
            ->with(['areas' => function ($q) {
                $q->where('is_active', true)->orderBy('name');
            }])
            ->orderBy('name')
            ->get(['id', 'name', 'state', 'code']);

        return response()->json(['success' => true, 'data' => $districts]);
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

        $columns = [
            'customers.id',
            'customers.name',
            'customers.mobile',
            'customers.address',
            'customers.city',
            'customers.district_id',
            'customers.area_id',
        ];

        $parties = $query->orderBy('customers.name')->get($columns);

        return response()->json(['success' => true, 'data' => $parties]);
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
}
