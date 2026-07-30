<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\DayStart;
use App\Models\Visit;
use Carbon\Carbon;
use Illuminate\Http\Request;

class VisitController extends Controller
{
    /**
     * Get active visit for the day or list of visits.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        
        // Ensure a day is started
        $dayStart = DayStart::where('user_id', $user->id)
            ->whereDate('date', Carbon::today())
            ->first();

        if (!$dayStart) {
            return response()->json(['success' => false, 'message' => 'Day not started.']);
        }

        // Return today's visits
        $visits = Visit::where('day_start_id', $dayStart->id)
            ->with('party')
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $visits,
        ]);
    }

    /**
     * Get active visit for a specific party.
     */
    public function status(Request $request, $partyId)
    {
        $user = $request->user();
        $dayStart = DayStart::where('user_id', $user->id)
            ->whereDate('date', Carbon::today())
            ->first();

        if (!$dayStart) {
            return response()->json(['success' => false, 'message' => 'Day not started.']);
        }

        // Check if there's an ongoing visit for this party
        $activeVisit = Visit::where('day_start_id', $dayStart->id)
            ->where('party_id', $partyId)
            ->where('status', 'pending')
            ->first();

        // Also check if there's ANY active visit that is not this party
        $otherActiveVisit = Visit::where('day_start_id', $dayStart->id)
            ->where('status', 'pending')
            ->where('party_id', '!=', $partyId)
            ->first();
                
         return response()->json([
            'success' => true,
            'active_visit' => $activeVisit,
            'has_other_active_visit' => (bool)$otherActiveVisit,
        ]);
    }

    /**
     * Check-in to a party.
     */
    public function checkIn(Request $request)
    {
        $request->validate([
            'party_id' => 'required|exists:customers,id',
            'lat' => 'nullable|numeric',
            'lng' => 'nullable|numeric',
        ]);

        $user = $request->user();
        
        $dayStart = DayStart::where('user_id', $user->id)
            ->whereDate('date', Carbon::today())
            ->first();

        if (!$dayStart) {
            return response()->json(['success' => false, 'message' => 'You must start your day first.'], 400);
        }

        if ($dayStart->day_end_at) {
            return response()->json(['success' => false, 'message' => 'Your day has already ended.'], 400);
        }

        // Ensure user doesn't already have an active visit
        $activeVisit = Visit::where('day_start_id', $dayStart->id)
            ->where('status', 'pending')
            ->first();

        if ($activeVisit) {
            return response()->json(['success' => false, 'message' => 'You already have an ongoing visit.'], 400);
        }

        $customer = Customer::findOrFail($request->party_id);

        if ($request->lat && $request->lng) {
            if (!$customer->latitude || !$customer->longitude) {
                // First visit: Auto-capture location
                $customer->update([
                    'latitude' => $request->lat,
                    'longitude' => $request->lng,
                ]);
            } else {
                // Future visits: Enforce 100-meter geo-fence
                $distance = $this->getDistanceInMeters($request->lat, $request->lng, $customer->latitude, $customer->longitude);
                if ($distance > 100) {
                    return response()->json(['success' => false, 'message' => 'You must be within 100 meters of the party to check in.'], 403);
                }
            }
        }

        // Handle check_in_photo upload
        $imagePath = null;
        if ($request->hasFile('check_in_photo')) {
            $imagePath = $request->file('check_in_photo')->store('visits/check-in-photos', 'public');
        }

        $visit = Visit::create([
            'user_id' => $user->id,
            'day_start_id' => $dayStart->id,
            'party_id' => $request->party_id,
            'check_in_time' => Carbon::now(),
            'check_in_lat' => $request->lat,
            'check_in_lng' => $request->lng,
            'check_in_photo' => $imagePath,
            'status' => 'pending',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Checked in successfully.',
            'data' => $visit,
        ]);
    }

    /**
     * Check-out from a party.
     */
    public function checkOut(Request $request, $id)
    {
        $request->validate([
            'lat' => 'nullable|numeric',
            'lng' => 'nullable|numeric',
            'remarks' => 'nullable|string',
        ]);

        $user = $request->user();
        
        $visit = Visit::where('id', $id)->where('user_id', $user->id)->firstOrFail();

        if ($visit->status === 'completed') {
            return response()->json(['success' => false, 'message' => 'Visit is already completed.'], 400);
        }

        $visit->update([
            'check_out_time' => Carbon::now(),
            'check_out_lat' => $request->lat,
            'check_out_lng' => $request->lng,
            'notes' => $request->remarks,
            'status' => 'completed',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Checked out successfully.',
            'data' => $visit,
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
