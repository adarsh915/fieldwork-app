<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DayStart;
use App\Models\ExpenseBill;
use App\Models\GpsTrackingLog;
use App\Models\VehicleType;
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

        // --- Fraud Prevention & Automated Expense Generation ---
        if ($request->closing_km && $dayStart->opening_km) {
            $claimedKm = $request->closing_km - $dayStart->opening_km;
            $gpsDistance = 0;

            // Calculate GPS Distance
            $logs = GpsTrackingLog::where('user_id', $user->id)
                ->whereDate('created_at', $today)
                ->orderBy('created_at', 'asc')
                ->get();
            
            if ($logs->count() > 1) {
                for ($i = 1; $i < $logs->count(); $i++) {
                    $gpsDistance += $this->getDistanceInMeters(
                        $logs[$i - 1]->latitude, $logs[$i - 1]->longitude,
                        $logs[$i]->latitude, $logs[$i]->longitude
                    );
                }
            }
            
            $gpsKm = $gpsDistance / 1000;
            $dayStart->gps_calculated_km = $gpsKm;
            
            // Check Deviation (> 20%)
            if ($claimedKm > 0 && $gpsKm > 0) {
                $deviation = abs($claimedKm - $gpsKm) / $claimedKm;
                if ($deviation > 0.20) {
                    $dayStart->flagged_reason = "Suspicious KM: Claimed {$claimedKm}KM, but GPS calculated {$gpsKm}KM.";
                }
            }
            $dayStart->save();

            // Generate Travel Expense
            if ($dayStart->vehicle_type_id) {
                $vehicle = VehicleType::find($dayStart->vehicle_type_id);
                if ($vehicle && $vehicle->rate_per_km > 0) {
                    $expenseAmount = $claimedKm * $vehicle->rate_per_km;
                    
                    if ($expenseAmount > 0) {
                        $travelCategory = \App\Models\ExpenseCategory::where('name', 'Travel')->first();
                        ExpenseBill::create([
                            'user_id' => $user->id,
                            'day_start_id' => $dayStart->id,
                            'date' => $today,
                            'amount' => $expenseAmount,
                            'status' => 'pending', // Admin must approve manually after checking meter photo
                            'is_system_generated' => true,
                            'category_id' => $travelCategory ? $travelCategory->id : null,
                        ]);
                    }
                }
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Day ended successfully!',
            'data'    => $dayStart,
        ]);
    }

    /**
     * Calculate distance between two points in meters using Haversine formula.
     */
    private function getDistanceInMeters($lat1, $lon1, $lat2, $lon2)
    {
        $earthRadius = 6371000; // meters

        $latDelta = deg2rad($lat2 - $lat1);
        $lonDelta = deg2rad($lon2 - $lon1);

        $a = sin($latDelta / 2) * sin($latDelta / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($lonDelta / 2) * sin($lonDelta / 2);
             
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }
}
