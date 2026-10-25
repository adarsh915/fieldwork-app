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

        $query = Customer::query()
            ->active()
            ->where('approval_status', 'approved');

        if (!$user->canSelectAllParties()) {
            $assignedDistrictIds = $user->territories()->whereNull('area_id')->pluck('district_id')->toArray();
            $assignedAreaIds = $user->territories()->whereNotNull('area_id')->pluck('area_id')->toArray();

            $query->where(function ($q) use ($user, $assignedDistrictIds, $assignedAreaIds) {
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

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('mobile', 'LIKE', "%{$search}%")
                  ->orWhere('city', 'LIKE', "%{$search}%");
            });
        }

        if ($request->filled('district_ids')) {
            $dIds = is_array($request->district_ids) ? $request->district_ids : explode(',', $request->district_ids);
            $query->whereIn('customers.district_id', array_filter($dIds));
        } elseif ($request->filled('district_id')) {
            $query->where('customers.district_id', $request->district_id);
        }

        if ($request->filled('area_ids')) {
            $aIds = is_array($request->area_ids) ? $request->area_ids : explode(',', $request->area_ids);
            $query->whereIn('customers.area_id', array_filter($aIds));
        } elseif ($request->filled('area_id')) {
            $query->where('customers.area_id', $request->area_id);
        }

        $columns = ['id', 'name', 'mobile', 'address', 'city', 'district_id', 'area_id', 'approval_status', 'is_active'];

        if ($request->has('page')) {
            $perPage = max(1, min(100, (int) $request->input('per_page', 20)));
            $paginated = $query->orderBy('name')->paginate($perPage, $columns);

            return response()->json([
                'success'      => true,
                'data'         => $paginated->items(),
                'current_page' => $paginated->currentPage(),
                'last_page'    => $paginated->lastPage(),
                'total'        => $paginated->total(),
                'has_more'     => $paginated->hasMorePages(),
            ]);
        }

        $parties = $query->orderBy('name')->get($columns);

        return response()->json(['success' => true, 'data' => $parties]);
    }

    /**
     * POST /api/parties
     * Field worker creates a new party — auto-approved and directly assigned to the creator.
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'name'        => 'required|string|max:255',
            'mobile'      => 'required|string|max:15',
            'party_type'  => 'nullable|string|max:50',
            'owner_name'  => 'nullable|string|max:255',
            'district_id' => 'nullable|exists:districts,id',
            'area_id'     => 'nullable|exists:areas,id',
            'address'     => 'nullable|string',
            'city'        => 'nullable|string|max:100',
            'state'       => 'nullable|string|max:100',
            'pincode'     => 'nullable|string|max:10',
            'email'       => 'nullable|email|max:255',
            'gst_number'  => 'nullable|string|max:20',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $user = $request->user();

        $city = $request->city;
        $state = $request->state;
        if ($request->filled('district_id')) {
            $district = \App\Models\District::find($request->district_id);
            if ($district && empty($state)) {
                $state = $district->state;
            }
            if ($district && empty($city)) {
                $city = $district->name;
            }
        }
        if ($request->filled('area_id')) {
            $area = \App\Models\Area::find($request->area_id);
            if ($area && empty($city)) {
                $city = $area->name;
            }
            if ($area && empty($request->pincode) && !empty($area->pincode)) {
                $pincode = $area->pincode;
            }
        }

        DB::beginTransaction();
        try {
            $party = Customer::create([
                'name'            => $request->name,
                'mobile'          => $request->mobile,
                'party_type'      => $request->party_type ?? 'Retailer',
                'owner_name'      => $request->owner_name,
                'district_id'     => $request->district_id,
                'area_id'         => $request->area_id,
                'address'         => $request->address,
                'city'            => $city,
                'state'           => $state,
                'pincode'         => $pincode ?? $request->pincode,
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
                    'district_id'     => $party->district_id,
                    'area_id'         => $party->area_id,
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
