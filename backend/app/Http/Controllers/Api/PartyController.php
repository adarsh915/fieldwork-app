<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\PartyEmployeeMap;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/**
 * API Party Controller
 *
 * Field workers can submit new party requests (approval_status = pending).
 * Admin approves via the web panel.
 */
class PartyController extends Controller
{
    /**
     * GET /api/parties
     * Returns parties assigned to the authenticated field worker.
     * Includes pending ones they submitted themselves.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        // Get approved assigned parties + their own pending submissions
        $assigned = $user->parties()
            ->where('customers.approval_status', 'approved')
            ->orderBy('customers.name')
            ->get(['customers.id', 'customers.name', 'customers.mobile',
                   'customers.address', 'customers.city', 'customers.approval_status']);

        $pending = Customer::where('submitted_by', $user->id)
            ->where('approval_status', 'pending')
            ->orderBy('name')
            ->get(['id', 'name', 'mobile', 'address', 'city', 'approval_status']);

        $all = $assigned->merge($pending)->unique('id')->values();

        return response()->json(['success' => true, 'data' => $all]);
    }

    /**
     * POST /api/parties
     * Field worker submits a new party request.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name'       => 'required|string|max:255',
            'mobile'     => 'required|string|max:15',
            'party_type' => 'nullable|string|max:50',
            'owner_name' => 'nullable|string|max:255',
            'address'    => 'nullable|string',
            'city'       => 'nullable|string|max:100',
            'state'      => 'nullable|string|max:100',
            'pincode'    => 'nullable|string|max:10',
            'email'      => 'nullable|email|max:255',
            'gst_number' => 'nullable|string|max:20',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $user = $request->user();

        DB::beginTransaction();
        try {
            $party = Customer::create([
                'name'            => $request->name,
                'mobile'          => $request->mobile,
                'party_type'      => $request->party_type ?? 'Retailer',
                'owner_name'      => $request->owner_name,
                'address'         => $request->address,
                'city'            => $request->city,
                'state'           => $request->state,
                'pincode'         => $request->pincode,
                'email'           => $request->email,
                'is_active'       => true,
                'approval_status' => 'pending',   // requires admin approval
                'submitted_by'    => $user->id,
                'user_id'         => $user->id,   // tentative assignment
            ]);

            // Pre-assign to the field worker who submitted (active after approval)
            PartyEmployeeMap::create([
                'party_id'    => $party->id,
                'employee_id' => $user->id,
                'assigned_by' => $user->id,
                'is_active'   => false, // becomes true after admin approves
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Party submitted for approval.',
                'data'    => $party->only(['id', 'name', 'mobile', 'approval_status']),
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Failed to submit party.'], 500);
        }
    }
}
