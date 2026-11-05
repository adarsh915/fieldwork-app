# Visit Records Excel Export & GPS Location Tracking - Code Changes

This document records the exact changes made to implement the **Export to Excel** feature with active filter support, as well as **Mart Actual Coordinates (`latitude`, `longitude`)**, **Check-in/Check-out Coordinates**, and **Distance Calculation** on the **Visits** page in the Admin Panel.

---

## Summary of Affected Files

| # | File Path | Type | Description |
|---|-----------|------|-------------|
| 1 | [backend/routes/web.php](file:///d:/all_project/filed-work-app/AMPL-Fieldwork/backend/routes/web.php) | Route Definition | Registered the `admin.visits.export` route with permission guards. |
| 2 | [backend/app/Http/Controllers/Admin/VisitController.php](file:///d:/all_project/filed-work-app/AMPL-Fieldwork/backend/app/Http/Controllers/Admin/VisitController.php) | Controller | Extracted shared query builder (`buildVisitQuery`), added Mart coordinates (`latitude`, `longitude`), Haversine distance formula, and chunked CSV/Excel streaming method (`export`). |
| 3 | [backend/resources/views/admin/visits/index.blade.php](file:///d:/all_project/filed-work-app/AMPL-Fieldwork/backend/resources/views/admin/visits/index.blade.php) | Blade View | Added "Export Excel" button, reactive Alpine.js URL generator `exportUrl()`, and table badges displaying Mart actual coordinates and Check-in/Check-out GPS with distance. |

---

## 1. Routes: `backend/routes/web.php`

### Old Code:
```php
        // ── Visits ────────────────────────────────────────────────────────────
        Route::get('/visits', [\App\Http\Controllers\Admin\VisitController::class, 'index'])->middleware('permission:visits.view|visits.view-team|visits.view-all|visits.view-own|visits.manage')->name('visits.index');
```

### Updated Code:
```php
        // ── Visits ────────────────────────────────────────────────────────────
        Route::get('/visits/export', [\App\Http\Controllers\Admin\VisitController::class, 'export'])->middleware('permission:visits.view|visits.view-team|visits.view-all|visits.view-own|visits.manage')->name('visits.export');
        Route::get('/visits', [\App\Http\Controllers\Admin\VisitController::class, 'index'])->middleware('permission:visits.view|visits.view-team|visits.view-all|visits.view-own|visits.manage')->name('visits.index');
```

---

## 2. Controller: `backend/app/Http/Controllers/Admin/VisitController.php`

### Old Code:
```php
<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Visit;
use App\Services\DataScope;
use Illuminate\Http\Request;

class VisitController extends Controller
{
    public function index(Request $request)
    {
        $query = Visit::visibleToUser()
            ->with(['party', 'user.roles'])
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
                  ->orWhereHas('party', fn($pq) => $pq->where('name', 'like', "%{$search}%")->orWhere('code', 'like', "%{$search}%"))
                  ->orWhereHas('user', fn($uq) => $uq->where('name', 'like', "%{$search}%"));
            });
        }

        $visits = $query->paginate(20)->withQueryString();

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'records' => $visits->map(function ($visit) {
                    return [
                        'id'             => $visit->id,
                        'party_name'     => $visit->party?->name ?? '—',
                        'party_code'     => $visit->party?->party_code ?? $visit->party?->code ?? '',
                        'user_name'      => $visit->user ? $visit->user->name_with_role : 'Unknown',
                        'check_in_time'  => $visit->check_in_time ? $visit->check_in_time->format('d M Y, h:i A') : 'Pending',
                        'check_out_time' => $visit->check_out_time ? $visit->check_out_time->format('d M Y, h:i A') : '—',
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
}
```

### Updated Code:
```php
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
```

---

## 3. Blade View: `backend/resources/views/admin/visits/index.blade.php`

### Old Code (Alpine Data & Table Columns):
```blade
    records: {{ Js::from($visits->map(fn($visit) => [
        'id'             => $visit->id,
        'party_name'     => $visit->party?->name ?? '—',
        'party_code'     => $visit->party?->party_code ?? $visit->party?->code ?? '',
        'user_name'      => $visit->user?->name ?? 'Unknown',
        'check_in_time'  => $visit->check_in_time ? $visit->check_in_time->format('d M Y, h:i A') : 'Pending',
        'check_out_time' => $visit->check_out_time ? $visit->check_out_time->format('d M Y, h:i A') : '—',
        'status'         => $visit->status,
        'purpose'        => $visit->purpose ?? '',
        'notes'          => $visit->notes ?? '',
        'check_in_photo' => $visit->check_in_photo_url,
    ])) }},
...
    {{-- Header --}}
    <div class="panel-header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-section-title">Visit Records</h2>
                <p class="text-caption mt-1">Real-time dealer and store check-ins logged by field workers.</p>
            </div>
        </div>
...
    <tbody>
        <template x-for="visit in records" :key="visit.id">
            <tr class="hover:bg-surface-muted/60 transition-colors">
                <td>
                    <div class="font-semibold text-ink-900" x-text="visit.party_name"></div>
                    <template x-if="visit.party_code">
                        <div class="text-xs text-ink-400 font-mono" x-text="visit.party_code"></div>
                    </template>
                </td>
                <td class="text-body font-medium" x-text="visit.user_name"></td>
                <td class="text-caption" x-text="visit.check_in_time"></td>
                <td class="text-caption" x-text="visit.check_out_time"></td>
```

### Updated Code (Alpine Data & Table Columns):
```blade
    records: {{ Js::from($visits->map(function($visit) {
        $distance = '—';
        if ($visit->check_in_lat && $visit->check_in_lng && $visit->party?->latitude && $visit->party?->longitude) {
            $earthRadius = 6371000;
            $latDelta = deg2rad((float)$visit->party->latitude - (float)$visit->check_in_lat);
            $lonDelta = deg2rad((float)$visit->party->longitude - (float)$visit->check_in_lng);
            $a = sin($latDelta / 2) * sin($latDelta / 2) + cos(deg2rad((float)$visit->check_in_lat)) * cos(deg2rad((float)$visit->party->latitude)) * sin($lonDelta / 2) * sin($lonDelta / 2);
            $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
            $m = round($earthRadius * $c);
            $distance = $m < 1000 ? "{$m} m" : round($m / 1000, 2) . " km";
        }

        return [
            'id'             => $visit->id,
            'party_name'     => $visit->party?->name ?? '—',
            'party_code'     => $visit->party?->party_code ?? $visit->party?->code ?? '',
            'party_lat'      => $visit->party?->latitude,
            'party_lng'      => $visit->party?->longitude,
            'user_name'      => $visit->user?->name ?? 'Unknown',
            'check_in_time'  => $visit->check_in_time ? $visit->check_in_time->format('d M Y, h:i A') : 'Pending',
            'check_in_lat'   => $visit->check_in_lat,
            'check_in_lng'   => $visit->check_in_lng,
            'check_out_time' => $visit->check_out_time ? $visit->check_out_time->format('d M Y, h:i A') : '—',
            'check_out_lat'  => $visit->check_out_lat,
            'check_out_lng'  => $visit->check_out_lng,
            'distance'       => $distance,
            'status'         => $visit->status,
            'purpose'        => $visit->purpose ?? '',
            'notes'          => $visit->notes ?? '',
            'check_in_photo' => $visit->check_in_photo_url,
        ];
    })) }},
...
    exportUrl() {
        const params = new URLSearchParams();
        if (this.dateFrom) params.append('date_from', this.dateFrom);
        if (this.dateTo) params.append('date_to', this.dateTo);
        if (this.employeeId) params.append('employee_id', this.employeeId);
        if (this.status) params.append('status', this.status);
        if (this.search) params.append('search', this.search);
        const query = params.toString();
        return `{{ route('admin.visits.export') }}${query ? '?' + query : ''}`;
    }
...
    {{-- Header --}}
    <div class="panel-header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-section-title">Visit Records</h2>
                <p class="text-caption mt-1">Real-time dealer and store check-ins logged by field workers.</p>
            </div>
            <div class="flex items-center gap-2">
                @canany(['visits.view', 'visits.view-team', 'visits.view-all', 'visits.view-own', 'visits.manage'])
                    <a :href="exportUrl()"
                       class="btn-primary text-xs py-2 px-3.5 inline-flex items-center gap-1.5 shadow-sm bg-emerald-600 hover:bg-emerald-700 border-emerald-600 text-white font-medium rounded-lg transition"
                       title="Export filtered visits to Excel">
                        <x-lucide-download class="w-4 h-4" />
                        <span>Export Excel</span>
                    </a>
                @endcanany
            </div>
        </div>
...
    <tbody>
        <template x-for="visit in records" :key="visit.id">
            <tr class="hover:bg-surface-muted/60 transition-colors">
                <td>
                    <div class="font-semibold text-ink-900" x-text="visit.party_name"></div>
                    <template x-if="visit.party_code">
                        <div class="text-xs text-ink-400 font-mono" x-text="visit.party_code"></div>
                    </template>
                    <template x-if="visit.party_lat && visit.party_lng">
                        <div class="text-[11px] text-slate-500 font-mono mt-0.5 flex items-center gap-1" title="Mart Actual Coordinates">
                            <span class="text-emerald-600 font-sans font-medium text-[10px]">Mart:</span>
                            <span x-text="`${parseFloat(visit.party_lat).toFixed(5)}, ${parseFloat(visit.party_lng).toFixed(5)}`"></span>
                        </div>
                    </template>
                </td>
                <td class="text-body font-medium" x-text="visit.user_name"></td>
                <td class="text-caption">
                    <span x-text="visit.check_in_time"></span>
                    <template x-if="visit.check_in_lat && visit.check_in_lng">
                        <div class="text-[11px] text-slate-500 font-mono mt-0.5" title="Check-In Coordinates">
                            <span x-text="`${parseFloat(visit.check_in_lat).toFixed(5)}, ${parseFloat(visit.check_in_lng).toFixed(5)}`"></span>
                            <template x-if="visit.distance && visit.distance !== '—'">
                                <span class="text-brand-600 font-sans font-medium text-[10px] block" x-text="`(${visit.distance} away)`"></span>
                            </template>
                        </div>
                    </template>
                </td>
                <td class="text-caption">
                    <span x-text="visit.check_out_time"></span>
                    <template x-if="visit.check_out_lat && visit.check_out_lng">
                        <div class="text-[11px] text-slate-500 font-mono mt-0.5" title="Check-Out Coordinates">
                            <span x-text="`${parseFloat(visit.check_out_lat).toFixed(5)}, ${parseFloat(visit.check_out_lng).toFixed(5)}`"></span>
                        </div>
                    </template>
                </td>
```
