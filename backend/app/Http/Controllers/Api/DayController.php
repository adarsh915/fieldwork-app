<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DayStart;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

/**
 * Day Controller
 *
 * Handles the full day lifecycle for field workers:
 *   POST /api/day/start  – Submit Day Start form
 *   GET  /api/day/status – Is today's day started? Returns current day_start
 *   POST /api/day/end    – End the day (Day End form)
 */
class DayController extends Controller
{
    /**
     * GET /api/day/status
     *
     * Returns today's day_start for the authenticated user.
     * status: null = not started, 'active' = started, 'ended' = day ended
     */
    public function status(Request $request): JsonResponse
    {
        $today = Carbon::today()->toDateString();
        $user  = $request->user();

        $dayStart = DayStart::where('user_id', $user->id)
            ->where('date', $today)
            ->with(['tourPurpose:id,name', 'tourType:id,name', 'vehicleType:id,name'])
            ->first();

        if (! $dayStart) {
            return response()->json([
                'success'   => true,
                'day_started' => false,
                'data'      => null,
            ]);
        }

        return response()->json([
            'success'     => true,
            'day_started' => true,
            'data'        => $dayStart,
        ]);
    }

    /**
     * POST /api/day/start
     *
     * Submit the Day Start form.
     * Accepts multipart/form-data when photo is included.
     */
    public function start(Request $request): JsonResponse
    {
        $user  = $request->user();
        $today = Carbon::today()->toDateString();

        // Prevent duplicate day start
        $existing = DayStart::where('user_id', $user->id)->where('date', $today)->first();
        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => 'Day already started for today.',
                'data'    => $existing,
            ], 409);
        }

        $validator = Validator::make($request->all(), [
            'tour_purpose_id' => 'required|exists:tour_purposes,id',
            'tour_type_id'    => 'required|exists:tour_types,id',
            'vehicle_type_id' => 'required|exists:vehicle_types,id',
            'vehicle_no'      => 'nullable|string|max:20',
            'opening_km'      => 'nullable|numeric|min:0',
            'opening_km_photo' => 'nullable|image|max:5120', // max 5MB
            'party_ids'       => 'nullable|array',
            'party_ids.*'     => 'exists:customers,id',
            'visit_place'     => 'nullable|string|max:500',
            'start_latitude'  => 'nullable|numeric',
            'start_longitude' => 'nullable|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        // Handle opening KM image upload
        $imagePath = null;
        if ($request->hasFile('opening_km_photo')) {
            $imagePath = $request->file('opening_km_photo')
                ->store('day-starts/km-images', 'public');
        }

        $partyId = $request->filled('party_ids') && count($request->party_ids) > 0 ? $request->party_ids[0] : null;

        $dayStart = DayStart::create([
            'user_id'          => $user->id,
            'date'             => $today,
            'tour_purpose_id'  => $request->tour_purpose_id,
            'tour_type_id'     => $request->tour_type_id,
            'vehicle_type_id'  => $request->vehicle_type_id,
            'vehicle_no'       => $request->vehicle_no,
            'opening_km'       => $request->opening_km,
            'opening_km_photo' => $imagePath,
            'party_id'         => $partyId,
            'visit_place'      => $request->visit_place,
            'start_latitude'   => $request->start_latitude,
            'start_longitude'  => $request->start_longitude,
            'start_time'       => Carbon::now()->toTimeString(),
            'status'           => 'pending',
        ]);

        $dayStart->load(['tourPurpose:id,name', 'tourType:id,name', 'vehicleType:id,name']);

        return response()->json([
            'success' => true,
            'message' => 'Day started successfully!',
            'data'    => $dayStart,
        ], 201);
    }

    /**
     * POST /api/day/end
     *
     * End today's day.
     */
    public function end(Request $request): JsonResponse
    {
        $user  = $request->user();
        $today = Carbon::today()->toDateString();

        $dayStart = DayStart::where('user_id', $user->id)->where('date', $today)->first();

        if (! $dayStart) {
            return response()->json([
                'success' => false,
                'message' => 'No active day found for today.',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'closing_km'       => 'nullable|numeric|min:0',
            'closing_km_photo' => 'nullable|image|max:5120',
            'remarks'          => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors'  => $validator->errors(),
            ], 422);
        }

        // Handle closing KM image
        $closingImagePath = null;
        if ($request->hasFile('closing_km_photo')) {
            $closingImagePath = $request->file('closing_km_photo')
                ->store('day-starts/km-images', 'public');
        }

        $dayStart->update([
            'closing_km'       => $request->closing_km,
            'closing_km_photo' => $closingImagePath,
            'remarks'          => $request->remarks,
            'end_time'         => Carbon::now()->toTimeString(),
            // status can remain pending until approved by admin
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Day ended successfully!',
            'data'    => $dayStart,
        ]);
    }
}
