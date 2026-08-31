<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
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
     * GET /api/master/parties
     *
     * Returns customers assigned to the authenticated user.
     * Admins get all customers.
     */
    public function parties(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->canSelectAllParties()) {
            $parties = Customer::active()->orderBy('name')->get(['id', 'name', 'mobile', 'address']);
        } else {
            $parties = $user->parties()->active()->orderBy('name')->get(['customers.id', 'customers.name', 'customers.mobile', 'customers.address']);
        }

        return response()->json(['success' => true, 'data' => $parties]);
    }

    /**
     * GET /api/master/parties/{id}
     *
     * Returns full details for a single customer.
     */
    public function partyDetails($id): JsonResponse
    {
        $party = Customer::find($id);
        
        if (!$party) {
            return response()->json(['success' => false, 'message' => 'Party not found'], 404);
        }

        return response()->json(['success' => true, 'data' => $party]);
    }
}
