<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\PartyEmployeeMap;
use App\Services\DataScope;
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
     * Returns parties actively assigned to the authenticated user/team.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $scopedUserIds = DataScope::visibleUserIds('parties');

        $query = Customer::query()
            ->active()
            ->where('approval_status', 'approved');

        if ($scopedUserIds !== null) {
            $query->whereHas('assignments', function ($sq) use ($scopedUserIds) {
                $sq->whereIn('employee_id', $scopedUserIds)->where('is_active', true);
            });
        } else {
            $query->whereHas('assignments', function ($sq) {
                $sq->where('is_active', true);
            });
        }

        $parties = $query->orderBy('name')
            ->get(['id', 'name', 'mobile', 'address', 'city', 'approval_status', 'is_active']);

        return response()->json(['success' => true, 'data' => $parties]);
    }

    /**
     * POST /api/parties
     * Field worker creates a new party — auto-approved and directly assigned to the creator.
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
                'approval_status' => 'approved',   // directly approved
                'submitted_by'    => $user->id,
                'user_id'         => $user->id,
            ]);

            // Directly assign to the creator with active status
            $assignment = PartyEmployeeMap::create([
                'party_id'    => $party->id,
                'employee_id' => $user->id,
                'assigned_by' => $user->id,
                'assigned_at' => now(),
                'is_active'   => true, // directly active
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Party created and assigned successfully.',
                'data'    => [
                    'id'              => $party->id,
                    'name'            => $party->name,
                    'mobile'          => $party->mobile,
                    'address'         => $party->address,
                    'city'            => $party->city,
                    'approval_status' => $party->approval_status,
                    'is_active'       => $party->is_active,
                ],
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Failed to create party: ' . $e->getMessage()], 500);
        }
    }
}
