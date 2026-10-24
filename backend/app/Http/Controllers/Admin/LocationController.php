<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Area;
use App\Models\District;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LocationController extends Controller
{
    /**
     * Display districts and areas management.
     */
    public function index(Request $request)
    {
        $districts = District::with(['areas' => function ($q) {
            $q->orderBy('name');
        }])
        ->withCount(['areas', 'customers', 'dayStarts'])
        ->orderBy('name')
        ->get();

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'districts' => $districts,
            ]);
        }

        return view('admin.locations.index', [
            'districts' => $districts,
        ]);
    }

    /**
     * Store a newly created district.
     */
    public function storeDistrict(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'      => ['required', 'string', 'max:100', 'unique:districts,name'],
            'state'     => ['nullable', 'string', 'max:100'],
            'code'      => ['nullable', 'string', 'max:20', 'unique:districts,code'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated['state'] = $validated['state'] ?? 'Gujarat';
        $validated['is_active'] = $request->boolean('is_active', true);

        $district = District::create($validated);

        return response()->json([
            'success' => true,
            'message' => "District '{$district->name}' created successfully.",
            'district' => $district->loadCount(['areas', 'customers', 'dayStarts']),
        ], 201);
    }

    /**
     * Update an existing district.
     */
    public function updateDistrict(Request $request, District $district): JsonResponse
    {
        $validated = $request->validate([
            'name'      => ['required', 'string', 'max:100', Rule::unique('districts', 'name')->ignore($district->id)],
            'state'     => ['nullable', 'string', 'max:100'],
            'code'      => ['nullable', 'string', 'max:20', Rule::unique('districts', 'code')->ignore($district->id)],
            'is_active' => ['nullable', 'boolean'],
        ]);

        if ($request->has('is_active')) {
            $validated['is_active'] = $request->boolean('is_active');
        }

        $district->update($validated);

        return response()->json([
            'success' => true,
            'message' => "District '{$district->name}' updated successfully.",
            'district' => $district->fresh()->loadCount(['areas', 'customers', 'dayStarts']),
        ]);
    }

    /**
     * Delete or deactivate a district.
     */
    public function destroyDistrict(District $district): JsonResponse
    {
        if ($district->customers()->count() > 0 || $district->dayStarts()->count() > 0) {
            $district->update(['is_active' => false]);
            return response()->json([
                'success' => true,
                'message' => "District has linked records, so it was set to Inactive instead of deleted.",
            ]);
        }

        $district->areas()->delete();
        $district->delete();

        return response()->json([
            'success' => true,
            'message' => "District deleted successfully.",
        ]);
    }

    /**
     * Store a newly created area under a district.
     */
    public function storeArea(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'district_id' => ['required', 'exists:districts,id'],
            'name'        => ['required', 'string', 'max:150'],
            'pincode'     => ['nullable', 'string', 'max:10'],
            'is_active'   => ['nullable', 'boolean'],
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);

        // Prevent duplicate area in same district
        $exists = Area::where('district_id', $validated['district_id'])
            ->where('name', $validated['name'])
            ->exists();

        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'An area with this name already exists in the selected district.',
            ], 422);
        }

        $area = Area::create($validated);

        return response()->json([
            'success' => true,
            'message' => "Area '{$area->name}' added successfully.",
            'area' => $area,
        ], 201);
    }

    /**
     * Update an area.
     */
    public function updateArea(Request $request, Area $area): JsonResponse
    {
        $validated = $request->validate([
            'district_id' => ['sometimes', 'required', 'exists:districts,id'],
            'name'        => ['sometimes', 'required', 'string', 'max:150'],
            'pincode'     => ['nullable', 'string', 'max:10'],
            'is_active'   => ['nullable', 'boolean'],
        ]);

        if ($request->has('is_active')) {
            $validated['is_active'] = $request->boolean('is_active');
        }

        $districtId = $validated['district_id'] ?? $area->district_id;
        $name = $validated['name'] ?? $area->name;

        $exists = Area::where('district_id', $districtId)
            ->where('name', $name)
            ->where('id', '!=', $area->id)
            ->exists();

        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'An area with this name already exists in the selected district.',
            ], 422);
        }

        $area->update($validated);

        return response()->json([
            'success' => true,
            'message' => "Area '{$area->name}' updated successfully.",
            'area' => $area->fresh(),
        ]);
    }

    /**
     * Delete or deactivate an area.
     */
    public function destroyArea(Area $area): JsonResponse
    {
        if ($area->customers()->count() > 0 || $area->dayStarts()->count() > 0) {
            $area->update(['is_active' => false]);
            return response()->json([
                'success' => true,
                'message' => "Area has linked parties or day-starts, so it was set to Inactive instead of deleted.",
            ]);
        }

        $area->delete();

        return response()->json([
            'success' => true,
            'message' => "Area deleted successfully.",
        ]);
    }
}
