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
use Spatie\Permission\Models\Role;

class FuelRateWebController extends Controller
{
    public function __construct(
        protected FuelRateResolverService $fuelRateService
    ) {}

    /**
     * Show Fuel & Petrol Allowance Rates Management UI
     */
    public function index(Request $request)
    {
        $date = $request->query('date', Carbon::today()->toDateString());
        
        $vehicleTypes = VehicleType::where('is_active', true)->orderBy('name')->get();
        $roles = Role::orderBy('name')->get();
        $users = User::where('is_active', true)->orderBy('name')->get(['id', 'name', 'employee_id']);

        $allRates = FuelAllowanceRate::with(['vehicleType', 'user:id,name,employee_id', 'role:id,name'])
            ->orderByDesc('effective_from')
            ->orderByDesc('id')
            ->get();

        $roleRates = $allRates->where('scope_type', 'role')->values();
        $userOverrides = $allRates->where('scope_type', 'user')->values();

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success'        => true,
                'vehicle_types'  => $vehicleTypes,
                'role_rates'     => $roleRates,
                'user_overrides' => $userOverrides,
                'roles'          => $roles,
                'users'          => $users,
                'current_date'   => $date,
            ]);
        }

        return view('admin.fuel-rates.index', compact(
            'vehicleTypes',
            'roleRates',
            'userOverrides',
            'roles',
            'users',
            'date'
        ));
    }

    /**
     * Store or update rate via AJAX
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'id'              => 'nullable|integer|exists:fuel_allowance_rates,id',
            'vehicle_type_id' => 'required|exists:vehicle_types,id',
            'scope_type'      => 'required|in:global,role,user',
            'scope_id'        => 'required_if:scope_type,role,user|nullable|integer',
            'rate_per_km'     => 'required|numeric|min:0|max:999.99',
            'effective_from'  => 'nullable|date',
            'effective_to'    => 'nullable|date|after_or_equal:effective_from',
            'notes'           => 'nullable|string|max:500',
        ]);

        if ($validated['scope_type'] === 'global') {
            $validated['scope_id'] = null;
        }

        $rate = $this->fuelRateService->setRate($validated);

        if ($request->ajax() || $request->wantsJson()) {
            $allRates = FuelAllowanceRate::with(['vehicleType', 'user:id,name,employee_id', 'role:id,name'])
                ->orderByDesc('effective_from')
                ->orderByDesc('id')
                ->get();

            return response()->json([
                'success'        => true,
                'message'        => 'Fuel allowance rate configured successfully.',
                'data'           => $rate->load(['vehicleType', 'user', 'role']),
                'role_rates'     => $allRates->where('scope_type', 'role')->values(),
                'user_overrides' => $allRates->where('scope_type', 'user')->values(),
            ]);
        }

        return redirect()->route('admin.fuel-rates.index')->with('success', 'Fuel allowance rate saved successfully.');
    }

    /**
     * Delete / remove rate override via AJAX
     */
    public function destroy(Request $request, int $id)
    {
        $this->fuelRateService->deleteRate($id);

        if ($request->ajax() || $request->wantsJson()) {
            $allRates = FuelAllowanceRate::with(['vehicleType', 'user:id,name,employee_id', 'role:id,name'])
                ->orderByDesc('effective_from')
                ->orderByDesc('id')
                ->get();

            return response()->json([
                'success'        => true,
                'message'        => 'Fuel allowance override removed successfully.',
                'role_rates'     => $allRates->where('scope_type', 'role')->values(),
                'user_overrides' => $allRates->where('scope_type', 'user')->values(),
            ]);
        }

        return redirect()->route('admin.fuel-rates.index')->with('success', 'Rate override removed successfully.');
    }

    /**
     * Live test rate resolver
     */
    public function resolve(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id'            => 'required|exists:users,id',
            'vehicle_identifier' => 'required',
            'date'               => 'nullable|date',
            'distance_km'        => 'nullable|numeric|min:0',
        ]);

        $userId = (int) $validated['user_id'];
        $vehicleIdentifier = $validated['vehicle_identifier'];
        $date = $validated['date'] ?? Carbon::today()->toDateString();
        $distanceKm = isset($validated['distance_km']) ? (float)$validated['distance_km'] : null;

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
