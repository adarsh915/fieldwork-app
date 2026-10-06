<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\DayStart;
use App\Models\TourPurpose;
use App\Models\TourType;
use App\Models\User;
use App\Models\VehicleType;
use App\Models\Visit;
use App\Models\Order;
use App\Models\ExpenseBill;
use App\Services\DataScope;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Log;

class DayStartController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        abort_unless(
            $user->can('day-start.view-all')
            || $user->can('day-start.view-team')
            || $user->can('day-start.view-own'),
            403
        );

        $query = DayStart::query()
            ->forUser($user)
            ->with(['user.roles', 'tourPurpose', 'tourType', 'vehicleType', 'party'])
            ->orderByDesc('date')
            ->orderByDesc('created_at');

        if ($request->filled('date_from')) {
            $query->whereDate('date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('date', '<=', $request->date_to);
        }

        $visibleIds = DataScope::visibleUserIds('day-start');
        if ($request->filled('employee_id')) {
            if ($visibleIds === null || in_array($request->employee_id, $visibleIds)) {
                $query->where('user_id', $request->employee_id);
            } else {
                $query->whereRaw('1=0');
            }
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $dayStarts = $query->paginate(20)->withQueryString();

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'records' => $dayStarts->map(function ($ds) {
                    return [
                        'id'                => $ds->id,
                        'user_name'         => $ds->user ? $ds->user->name_with_role : 'Unknown',
                        'date_formatted'    => $ds->date?->format('d M Y') ?? '—',
                        'tour_purpose'      => $ds->tourPurpose?->name ?? '—',
                        'opening_km'        => $ds->opening_km ?? '—',
                        'closing_km'        => $ds->closing_km ?? '—',
                        'status'            => $ds->status,
                        'end_time'          => $ds->end_time,
                        'show_url'          => route('admin.day-starts.show', $ds->id),
                        'update_status_url' => route('admin.day-starts.updateStatus', $ds->id),
                    ];
                }),
                'pagination' => [
                    'current_page' => $dayStarts->currentPage(),
                    'last_page'    => $dayStarts->lastPage(),
                    'per_page'     => $dayStarts->perPage(),
                    'total'        => $dayStarts->total(),
                    'from'         => $dayStarts->firstItem() ?? 0,
                    'to'           => $dayStarts->lastItem() ?? 0,
                ],
                'can_approve' => $user->can('day-start.approve'),
            ]);
        }

        $employees = User::with('roles')
            ->where('is_active', true)
            ->when($visibleIds !== null, fn($q) => $q->whereIn('id', $visibleIds))
            ->orderBy('name')
            ->get();

        return view('admin.day-starts.index', compact('dayStarts', 'employees'));
    }

    public function create()
    {
        abort_unless(auth()->user()->can('day-start.create'), 403);

        return view('admin.day-starts.create', $this->formData(auth()->user()));
    }

    public function store(Request $request)
    {
        $startTime = microtime(true);
        $user = $request->user();
        abort_unless($user->can('day-start.create'), 403);

        Log::info('Admin DayStart store request initiated', [
            'user_id' => $user->id,
            'ip' => $request->ip(),
            'payload' => $request->except(['opening_km_image']),
            'has_opening_km_image' => $request->hasFile('opening_km_image'),
        ]);

        try {
            $validated = $this->validateDayStart($request, $user);

            if ($request->hasFile('opening_km_image')) {
                $file = $request->file('opening_km_image');
                Log::info('Admin DayStart storing photo', [
                    'original_name' => $file->getClientOriginalName(),
                    'size_bytes' => $file->getSize(),
                    'mime_type' => $file->getClientMimeType(),
                ]);
                $validated['opening_km_image'] = $file->store('day-start-photos', 'public');
                Log::info('Admin DayStart photo stored', ['path' => $validated['opening_km_image']]);
            }

            $validated['user_id'] = $user->id;
            $validated['status'] = 'pending';

            $dayStart = DayStart::create($validated);
            Log::info('Admin DayStart record created', ['id' => $dayStart->id]);

            $dayStart->load(['user', 'tourPurpose']);

            if ($user->manager) {
                try {
                    $user->manager->notify(new TourPlanSubmitted($dayStart));
                    Log::info('Admin DayStart notification sent to manager', ['manager_id' => $user->manager->id]);
                } catch (\Exception $ne) {
                    Log::error('Admin DayStart notification failed: ' . $ne->getMessage());
                }
            }

            $elapsedMs = round((microtime(true) - $startTime) * 1000, 2);
            Log::info('Admin DayStart completed successfully', ['elapsed_ms' => $elapsedMs]);

            return redirect()
                ->route('admin.day-starts.index')
                ->with('success', 'Tour plan submitted');
        } catch (\Exception $e) {
            $elapsedMs = round((microtime(true) - $startTime) * 1000, 2);
            Log::error('Admin DayStart store error', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'elapsed_ms' => $elapsedMs,
            ]);
            throw $e;
        }
    }

    public function show(Request $request, int $id)
    {
        $user = $request->user();

        abort_unless(
            $user->can('day-start.view-all')
            || $user->can('day-start.view-team')
            || $user->can('day-start.view-own'),
            403
        );

        $dayStart = DayStart::query()
            ->forUser($user)
            ->with(['user', 'tourPurpose', 'tourType', 'vehicleType', 'party'])
            ->findOrFail($id);

        $visits = Visit::with('party')
            ->where('user_id', $dayStart->user_id)
            ->where('day_start_id', $dayStart->id)
            ->orderBy('check_in_time')
            ->get();

        $orders = Order::with('party')
            ->where('user_id', $dayStart->user_id)
            ->whereDate('created_at', $dayStart->date)
            ->orderBy('created_at')
            ->get();

        $expenses = ExpenseBill::with(['category', 'items'])
            ->where('user_id', $dayStart->user_id)
            ->where('day_start_id', $dayStart->id)
            ->orderBy('created_at')
            ->get();

        return view('admin.day-starts.show', compact('dayStart', 'visits', 'orders', 'expenses'));
    }

    public function updateStatus(Request $request, int $id)
    {
        $user = $request->user();
        abort_unless($user->can('day-start.approve'), 403);

        $request->validate([
            'status' => 'required|in:approved,rejected',
        ]);

        $dayStart = DayStart::findOrFail($id);
        $dayStart->update([
            'status' => $request->status,
            'approved_by' => $user->id,
        ]);

        // Auto-update system-generated travel expenses linked to this day start
        $dayStart->expenses()
            ->where('is_system_generated', true)
            ->update([
                'status' => $request->status,
                'approved_by' => $user->id,
            ]);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'status'  => $request->status,
                'message' => 'Day status updated to ' . ucfirst($request->status),
            ]);
        }

        return back()->with('success', 'Day status updated to ' . ucfirst($request->status));
    }

    protected function formData(?User $user = null): array
    {
        $user = $user ?? auth()->user();

        $tourPurposes = TourPurpose::query()->where('is_active', true)->orderBy('name')->get();
        $tourTypes = TourType::query()->where('is_active', true)->orderBy('name')->get();
        $vehicleTypes = VehicleType::query()->where('is_active', true)->orderBy('name')->get();
        $customers = $this->assignedPartiesFor($user);

        return compact('tourPurposes', 'tourTypes', 'vehicleTypes', 'customers') + [
            'purposeFlags' => $tourPurposes->mapWithKeys(fn ($item) => [
                $item->id => ['requires_party' => $item->requires_party],
            ]),
            'tourTypeFlags' => $tourTypes->mapWithKeys(fn ($item) => [
                $item->id => ['requires_vehicle' => $item->requires_vehicle],
            ]),
            'vehicleTypeFlags' => $vehicleTypes->mapWithKeys(fn ($item) => [
                $item->id => ['requires_vehicle_no' => $item->requires_vehicle_no],
            ]),
        ];
    }

    protected function validateDayStart(Request $request, User $user): array
    {
        $request->validate([
            'tour_purpose_id' => ['required', 'exists:tour_purposes,id'],
            'tour_type_id' => ['required', 'exists:tour_types,id'],
            'vehicle_type_id' => ['required', 'exists:vehicle_types,id'],
        ]);

        $tourPurpose = TourPurpose::query()->findOrFail($request->input('tour_purpose_id'));
        $tourType = TourType::query()->findOrFail($request->input('tour_type_id'));
        $vehicleType = VehicleType::query()->findOrFail($request->input('vehicle_type_id'));

        $requiresParty = $tourPurpose->requires_party;
        $requiresVehicleNo = $vehicleType->requires_vehicle_no;
        $requiresKmAndPhoto = $tourType->requires_vehicle && $vehicleType->requires_vehicle_no;

        if ($tourPurpose->name === 'Work From Home') {
            $requiresKmAndPhoto = false;
            $requiresVehicleNo = false;
        }

        $partyRules = ['nullable', 'exists:customers,id'];
        if ($requiresParty) {
            $partyRules[0] = 'required';
            if (! $user->canSelectAllParties()) {
                $partyRules[] = Rule::exists('party_employee_map', 'party_id')
                    ->where('employee_id', $user->id)
                    ->where('is_active', true);
            }
        }

        $rules = [
            'date' => [
                'required',
                'date',
                Rule::unique('day_starts')->where(fn ($query) => $query->where('user_id', $user->id)),
            ],
            'tour_purpose_id' => ['required', 'exists:tour_purposes,id'],
            'tour_type_id' => ['required', 'exists:tour_types,id'],
            'vehicle_type_id' => ['required', 'exists:vehicle_types,id'],
            'visit_place' => ['nullable', 'string', 'max:255'],
            'start_lat' => ['nullable', 'numeric', 'between:-90,90'],
            'start_lng' => ['nullable', 'numeric', 'between:-180,180'],
            'party_id' => $partyRules,
            'vehicle_no' => [$requiresVehicleNo ? 'required' : 'nullable', 'string', 'max:50'],
            'opening_km' => [$requiresKmAndPhoto ? 'required' : 'nullable', 'numeric', 'min:0'],
            'opening_km_image' => [
                $requiresKmAndPhoto ? 'required' : 'nullable',
                'image',
                'max:20480',
            ],
        ];

        return $request->validate($rules);
    }

    protected function assignedPartiesFor(User $user)
    {
        if ($user->canSelectAllParties()) {
            return Customer::query()->orderBy('name')->get(['id', 'name']);
        }

        return $user->parties()->select('customers.id', 'customers.name')->orderBy('customers.name')->get();
    }
}
