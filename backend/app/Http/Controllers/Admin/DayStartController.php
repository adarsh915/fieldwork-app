<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\DayStart;
use App\Models\TourPurpose;
use App\Models\TourType;
use App\Models\User;
use App\Models\VehicleType;
use App\Notifications\TourPlanSubmitted;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

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
            ->with(['user', 'tourPurpose', 'tourType', 'vehicleType', 'party'])
            ->orderByDesc('date')
            ->orderByDesc('created_at');

        if ($request->filled('date_from')) {
            $query->whereDate('date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('date', '<=', $request->date_to);
        }

        if ($request->filled('employee_id') && $user->canany(['day-start.view-all', 'day-start.view-team'])) {
            $query->where('user_id', $request->employee_id);
        }

        $dayStarts = $query->paginate(20)->withQueryString();

        $employees = collect();
        if ($user->can('day-start.view-all')) {
            $employees = User::query()->orderBy('name')->get(['id', 'name']);
        } elseif ($user->can('day-start.view-team')) {
            $teamMembers = $user->teamMembers()->orderBy('name')->get(['id', 'name']);
            $employees = collect([['id' => $user->id, 'name' => $user->name]])->concat($teamMembers);
        }

        return view('admin.day-starts.index', compact('dayStarts', 'employees'));
    }

    public function create()
    {
        abort_unless(auth()->user()->can('day-start.create'), 403);

        return view('admin.day-starts.create', $this->formData(auth()->user()));
    }

    public function store(Request $request)
    {
        $user = $request->user();
        abort_unless($user->can('day-start.create'), 403);

        $validated = $this->validateDayStart($request, $user);

        if ($request->hasFile('opening_km_image')) {
            $validated['opening_km_image'] = $request->file('opening_km_image')
                ->store('day-start-photos', 'public');
        }

        $validated['user_id'] = $user->id;
        $validated['status'] = 'submitted';

        $dayStart = DayStart::create($validated);
        $dayStart->load(['user', 'tourPurpose']);

        if ($user->manager) {
            $user->manager->notify(new TourPlanSubmitted($dayStart));
        }

        return redirect()
            ->route('admin.day-starts.index')
            ->with('success', 'Tour plan submitted');
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

        return view('admin.day-starts.show', compact('dayStart'));
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
            'party_id' => array_filter([
                $requiresParty ? 'required' : 'nullable',
                'exists:customers,id',
                $requiresParty && ! $user->canSelectAllParties()
                    ? Rule::in($this->assignedPartiesFor($user)->pluck('id')->all())
                    : null,
            ]),
            'vehicle_no' => [$requiresVehicleNo ? 'required' : 'nullable', 'string', 'max:50'],
            'opening_km' => [$requiresKmAndPhoto ? 'required' : 'nullable', 'numeric', 'min:0'],
            'opening_km_image' => [
                $requiresKmAndPhoto ? 'required' : 'nullable',
                'image',
                'max:5120',
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
