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
use App\Models\Trip;
use App\Models\AuditLog;
use App\Services\DataScope;
use App\Services\FuelRateResolverService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class DayStartController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        abort_unless(
            $user->can('day-start.view-all')
            || $user->can('day-start.view-team')
            || $user->can('day-start.view-own')
            || $user->can('day-start.manage'),
            403
        );

        $visibleIds = DataScope::visibleUserIds('day-start');

        $employees = User::with('roles')
            ->where('is_active', true)
            ->when($visibleIds !== null, fn($q) => $q->whereIn('id', $visibleIds))
            ->orderBy('name')
            ->get();

        return view('admin.day-starts.index', compact('employees'));
    }

    public function data(Request $request)
    {
        $user = $request->user();

        abort_unless(
            $user->can('day-start.view-all')
            || $user->can('day-start.view-team')
            || $user->can('day-start.view-own')
            || $user->can('day-start.manage'),
            403
        );

        $query = DayStart::query()
            ->forUser($user)
            ->with(['user.roles', 'tourPurpose', 'tourType', 'vehicleType', 'party', 'district', 'area'])
            ->select('day_starts.*')
            ->orderBy('day_starts.date', 'desc')
            ->orderBy('day_starts.id', 'desc');

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

        $search = $request->input('search.value');
        if (empty($search) && is_string($request->input('search'))) {
            $search = $request->input('search');
        }
        if (!empty($search) && is_string($search)) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('user', function ($uq) use ($search) {
                    $uq->where('name', 'like', "%{$search}%")
                       ->orWhere('employee_id', 'like', "%{$search}%");
                })->orWhereHas('tourPurpose', function ($tq) use ($search) {
                    $tq->where('name', 'like', "%{$search}%");
                })->orWhereHas('district', function ($dq) use ($search) {
                    $dq->where('name', 'like', "%{$search}%");
                })->orWhereHas('area', function ($aq) use ($search) {
                    $aq->where('name', 'like', "%{$search}%");
                })->orWhere('area_name_custom', 'like', "%{$search}%")
                  ->orWhere('visit_place', 'like', "%{$search}%")
                  ->orWhere('vehicle_no', 'like', "%{$search}%")
                  ->orWhere('remarks', 'like', "%{$search}%");
            });
        }

        $canApprove = $user->can('day-start.approve');
        $actionableIds = DataScope::actionableSubordinateIds('day-start');

        return DataTables::of($query)
            ->addColumn('employee', function ($ds) {
                return [
                    'name' => $ds->user?->name ?? 'Unknown',
                    'role' => $ds->user?->role_name ?? ($ds->user?->roles->first()?->name ?? 'Field Worker'),
                    'employee_id' => $ds->user?->employee_id ?? '—',
                ];
            })
            ->addColumn('date', function ($ds) {
                return [
                    'formatted' => $ds->date?->format('d M Y') ?? '—',
                    'raw' => $ds->date?->format('Y-m-d') ?? '',
                ];
            })
            ->addColumn('tour_purpose', function ($ds) {
                return [
                    'purpose' => $ds->tourPurpose?->name ?? '—',
                    'tour_type' => $ds->tourType?->name ?? null,
                    'vehicle' => $ds->vehicleType?->name ? ($ds->vehicleType->name . ($ds->vehicle_no ? " ({$ds->vehicle_no})" : '')) : ($ds->vehicle_no ?? null),
                    'visit_place' => $ds->visit_place,
                    'district' => $ds->district?->name,
                    'area' => $ds->area?->name ?? $ds->area_name_custom,
                    'party' => $ds->party?->name,
                    'remarks' => $ds->remarks,
                ];
            })
            ->addColumn('opening_km', function ($ds) {
                return $ds->opening_km !== null ? number_format((float)$ds->opening_km, 1) : '—';
            })
            ->addColumn('closing_km', function ($ds) {
                return $ds->closing_km !== null ? number_format((float)$ds->closing_km, 1) : '—';
            })
            ->addColumn('status', function ($ds) {
                return [
                    'status' => $ds->status ?? 'pending',
                    'end_time' => $ds->end_time ? Carbon::parse($ds->end_time)->format('h:i A') : null,
                ];
            })
            ->addColumn('actions', function ($ds) use ($user, $canApprove, $actionableIds) {
                $isSubordinateEligible = $actionableIds === null || in_array($ds->user_id, $actionableIds, true);
                $notSelf = ($ds->user_id !== $user->id) || $user->hasRole(['Super Admin', 'super_admin']);
                $isPending = in_array($ds->status, ['pending', 'submitted'], true);
                $canAction = $canApprove && $isPending && $notSelf && $isSubordinateEligible;

                return [
                    'id' => $ds->id,
                    'show_url' => route('admin.day-starts.show', $ds->id),
                    'update_status_url' => route('admin.day-starts.updateStatus', $ds->id),
                    'can_action' => $canAction,
                    'status' => $ds->status,
                ];
            })
            ->rawColumns(['actions'])
            ->make(true);
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

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Tour plan submitted successfully!',
                    'day_start_id' => $dayStart->id,
                    'redirect_url' => route('admin.day-starts.index'),
                ]);
            }

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
            ->with(['user', 'tourPurpose', 'tourType', 'vehicleType', 'party', 'approvedBy'])
            ->findOrFail($id);

        $visits = Visit::with('party')
            ->where('user_id', $dayStart->user_id)
            ->where('day_start_id', $dayStart->id)
            ->orderBy('check_in_time', 'desc')
            ->get();

        $orders = Order::with('party')
            ->where('user_id', $dayStart->user_id)
            ->whereDate('created_at', $dayStart->date)
            ->orderBy('created_at', 'desc')
            ->get();

        $expenses = ExpenseBill::with(['category', 'items', 'tempApprovedBy', 'approvedBy', 'rejectedBy'])
            ->where('user_id', $dayStart->user_id)
            ->where('day_start_id', $dayStart->id)
            ->orderBy('created_at', 'desc')
            ->get();

        $authUser = auth()->user();
        $isSuperAdmin = $authUser ? $authUser->hasRole(['Super Admin', 'super_admin']) : false;
        $canFinalApprove = $authUser ? ($authUser->can('expenses.final_approve') || $isSuperAdmin) : false;
        $canTempApprove = $authUser ? ($authUser->can('expenses.approve') || $isSuperAdmin) : false;
        $canEditExpense = $authUser ? ($authUser->can('expenses.edit') || $authUser->can('expenses.manage') || $isSuperAdmin) : false;
        $canEditDay = $authUser ? ($authUser->can('day-start.approve') || $authUser->hasRole(['Super Admin', 'Admin', 'Manager'])) : false;
        $canEditClosing = $canEditDay;
        $categories = \App\Models\ExpenseCategory::where('is_active', true)->orderBy('name')->get();

        $tourPurposes = TourPurpose::where('is_active', true)->orderBy('name')->get();
        $tourTypes = TourType::where('is_active', true)->orderBy('name')->get();
        $vehicleTypes = VehicleType::where('is_active', true)->orderBy('name')->get();
        $customers = Customer::orderBy('name')->get(['id', 'name']);

        return view('admin.day-starts.show', compact(
            'dayStart', 'visits', 'orders', 'expenses',
            'canTempApprove', 'canFinalApprove', 'canEditExpense', 'canEditClosing', 'canEditDay',
            'categories', 'tourPurposes', 'tourTypes', 'vehicleTypes', 'customers'
        ));
    }

    /**
     * Update Closing KM, end time, and photo for a DayStart session.
     * Accessible by Admins and Managers with proper permissions.
     */
    public function updateClosing(Request $request, int $id)
    {
        $user = $request->user();
        abort_unless(
            $user->can('day-start.approve')
            || $user->hasRole(['Super Admin', 'Admin', 'Manager']),
            403,
            'Unauthorized: Only Admins and Managers can adjust Day End records.'
        );

        $dayStart = DayStart::with(['user', 'vehicleType'])->findOrFail($id);

        if ($dayStart->user_id === $user->id && !$user->hasRole(['Super Admin', 'super_admin'])) {
            abort(403, 'Conflict of Interest: You cannot edit your own Day End details.');
        }

        $actionableIds = DataScope::actionableSubordinateIds('day-start');
        if ($actionableIds !== null && !in_array($dayStart->user_id, $actionableIds, true)) {
            abort(403, 'Unauthorized: You can only adjust Day End records for subordinates strictly below you.');
        }

        $request->validate([
            'closing_km' => 'required|numeric|min:0',
            'end_time' => 'nullable',
            'closing_km_photo' => 'nullable|image|max:20480',
            'remarks' => 'nullable|string|max:1000',
            'reason' => 'required|string|min:3',
        ]);

        if ($dayStart->opening_km !== null && (float) $request->closing_km < (float) $dayStart->opening_km) {
            return response()->json([
                'success' => false,
                'message' => 'Closing KM (' . $request->closing_km . ') cannot be less than Opening KM (' . $dayStart->opening_km . ').',
                'errors' => [
                    'closing_km' => ['Closing KM must be greater than or equal to Opening KM (' . $dayStart->opening_km . ').'],
                ],
            ], 422);
        }

        $oldValues = [
            'closing_km' => $dayStart->closing_km,
            'end_time' => $dayStart->end_time,
            'is_auto_closed' => $dayStart->is_auto_closed,
            'flagged_reason' => $dayStart->flagged_reason,
            'remarks' => $dayStart->remarks,
            'end_remarks' => $dayStart->end_remarks,
        ];

        DB::transaction(function () use ($request, $dayStart, $user, $oldValues) {
            // Handle closing KM image upload if provided
            $closingImagePath = $dayStart->closing_km_photo;
            if ($request->hasFile('closing_km_photo')) {
                $closingImagePath = $request->file('closing_km_photo')
                    ->store('day-starts/km-images', 'public');
            }

            // Determine end_time
            $endTime = $request->filled('end_time')
                ? Carbon::parse($request->end_time)->format('H:i:s')
                : ($dayStart->end_time ?: Carbon::now()->toTimeString());

            $newClosingKm = (float) $request->closing_km;
            $openingKm = (float) ($dayStart->opening_km ?? 0);
            $claimedKm = max(0, $newClosingKm - $openingKm);

            $dayStart->update([
                'closing_km' => $newClosingKm,
                'closing_km_photo' => $closingImagePath,
                'end_time' => $endTime,
                'end_remarks' => $request->remarks,
                'is_auto_closed' => false,
                'flagged_reason' => null, // Clear auto-close flag as admin manually corrected it
            ]);

            // Calculate distance & fuel allowance
            if ($dayStart->vehicle_type_id && $claimedKm > 0) {
                /** @var FuelRateResolverService $fuelResolver */
                $fuelResolver = app(FuelRateResolverService::class);
                $dateStr = $dayStart->date ? $dayStart->date->toDateString() : Carbon::today()->toDateString();
                $rateInfo = $fuelResolver->getEffectiveRate($dayStart->user_id, $dayStart->vehicle_type_id, $dateStr);

                $appliedRate = (float) $rateInfo['rate_per_km'];
                $rateSource = $rateInfo['source'];
                $totalAmount = round($claimedKm * $appliedRate, 2);

                $dayStart->update([
                    'applied_rate_per_km' => $appliedRate,
                    'applied_rate_source' => $rateSource,
                    'total_amount' => $totalAmount,
                ]);

                Trip::updateOrCreate(
                    ['day_start_id' => $dayStart->id],
                    [
                        'user_id' => $dayStart->user_id,
                        'vehicle_type_id' => $dayStart->vehicle_type_id,
                        'vehicle_no' => $dayStart->vehicle_no,
                        'start_location' => $dayStart->visit_place,
                        'opening_km' => $dayStart->opening_km,
                        'closing_km' => $newClosingKm,
                        'distance_km' => $claimedKm,
                        'applied_rate_per_km' => $appliedRate,
                        'applied_rate_source' => $rateSource,
                        'total_amount' => $totalAmount,
                        'date' => $dayStart->date,
                        'status' => 'completed',
                        'remarks' => $request->remarks,
                    ]
                );

                if ($totalAmount > 0) {
                    $travelCategory = \App\Models\ExpenseCategory::where('name', 'Travel')->first();
                    ExpenseBill::updateOrCreate(
                        [
                            'day_start_id' => $dayStart->id,
                            'is_system_generated' => true,
                        ],
                        [
                            'user_id' => $dayStart->user_id,
                            'date' => $dayStart->date,
                            'amount' => $totalAmount,
                            'status' => 'pending',
                            'category_id' => $travelCategory?->id,
                        ]
                    );
                }
            }

            // Audit log
            AuditLog::logEvent(
                event: 'daystart.closing_updated',
                auditable: $dayStart,
                oldValues: $oldValues,
                newValues: [
                    'closing_km' => $newClosingKm,
                    'end_time' => $endTime,
                    'is_auto_closed' => false,
                    'closing_km_photo' => $closingImagePath,
                ],
                reason: $request->reason,
                causerUserId: $user->id
            );
        });

        $dayStart->refresh()->load(['user', 'tourPurpose', 'tourType', 'vehicleType']);

        $distance = ($dayStart->closing_km && $dayStart->opening_km)
            ? max(0, $dayStart->closing_km - $dayStart->opening_km)
            : 0;

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Closing KM and Day End details updated successfully.',
                'record' => [
                    'id' => $dayStart->id,
                    'closing_km' => $dayStart->closing_km,
                    'end_time' => $dayStart->end_time,
                    'end_time_formatted' => $dayStart->end_time ? Carbon::parse($dayStart->end_time)->format('h:i A') : '—',
                    'distance_km' => $distance,
                    'closing_km_photo_url' => $dayStart->closing_km_photo ? asset('storage/' . $dayStart->closing_km_photo) : null,
                    'remarks' => $dayStart->remarks ?? '',
                    'end_remarks' => $dayStart->end_remarks ?? '',
                    'is_auto_closed' => $dayStart->is_auto_closed,
                    'total_amount' => $dayStart->total_amount,
                ],
            ]);
        }

        return back()->with('success', 'Closing KM and Day End details updated successfully.');
    }

    /**
     * Update Day Start details (Opening KM, vehicle, tour purpose/type, party, date, start time).
     * Accessible by Admins and Managers with proper permissions.
     */
    public function updateStart(Request $request, int $id)
    {
        $user = $request->user();
        abort_unless(
            $user->can('day-start.approve')
            || $user->hasRole(['Super Admin', 'Admin', 'Manager']),
            403,
            'Unauthorized: Only Admins and Managers can adjust Day Start records.'
        );

        $dayStart = DayStart::with(['user', 'vehicleType'])->findOrFail($id);

        if ($dayStart->user_id === $user->id && !$user->hasRole(['Super Admin', 'super_admin'])) {
            abort(403, 'Conflict of Interest: You cannot edit your own Day Start details.');
        }

        $actionableIds = DataScope::actionableSubordinateIds('day-start');
        if ($actionableIds !== null && !in_array($dayStart->user_id, $actionableIds, true)) {
            abort(403, 'Unauthorized: You can only adjust Day Start records for subordinates strictly below you.');
        }

        $request->validate([
            'date' => 'required|date',
            'start_time' => 'nullable',
            'tour_purpose_id' => 'required|exists:tour_purposes,id',
            'tour_type_id' => 'required|exists:tour_types,id',
            'vehicle_type_id' => 'required|exists:vehicle_types,id',
            'vehicle_no' => 'nullable|string|max:50',
            'opening_km' => 'required|numeric|min:0',
            'opening_km_photo' => 'nullable|image|max:20480',
            'closing_km' => 'nullable|numeric|min:0',
            'party_id' => 'nullable|exists:customers,id',
            'visit_place' => 'nullable|string|max:255',
            'reason' => 'required|string|min:3',
        ]);

        $newOpeningKm = (float) $request->opening_km;
        $effectiveClosingKm = $request->filled('closing_km')
            ? (float) $request->closing_km
            : ($dayStart->closing_km !== null ? (float) $dayStart->closing_km : null);

        if ($effectiveClosingKm !== null && $effectiveClosingKm < $newOpeningKm) {
            return response()->json([
                'success' => false,
                'message' => 'Closing KM (' . $effectiveClosingKm . ') cannot be less than Opening KM (' . $newOpeningKm . ').',
                'errors' => [
                    'opening_km' => ['Opening KM must be less than or equal to Closing KM (' . $effectiveClosingKm . ').'],
                ],
            ], 422);
        }

        $oldValues = [
            'date' => $dayStart->date?->toDateString(),
            'start_time' => $dayStart->start_time,
            'tour_purpose_id' => $dayStart->tour_purpose_id,
            'tour_type_id' => $dayStart->tour_type_id,
            'vehicle_type_id' => $dayStart->vehicle_type_id,
            'vehicle_no' => $dayStart->vehicle_no,
            'opening_km' => $dayStart->opening_km,
            'closing_km' => $dayStart->closing_km,
            'party_id' => $dayStart->party_id,
            'visit_place' => $dayStart->visit_place,
        ];

        DB::transaction(function () use ($request, $dayStart, $user, $oldValues, $newOpeningKm, $effectiveClosingKm) {
            $openingImagePath = $dayStart->opening_km_photo;
            if ($request->hasFile('opening_km_photo')) {
                $openingImagePath = $request->file('opening_km_photo')
                    ->store('day-starts/km-images', 'public');
            }

            $startTime = $request->filled('start_time')
                ? Carbon::parse($request->start_time)->format('H:i:s')
                : $dayStart->start_time;

            $updateData = [
                'date' => $request->date,
                'start_time' => $startTime,
                'tour_purpose_id' => $request->tour_purpose_id,
                'tour_type_id' => $request->tour_type_id,
                'vehicle_type_id' => $request->vehicle_type_id,
                'vehicle_no' => $request->vehicle_no,
                'opening_km' => $newOpeningKm,
                'opening_km_photo' => $openingImagePath,
                'party_id' => $request->party_id,
                'visit_place' => $request->visit_place,
            ];

            if ($request->filled('closing_km')) {
                $updateData['closing_km'] = (float) $request->closing_km;
            }

            $dayStart->update($updateData);

            // Recalculate trip distance and fuel if closing KM exists
            if ($effectiveClosingKm !== null) {
                $claimedKm = max(0, $effectiveClosingKm - $newOpeningKm);

                if ($dayStart->vehicle_type_id && $claimedKm > 0) {
                    /** @var FuelRateResolverService $fuelResolver */
                    $fuelResolver = app(FuelRateResolverService::class);
                    $dateStr = $dayStart->date ? $dayStart->date->toDateString() : Carbon::today()->toDateString();
                    $rateInfo = $fuelResolver->getEffectiveRate($dayStart->user_id, $dayStart->vehicle_type_id, $dateStr);

                    $appliedRate = (float) $rateInfo['rate_per_km'];
                    $rateSource = $rateInfo['source'];
                    $totalAmount = round($claimedKm * $appliedRate, 2);

                    $dayStart->update([
                        'applied_rate_per_km' => $appliedRate,
                        'applied_rate_source' => $rateSource,
                        'total_amount' => $totalAmount,
                    ]);

                    Trip::updateOrCreate(
                        ['day_start_id' => $dayStart->id],
                        [
                            'user_id' => $dayStart->user_id,
                            'vehicle_type_id' => $dayStart->vehicle_type_id,
                            'vehicle_no' => $dayStart->vehicle_no,
                            'start_location' => $dayStart->visit_place,
                            'opening_km' => $newOpeningKm,
                            'closing_km' => $effectiveClosingKm,
                            'distance_km' => $claimedKm,
                            'applied_rate_per_km' => $appliedRate,
                            'applied_rate_source' => $rateSource,
                            'total_amount' => $totalAmount,
                            'date' => $dayStart->date,
                            'status' => 'completed',
                            'remarks' => $dayStart->remarks,
                        ]
                    );

                    if ($totalAmount > 0) {
                        $travelCategory = \App\Models\ExpenseCategory::where('name', 'Travel')->first();
                        ExpenseBill::updateOrCreate(
                            [
                                'day_start_id' => $dayStart->id,
                                'is_system_generated' => true,
                            ],
                            [
                                'user_id' => $dayStart->user_id,
                                'date' => $dayStart->date,
                                'amount' => $totalAmount,
                                'status' => 'pending',
                                'category_id' => $travelCategory?->id,
                            ]
                        );
                    }
                }
            }

            // Audit log
            AuditLog::logEvent(
                event: 'daystart.start_updated',
                auditable: $dayStart,
                oldValues: $oldValues,
                newValues: [
                    'opening_km' => $newOpeningKm,
                    'closing_km' => $effectiveClosingKm,
                    'tour_purpose_id' => $request->tour_purpose_id,
                    'vehicle_type_id' => $request->vehicle_type_id,
                    'vehicle_no' => $request->vehicle_no,
                    'date' => $request->date,
                ],
                reason: $request->reason,
                causerUserId: $user->id
            );
        });

        $dayStart->refresh()->load(['user', 'tourPurpose', 'tourType', 'vehicleType', 'party']);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Day Start details updated successfully.',
                'record' => [
                    'id' => $dayStart->id,
                    'opening_km' => $dayStart->opening_km,
                    'closing_km' => $dayStart->closing_km,
                    'tour_purpose_name' => $dayStart->tourPurpose?->name ?? '—',
                    'tour_type_name' => $dayStart->tourType?->name ?? '—',
                    'vehicle_type_name' => $dayStart->vehicleType?->name ?? '—',
                    'vehicle_no' => $dayStart->vehicle_no ?? '—',
                    'visit_place' => $dayStart->visit_place ?? '—',
                    'party_name' => $dayStart->party?->name ?? '—',
                    'opening_km_photo_url' => $dayStart->opening_km_photo ? asset('storage/' . $dayStart->opening_km_photo) : null,
                ],
            ]);
        }

        return back()->with('success', 'Day Start details updated successfully.');
    }

    public function updateStatus(Request $request, int $id)
    {
        $user = $request->user();
        abort_unless($user->can('day-start.approve'), 403);

        $request->validate([
            'status' => 'required|in:approved,rejected',
        ]);

        $dayStart = DayStart::findOrFail($id);

        if ($dayStart->user_id === $user->id && !$user->hasRole(['Super Admin', 'super_admin'])) {
            abort(403, 'Conflict of Interest: You cannot approve or reject your own Day Start.');
        }

        $actionableIds = DataScope::actionableSubordinateIds('day-start');
        if ($actionableIds !== null && !in_array($dayStart->user_id, $actionableIds, true)) {
            abort(403, 'Unauthorized: You can only approve or reject Day Start records for subordinates strictly below you.');
        }

        $oldStatus = $dayStart->status;

        $dayStart->disableAuditLog = true;
        $dayStart->update([
            'status' => $request->status,
            'approved_by' => $user->id,
        ]);

        // If day start is rejected, reject any linked pending system-generated travel expense
        if ($request->status === 'rejected') {
            $dayStart->expenses()
                ->where('is_system_generated', true)
                ->whereIn('status', ['pending', 'temp_approved'])
                ->update([
                    'status' => 'rejected',
                    'rejected_by' => $user->id,
                    'rejected_at' => now(),
                    'rejection_reason' => 'Day Start was rejected by ' . $user->name,
                ]);
        }

        \App\Models\AuditLog::logEvent(
            event: 'daystart.' . $request->status,
            auditable: $dayStart,
            oldValues: ['status' => $oldStatus],
            newValues: ['status' => $request->status, 'approved_by' => $user->id],
            reason: 'Day Start ' . ucfirst($request->status) . ' by ' . $user->name,
            causerUserId: $user->id
        );

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'status' => $request->status,
                'approved_by_name' => $user->name,
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
            'purposeFlags' => $tourPurposes->mapWithKeys(fn($item) => [
                $item->id => ['requires_party' => $item->requires_party],
            ]),
            'tourTypeFlags' => $tourTypes->mapWithKeys(fn($item) => [
                $item->id => ['requires_vehicle' => $item->requires_vehicle],
            ]),
            'vehicleTypeFlags' => $vehicleTypes->mapWithKeys(fn($item) => [
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
            if (!$user->canSelectAllParties()) {
                $partyRules[] = Rule::exists('party_employee_map', 'party_id')
                    ->where('employee_id', $user->id)
                    ->where('is_active', true);
            }
        }

        $rules = [
            'date' => [
                'required',
                'date',
                Rule::unique('day_starts')->where(fn($query) => $query->where('user_id', $user->id)),
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
