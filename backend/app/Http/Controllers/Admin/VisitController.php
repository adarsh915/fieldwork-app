<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Visit;
use App\Services\DataScope;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class VisitController extends Controller
{
    /**
     * Build base query with filters and DataScope applied.
     */
    protected function buildVisitQuery(Request $request)
    {
        $query = Visit::visibleToUser()
            ->with([
                'party:id,party_code,name,owner_name,mobile,address,city,state,latitude,longitude',
                'user:id,name,employee_id',
                'user.roles:id,name',
            ])
            ->orderBy('created_at', 'desc');

        $scopedUserIds = DataScope::visibleUserIds('visits');

        if ($request->filled('employee_id')) {
            if ($scopedUserIds === null || in_array($request->employee_id, $scopedUserIds)) {
                $query->where('user_id', $request->employee_id);
            } else {
                $query->whereRaw('1=0');
            }
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('check_in_time', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('check_in_time', '<=', $request->date_to);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('notes', 'like', "%{$search}%")
                  ->orWhere('purpose', 'like', "%{$search}%")
                  ->orWhereHas('party', fn($pq) => $pq->where('name', 'like', "%{$search}%")->orWhere('party_code', 'like', "%{$search}%"))
                  ->orWhereHas('user', fn($uq) => $uq->where('name', 'like', "%{$search}%"));
            });
        }

        return $query;
    }

    /**
     * Calculate distance between two coordinates in meters (Haversine formula).
     */
    protected function calculateDistanceInMeters($lat1, $lon1, $lat2, $lon2): ?float
    {
        if ($lat1 === null || $lon1 === null || $lat2 === null || $lon2 === null) {
            return null;
        }

        $earthRadius = 6371000; // in meters
        $latDelta = deg2rad((float)$lat2 - (float)$lat1);
        $lonDelta = deg2rad((float)$lon2 - (float)$lon1);

        $a = sin($latDelta / 2) * sin($latDelta / 2) +
             cos(deg2rad((float)$lat1)) * cos(deg2rad((float)$lat2)) *
             sin($lonDelta / 2) * sin($lonDelta / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return round($earthRadius * $c);
    }

    /**
     * Format distance in meters or kilometers.
     */
    protected function formatDistance(?float $meters): string
    {
        if ($meters === null) {
            return '—';
        }

        if ($meters < 1000) {
            return round($meters) . ' m';
        }

        return round($meters / 1000, 2) . ' km';
    }

    public function index(Request $request)
    {
        $query = $this->buildVisitQuery($request);

        $visits = $query->paginate(20)->withQueryString();

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'records' => $visits->map(function ($visit) {
                    $distMeters = $this->calculateDistanceInMeters(
                        $visit->check_in_lat,
                        $visit->check_in_lng,
                        $visit->party?->latitude,
                        $visit->party?->longitude
                    );

                    return [
                        'id'             => $visit->id,
                        'party_name'     => $visit->party?->name ?? '—',
                        'party_code'     => $visit->party?->party_code ?? '',
                        'party_lat'      => $visit->party?->latitude,
                        'party_lng'      => $visit->party?->longitude,
                        'user_name'      => $visit->user ? $visit->user->name_with_role : 'Unknown',
                        'check_in_time'  => $visit->check_in_time ? $visit->check_in_time->format('d M Y, h:i A') : 'Pending',
                        'check_in_lat'   => $visit->check_in_lat,
                        'check_in_lng'   => $visit->check_in_lng,
                        'check_out_time' => $visit->check_out_time ? $visit->check_out_time->format('d M Y, h:i A') : '—',
                        'check_out_lat'  => $visit->check_out_lat,
                        'check_out_lng'  => $visit->check_out_lng,
                        'distance'       => $this->formatDistance($distMeters),
                        'status'         => $visit->status,
                        'purpose'        => $visit->purpose ?? '',
                        'notes'          => $visit->notes ?? '',
                        'check_in_photo' => $visit->check_in_photo_url,
                    ];
                }),
                'pagination' => [
                    'current_page' => $visits->currentPage(),
                    'last_page'    => $visits->lastPage(),
                    'per_page'     => $visits->perPage(),
                    'total'        => $visits->total(),
                    'from'         => $visits->firstItem() ?? 0,
                    'to'           => $visits->lastItem() ?? 0,
                ],
            ]);
        }

        $employees = User::visibleToUser()
            ->with('roles')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('admin.visits.index', compact('visits', 'employees'));
    }

    /**
     * Export visits data to Excel (CSV with UTF-8 BOM) matching active filters.
     */
    public function export(Request $request): StreamedResponse
    {
        $user = auth()->user();
        if (!$user->canAny(['visits.view', 'visits.view-team', 'visits.view-all', 'visits.view-own', 'visits.manage']) && !$user->hasRole(['Super Admin', 'Admin'])) {
            abort(403, 'Unauthorized to export visit records.');
        }

        $query = $this->buildVisitQuery($request);

        $filename = 'visits_export_' . date('Y-m-d_His') . '.csv';
        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Pragma'              => 'no-cache',
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            'Expires'             => '0',
        ];

        $callback = function () use ($query) {
            $file = fopen('php://output', 'w');

            // UTF-8 BOM for Microsoft Excel compatibility
            fputs($file, "\xEF\xBB\xBF");

            fputcsv($file, [
                'Visit ID',
                'Date',
                'Field Worker',
                'Employee ID',
                'Designation',
                'Party / Mart Code',
                'Party / Mart Name',
                'Party Owner',
                'Mobile',
                'Address',
                'City',
                'State',
                'Mart Actual Latitude',
                'Mart Actual Longitude',
                'Check-In Time',
                'Check-In Latitude',
                'Check-In Longitude',
                'Distance from Mart',
                'Check-Out Time',
                'Check-Out Latitude',
                'Check-Out Longitude',
                'Duration',
                'Purpose',
                'Status',
                'Notes / Remarks',
            ]);

            $query->chunk(500, function ($visits) use ($file) {
                foreach ($visits as $visit) {
                    $duration = '—';
                    if ($visit->check_in_time && $visit->check_out_time) {
                        $diffMinutes = $visit->check_in_time->diffInMinutes($visit->check_out_time);
                        $hours = floor($diffMinutes / 60);
                        $mins = $diffMinutes % 60;
                        $duration = $hours > 0 ? "{$hours}h {$mins}m" : "{$mins}m";
                    }

                    $distMeters = $this->calculateDistanceInMeters(
                        $visit->check_in_lat,
                        $visit->check_in_lng,
                        $visit->party?->latitude,
                        $visit->party?->longitude
                    );

                    fputcsv($file, [
                        $visit->id,
                        $visit->check_in_time ? $visit->check_in_time->format('Y-m-d') : ($visit->created_at ? $visit->created_at->format('Y-m-d') : ''),
                        $visit->user?->name ?? '—',
                        $visit->user?->employee_id ?? '—',
                        $visit->user?->roles->first()?->name ?? '—',
                        $visit->party?->party_code ?? '',
                        $visit->party?->name ?? '—',
                        $visit->party?->owner_name ?? '—',
                        $visit->party?->mobile ?? '—',
                        $visit->party?->address ?? '—',
                        $visit->party?->city ?? '—',
                        $visit->party?->state ?? '—',
                        $visit->party?->latitude ?? '—',
                        $visit->party?->longitude ?? '—',
                        $visit->check_in_time ? $visit->check_in_time->format('d M Y, h:i A') : 'Pending',
                        $visit->check_in_lat ?? '—',
                        $visit->check_in_lng ?? '—',
                        $this->formatDistance($distMeters),
                        $visit->check_out_time ? $visit->check_out_time->format('d M Y, h:i A') : '—',
                        $visit->check_out_lat ?? '—',
                        $visit->check_out_lng ?? '—',
                        $duration,
                        $visit->purpose ?? '—',
                        ucfirst($visit->status ?? 'pending'),
                        $visit->notes ?? '',
                    ]);
                }
            });

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
