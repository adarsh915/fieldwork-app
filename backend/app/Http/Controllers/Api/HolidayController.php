<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreHolidayRequest;
use App\Models\Holiday;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HolidayController extends Controller
{
    /**
     * GET /api/holidays?year=2026
     * All holidays for a year (available to all authenticated users).
     */
    public function index(Request $request): JsonResponse
    {
        $year = (int) $request->input('year', now()->year);

        $holidays = Holiday::forYear($year)
            ->orderBy('date')
            ->get(['id', 'date', 'name', 'type']);

        return response()->json(['success' => true, 'data' => $holidays]);
    }

    /**
     * POST /api/admin/holidays
     * Declare a new holiday (admin only).
     */
    public function store(StoreHolidayRequest $request): JsonResponse
    {
        $holiday = Holiday::create([
            'date'       => $request->date,
            'name'       => $request->name,
            'type'       => $request->input('type', 'company'),
            'created_by' => $request->user()->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Holiday added successfully.',
            'data'    => $holiday,
        ], 201);
    }

    /**
     * DELETE /api/admin/holidays/{id}
     * Remove a holiday (admin only).
     */
    public function destroy(int $id): JsonResponse
    {
        $holiday = Holiday::find($id);

        if (! $holiday) {
            return response()->json(['success' => false, 'message' => 'Holiday not found.'], 404);
        }

        $holiday->delete();

        return response()->json(['success' => true, 'message' => 'Holiday removed.']);
    }
}
