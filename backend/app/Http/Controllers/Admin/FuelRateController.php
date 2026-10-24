<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FuelAllowanceRate;
use App\Models\User;
use App\Models\VehicleType;
use App\Services\FuelRateResolverService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Role;

class FuelRateController extends Controller
{
    public function __construct(
        protected FuelRateResolverService $fuelRateService
    ) {}

    /**
     * GET /api/admin/fuel-rates
     * 
     * List all vehicle types, global rates, role rates, and individual user overrides.
     */
    public function index(Request $request): JsonResponse
    {
        $date = $request->query('date', Carbon::today()->toDateString());
        $activeOnly = $request->boolean('active_only', false);

        // 1. Vehicle Types
        $vehicleTypes = VehicleType::where('is_active', true)->orderBy('name')->get();

        // 2. Base Query
        $ratesQuery = FuelAllowanceRate::with(['vehicleType', 'user:id,name,employee_id', 'role:id,name'])
            ->when($activeOnly, fn($q) => $q->where('is_active', true))
            ->when($request->filled('vehicle_type_id'), fn($q) => $q->where('vehicle_type_id', $request->vehicle_type_id))
            ->orderByDesc('effective_from')
            ->orderByDesc('id');

        $allRates = $ratesQuery->get();

        // Group rates by scope
        $globalRates = $allRates->where('scope_type', 'global')->values();
        $roleRates = $allRates->where('scope_type', 'role')->values();
        $userOverrides = $allRates->where('scope_type', 'user')->values();

        // Dropdowns for UI
        $roles = Role::orderBy('name')->get(['id', 'name']);
        $users = User::where('is_active', true)->orderBy('name')->get(['id', 'name', 'employee_id']);

        return response()->json([
            'success' => true,
            'data' => [
                'vehicle_types'   => $vehicleTypes,
                'global_rates'    => $globalRates,
                'role_rates'      => $roleRates,
                'user_overrides'  => $userOverrides,
                'available_roles' => $roles,
                'available_users' => $users,
                'current_date'    => $date,
            ],
        ]);
    }

    /**
     * POST /api/admin/fuel-rates
     * 
     * Create or update fuel allowance rate with automatic safe date archiving.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'vehicle_type_id' => 'required|exists:vehicle_types,id',
            'scope_type'      => 'required|in:global,role,user',
            'scope_id'        => 'required_if:scope_type,role,user|nullable|integer',
            'rate_per_km'     => 'required|numeric|min:0|max:999.99',
            'effective_from'  => 'nullable|date',
            'effective_to'    => 'nullable|date|after_or_equal:effective_from',
            'is_active'       => 'nullable|boolean',
            'notes'           => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();

        // Additional scope_id validation based on scope_type
        if ($validated['scope_type'] === 'role') {
            if (!Role::where('id', $validated['scope_id'])->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Specified role does not exist.',
                ], 422);
            }
        } elseif ($validated['scope_type'] === 'user') {
            if (!User::where('id', $validated['scope_id'])->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Specified employee user does not exist.',
                ], 422);
            }
        } else {
            $validated['scope_id'] = null;
        }

        $rate = $this->fuelRateService->setRate($validated);
        $rate->load(['vehicleType', 'user:id,name,employee_id', 'role:id,name']);

        return response()->json([
            'success' => true,
            'message' => 'Fuel allowance rate configured successfully.',
            'data'    => $rate,
        ], 201);
    }

    /**
     * DELETE /api/admin/fuel-rates/{id}
     * 
     * Delete or deactivate rate override.
     */
    public function destroy(int $id): JsonResponse
    {
        $rate = FuelAllowanceRate::find($id);

        if (!$rate) {
            return response()->json([
                'success' => false,
                'message' => 'Fuel rate record not found.',
            ], 404);
        }

        $this->fuelRateService->deleteRate($id);

        return response()->json([
            'success' => true,
            'message' => 'Fuel rate override removed successfully. Fallback hierarchy is now active.',
        ]);
    }

    /**
     * POST /api/admin/fuel-rates/resolve
     * 
     * Preview / verify rate resolution hierarchy for any user, vehicle, and date.
     */
    public function resolve(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'user_id'            => 'required|exists:users,id',
            'vehicle_identifier' => 'required',
            'date'               => 'nullable|date',
            'distance_km'        => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors'  => $validator->errors(),
            ], 422);
        }

        $userId = (int) $request->user_id;
        $vehicleIdentifier = $request->vehicle_identifier;
        $date = $request->date ?: Carbon::today()->toDateString();
        $distanceKm = $request->filled('distance_km') ? (float)$request->distance_km : null;

        if ($distanceKm !== null) {
            $result = $this->fuelRateService->calculateTripAllowance($userId, $vehicleIdentifier, $distanceKm, $date);
        } else {
            $result = $this->fuelRateService->getEffectiveRate($userId, $vehicleIdentifier, $date);
        }

        return response()->json([
            'success' => true,
            'data'    => $result,
        ]);
    }
}
