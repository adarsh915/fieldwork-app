<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\DayStart;
use App\Models\ExpenseBill;
use App\Models\Trip;
use App\Models\User;
use App\Services\ActivityNotificationService;
use App\Services\DataScope;
use App\Services\FuelRateResolverService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminApprovalController extends Controller
{
    /**
     * GET /api/admin/approvals/counts
     * Returns badge counts of pending approvals visible to this user.
     */
    public function counts(Request $request): JsonResponse
    {
        $user = $request->user();

        // 1. Check if user is eligible to approve
        $isSuperAdmin = $user->hasRole(['Super Admin', 'super_admin']);
        $isAdmin = $user->hasRole(['Admin', 'admin']);
        $canApproveDay = $user->can('day-start.approve') || $isSuperAdmin || $isAdmin || $user->hasRole(['Manager', 'VP']);
        $canFinalApproveExp = $user->can('expenses.final_approve') || $user->hasRole(['Super Admin', 'Admin', 'Finance', 'HR', 'VP']);
        $canTempApproveExp = $user->can('expenses.approve') || $user->can('expenses.manage') || $canFinalApproveExp || $user->hasRole(['Manager', 'VP']);

        // Check if user has subordinates
        $hasSubordinates = User::where('reporting_to', $user->id)->exists();
        $canApproveAny = $canApproveDay || $canTempApproveExp || $canFinalApproveExp || $hasSubordinates;

        if (!$canApproveAny) {
            return response()->json([
                'success' => true,
                'data' => [
                    'can_approve' => false,
                    'pending_day_starts' => 0,
                    'pending_expenses_l1' => 0,
                    'pending_expenses_l2' => 0,
                    'total_pending' => 0,
                ],
            ]);
        }

        // Pending Day Starts Count (using the exact same visibleUserIds function as the admin panel)
        $dayStartVisibleIds = DataScope::visibleUserIds('day-start', $user);
        $dayStartQuery = DayStart::query()->whereIn('status', ['pending', 'submitted']);
        if (!$isSuperAdmin) {
            $dayStartQuery->where('user_id', '!=', $user->id);
            if ($dayStartVisibleIds !== null) {
                $dayStartQuery->whereIn('user_id', $dayStartVisibleIds);
            }
        }
        $pendingDayStarts = $canApproveDay ? $dayStartQuery->count() : 0;

        // Pending Expenses Level 1 (using the exact same visibleUserIds function as the admin panel)
        $expenseVisibleIds = DataScope::visibleUserIds('expenses', $user);
        $expL1Query = ExpenseBill::query()->where('status', 'pending');
        if (!$isSuperAdmin) {
            $expL1Query->where('user_id', '!=', $user->id);
            if ($isAdmin || $user->can('expenses.manage')) {
                // Admin can verify all
            } else {
                $expL1Query->whereHas('user', function ($uq) use ($user) {
                    $uq->where('reporting_to', $user->id);
                });
            }
            if ($expenseVisibleIds !== null) {
                $expL1Query->whereIn('user_id', $expenseVisibleIds);
            }
        }
        $pendingExpL1 = $canTempApproveExp ? $expL1Query->count() : 0;

        // Pending Expenses Level 2 (Final approval for temp_approved claims)
        $expL2Query = ExpenseBill::query()->where('status', 'temp_approved');
        if (!$isSuperAdmin) {
            $expL2Query->where('user_id', '!=', $user->id);
            if ($expenseVisibleIds !== null) {
                $expL2Query->whereIn('user_id', $expenseVisibleIds);
            }
        }
        $pendingExpL2 = $canFinalApproveExp ? $expL2Query->count() : 0;

        $totalPending = $pendingDayStarts + $pendingExpL1 + $pendingExpL2;

        return response()->json([
            'success' => true,
            'data' => [
                'can_approve' => true,
                'can_approve_day' => $canApproveDay,
                'can_temp_approve_expense' => $canTempApproveExp,
                'can_final_approve_expense' => $canFinalApproveExp,
                'pending_day_starts' => $pendingDayStarts,
                'pending_expenses_l1' => $pendingExpL1,
                'pending_expenses_l2' => $pendingExpL2,
                'total_pending' => $totalPending,
            ],
        ]);
    }

    /**
     * GET /api/admin/approvals/team
     * List of subordinate / visible employees for approval scoping filter.
     */
    public function teamMembers(Request $request): JsonResponse
    {
        $user = $request->user();
        $isSuperAdmin = $user->hasRole(['Super Admin', 'super_admin']);
        $module = $request->query('module'); // 'day-start', 'expenses', or null

        $query = User::query()->select('id', 'name', 'employee_id', 'department', 'position');

        if (!$isSuperAdmin) {
            $query->where('id', '!=', $user->id);
            if ($module === 'expenses') {
                $visibleIds = DataScope::visibleUserIds('expenses', $user);
            } elseif ($module === 'day-start') {
                $visibleIds = DataScope::visibleUserIds('day-start', $user);
            } else {
                $dsIds = DataScope::visibleUserIds('day-start', $user);
                $expIds = DataScope::visibleUserIds('expenses', $user);
                if ($dsIds === null || $expIds === null) {
                    $visibleIds = null;
                } else {
                    $visibleIds = array_values(array_unique(array_merge($dsIds, $expIds)));
                }
            }

            if ($visibleIds !== null) {
                $query->whereIn('id', $visibleIds);
            }
        } else {
            $query->where('id', '!=', $user->id);
        }

        if ($request->filled('search')) {
            $s = trim($request->search);
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                  ->orWhere('employee_id', 'like', "%{$s}%")
                  ->orWhere('department', 'like', "%{$s}%")
                  ->orWhere('position', 'like', "%{$s}%");
            });
        }

        $members = $query->where('is_active', true)->orderBy('name')->get();

        return response()->json([
            'success' => true,
            'data' => $members,
        ]);
    }

    /**
     * GET /api/admin/approvals/day-starts
     * Paginated list of Day Start / Tour Plan sessions for approval.
     */
    public function dayStarts(Request $request): JsonResponse
    {
        $user = $request->user();
        $isSuperAdmin = $user->hasRole(['Super Admin', 'super_admin']);
        $canApprove = $user->can('day-start.approve') || $isSuperAdmin || $user->hasRole(['Admin', 'admin', 'Manager', 'VP']);

        if (!$canApprove) {
            return response()->json(['success' => false, 'message' => 'Unauthorized: Only Managers and Admins can view approval requests.'], 403);
        }

        $query = DayStart::with([
            'user:id,name,employee_id,department,position,reporting_to',
            'tourPurpose:id,name',
            'tourType:id,name',
            'vehicleType:id,name',
            'party:id,name',
            'district:id,name',
            'area:id,name',
            'approvedBy:id,name',
        ]);

        // Data scoping — using the exact same visibleUserIds as the admin panel
        if (!$isSuperAdmin) {
            $query->where('user_id', '!=', $user->id); // Conflict of interest: cannot approve self
            $visibleIds = DataScope::visibleUserIds('day-start', $user);
            if ($visibleIds !== null) {
                $query->whereIn('user_id', $visibleIds);
            }
        }

        // Status filter: pending (default), approved, rejected, all
        $status = $request->input('status', 'pending');
        if ($status === 'pending') {
            $query->whereIn('status', ['pending', 'submitted']);
        } elseif ($status !== 'all' && in_array($status, ['approved', 'rejected'], true)) {
            $query->where('status', $status);
        }

        // Filter by specific employee (user_id / employee_id)
        $employeeId = $request->input('user_id', $request->input('employee_id'));
        if ($employeeId && $employeeId !== 'all') {
            $query->where('user_id', $employeeId);
        }

        // Search query
        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->whereHas('user', function ($uq) use ($search) {
                    $uq->where('name', 'like', "%{$search}%")
                       ->orWhere('employee_id', 'like', "%{$search}%");
                })->orWhere('visit_place', 'like', "%{$search}%")
                  ->orWhere('vehicle_no', 'like', "%{$search}%")
                  ->orWhere('remarks', 'like', "%{$search}%")
                  ->orWhereHas('tourPurpose', function ($pq) use ($search) {
                      $pq->where('name', 'like', "%{$search}%");
                  });
            });
        }

        $perPage = max(1, min(50, (int) $request->input('per_page', 15)));
        $paginated = $query->orderBy('date', 'desc')->orderBy('created_at', 'desc')->paginate($perPage);

        $actionableIds = DataScope::actionableSubordinateIds('day-start', $user);

        $formattedItems = collect($paginated->items())->map(function ($ds) use ($user, $isSuperAdmin, $canApprove, $actionableIds) {
            $isSubordinateEligible = $actionableIds === null || in_array($ds->user_id, $actionableIds, true);
            $notSelf = ($ds->user_id !== $user->id) || $isSuperAdmin;
            $isPending = in_array($ds->status, ['pending', 'submitted'], true);
            $canAction = $canApprove && $isPending && $notSelf && $isSubordinateEligible;

            return [
                'id' => $ds->id,
                'user_id' => $ds->user_id,
                'employee_name' => $ds->user?->name ?? 'Unknown',
                'employee_role' => $ds->user?->role_name ?? 'Field Worker',
                'employee_id' => $ds->user?->employee_id ?? '—',
                'date' => $ds->date ? $ds->date->format('Y-m-d') : null,
                'date_formatted' => $ds->date ? $ds->date->format('d M Y') : '—',
                'start_time' => $ds->start_time ? Carbon::parse($ds->start_time)->format('h:i A') : null,
                'end_time' => $ds->end_time ? Carbon::parse($ds->end_time)->format('h:i A') : null,
                'tour_purpose' => $ds->tourPurpose?->name ?? '—',
                'tour_type' => $ds->tourType?->name ?? null,
                'vehicle_type' => $ds->vehicleType?->name ?? null,
                'vehicle_no' => $ds->vehicle_no,
                'visit_place' => $ds->visit_place,
                'district' => $ds->district?->name,
                'area' => $ds->area?->name ?? $ds->area_name_custom,
                'party_name' => $ds->party?->name,
                'opening_km' => $ds->opening_km !== null ? (float) $ds->opening_km : null,
                'opening_km_photo_url' => $ds->opening_km_photo ? asset('storage/' . $ds->opening_km_photo) : null,
                'closing_km' => $ds->closing_km !== null ? (float) $ds->closing_km : null,
                'closing_km_photo_url' => $ds->closing_km_photo ? asset('storage/' . $ds->closing_km_photo) : null,
                'status' => $ds->status ?? 'pending',
                'approved_by_name' => $ds->approvedBy?->name,
                'remarks' => $ds->remarks,
                'can_action' => $canAction,
                'created_at' => $ds->created_at?->diffForHumans(),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $formattedItems,
            'current_page' => $paginated->currentPage(),
            'last_page' => $paginated->lastPage(),
            'total' => $paginated->total(),
            'has_more' => $paginated->hasMorePages(),
        ]);
    }

    /**
     * POST /api/admin/approvals/day-starts/{id}/action
     * Approve or reject a Day Start / Tour Plan session.
     */
    public function actOnDayStart(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $isSuperAdmin = $user->hasRole(['Super Admin', 'super_admin']);

        if (!$user->can('day-start.approve') && !$isSuperAdmin && !$user->hasRole(['Admin', 'admin', 'Manager', 'VP'])) {
            return response()->json(['success' => false, 'message' => 'Unauthorized: You do not have permission to approve tour plans.'], 403);
        }

        $request->validate([
            'status' => 'required|in:approved,rejected',
            'reason' => 'nullable|string|max:500',
        ]);

        $dayStart = DayStart::findOrFail($id);

        if ($dayStart->user_id === $user->id && !$isSuperAdmin) {
            return response()->json(['success' => false, 'message' => 'Conflict of Interest: You cannot approve your own tour plan.'], 403);
        }

        $actionableIds = DataScope::actionableSubordinateIds('day-start', $user);
        if ($actionableIds !== null && !in_array($dayStart->user_id, $actionableIds, true)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized: You can only approve tour plans for your team subordinates.'], 403);
        }

        $oldStatus = $dayStart->status;
        $newStatus = $request->input('status');

        $dayStart->disableAuditLog = true;
        $dayStart->update([
            'status' => $newStatus,
            'approved_by' => $user->id,
        ]);

        // Auto-update system-generated travel expenses linked to this day start
        $dayStart->expenses()
            ->where('is_system_generated', true)
            ->update([
                'status' => $newStatus,
                'approved_by' => $user->id,
            ]);

        AuditLog::logEvent(
            event: 'daystart.' . $newStatus,
            auditable: $dayStart,
            oldValues: ['status' => $oldStatus],
            newValues: ['status' => $newStatus, 'approved_by' => $user->id],
            reason: $request->input('reason') ?: ('Day Start ' . ucfirst($newStatus) . ' via Mobile by ' . $user->name),
            causerUserId: $user->id
        );

        return response()->json([
            'success' => true,
            'status' => $newStatus,
            'message' => 'Tour plan has been ' . ($newStatus === 'approved' ? 'approved' : 'rejected') . ' successfully.',
        ]);
    }

    /**
     * PATCH /api/admin/approvals/day-starts/{id}
     * Adjust Tour Plan / Day Start details (Opening/Closing KM, Vehicle, Purpose, Remarks)
     */
    public function updateDayStart(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $isSuperAdmin = $user->hasRole(['Super Admin', 'super_admin']);
        $canEdit = $user->can('day-start.approve') || $user->can('day-start.manage') || $isSuperAdmin || $user->hasRole(['Admin', 'admin', 'Manager', 'VP']);

        if (!$canEdit) {
            return response()->json(['success' => false, 'message' => 'Unauthorized to edit tour plan.'], 403);
        }

        $dayStart = DayStart::with(['user', 'vehicleType'])->findOrFail($id);

        if ($dayStart->user_id === $user->id && !$isSuperAdmin) {
            return response()->json(['success' => false, 'message' => 'Conflict of Interest: You cannot edit your own tour plan.'], 403);
        }

        $visibleIds = DataScope::visibleUserIds('day-start', $user);
        if ($visibleIds !== null && !in_array($dayStart->user_id, $visibleIds, true)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized: You can only edit tour plans for your team subordinates.'], 403);
        }

        $request->validate([
            'opening_km' => 'nullable|numeric|min:0',
            'closing_km' => 'nullable|numeric|min:0',
            'visit_place' => 'nullable|string|max:255',
            'vehicle_no' => 'nullable|string|max:50',
            'vehicle_type_id' => 'nullable|exists:vehicle_types,id',
            'tour_purpose_id' => 'nullable|exists:tour_purposes,id',
            'tour_type_id' => 'nullable|exists:tour_types,id',
            'remarks' => 'nullable|string|max:1000',
            'reason' => 'nullable|string|max:500',
        ]);

        $openingKm = $request->filled('opening_km') ? (float) $request->opening_km : $dayStart->opening_km;
        $closingKm = $request->filled('closing_km') ? (float) $request->closing_km : $dayStart->closing_km;

        if ($openingKm !== null && $closingKm !== null && $closingKm < $openingKm) {
            return response()->json([
                'success' => false,
                'message' => "Closing KM ({$closingKm}) cannot be less than Opening KM ({$openingKm}).",
            ], 422);
        }

        $oldValues = $dayStart->only([
            'opening_km', 'closing_km', 'visit_place', 'vehicle_no',
            'vehicle_type_id', 'tour_purpose_id', 'tour_type_id', 'remarks'
        ]);

        $updateData = [];
        if ($request->has('opening_km')) $updateData['opening_km'] = $openingKm;
        if ($request->has('closing_km')) $updateData['closing_km'] = $closingKm;
        if ($request->has('visit_place')) $updateData['visit_place'] = $request->visit_place;
        if ($request->has('vehicle_no')) $updateData['vehicle_no'] = $request->vehicle_no;
        if ($request->has('vehicle_type_id')) $updateData['vehicle_type_id'] = $request->vehicle_type_id;
        if ($request->has('tour_purpose_id')) $updateData['tour_purpose_id'] = $request->tour_purpose_id;
        if ($request->has('tour_type_id')) $updateData['tour_type_id'] = $request->tour_type_id;
        if ($request->has('remarks')) $updateData['remarks'] = $request->remarks;

        $dayStart->update($updateData);

        // Recalculate trip distance & fuel allowance if closing and opening are both present
        if ($openingKm !== null && $closingKm !== null && $dayStart->vehicle_type_id) {
            $claimedKm = max(0, $closingKm - $openingKm);
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
                    'opening_km' => $openingKm,
                    'closing_km' => $closingKm,
                    'distance_km' => $claimedKm,
                    'applied_rate_per_km' => $appliedRate,
                    'applied_rate_source' => $rateSource,
                    'total_amount' => $totalAmount,
                    'date' => $dayStart->date,
                    'status' => 'completed',
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
                        'status' => $dayStart->status === 'approved' ? 'approved' : 'pending',
                        'category_id' => $travelCategory?->id,
                    ]
                );
            }
        }

        AuditLog::logEvent(
            event: 'daystart.updated',
            auditable: $dayStart,
            oldValues: $oldValues,
            newValues: $updateData,
            reason: $request->input('reason') ?: ('Tour plan adjusted via Mobile by ' . $user->name),
            causerUserId: $user->id
        );

        return response()->json([
            'success' => true,
            'message' => 'Tour plan updated successfully.',
            'data' => $dayStart,
        ]);
    }

    /**
     * GET /api/admin/approvals/expenses
     * Paginated list of team expense claims for approval.
     */
    public function expenses(Request $request): JsonResponse
    {
        $user = $request->user();
        $isSuperAdmin = $user->hasRole(['Super Admin', 'super_admin']);
        $isAdmin = $user->hasRole(['Admin', 'admin']);
        $canFinalApprove = $user->can('expenses.final_approve') || $user->hasRole(['Super Admin', 'Admin', 'Finance', 'HR', 'VP']);
        $canTempApprove = $user->can('expenses.approve') || $user->can('expenses.manage') || $canFinalApprove || $user->hasRole(['Manager', 'VP']);

        if (!$canTempApprove && !$canFinalApprove) {
            return response()->json(['success' => false, 'message' => 'Unauthorized to approve expenses.'], 403);
        }

        $query = ExpenseBill::with([
            'user:id,name,employee_id,department,position,reporting_to',
            'category:id,name',
            'items',
            'tempApprovedBy:id,name',
            'approvedBy:id,name',
            'rejectedBy:id,name',
        ]);

        // Data scoping — using the exact same visibleUserIds as the admin panel
        if (!$isSuperAdmin) {
            $query->where('user_id', '!=', $user->id); // Conflict of interest: cannot approve self
            $visibleIds = DataScope::visibleUserIds('expenses', $user);
            if ($visibleIds !== null) {
                $query->whereIn('user_id', $visibleIds);
            }
        }

        // Status filter: pending (level 1), temp_approved (level 2), approved, rejected, all
        $status = $request->input('status', 'pending');
        if ($status === 'pending') {
            $query->where('status', 'pending');
        } elseif ($status === 'temp_approved') {
            $query->where('status', 'temp_approved');
        } elseif ($status === 'approved') {
            $query->where('status', 'approved');
        } elseif ($status === 'rejected') {
            $query->whereIn('status', ['rejected', 'manager_rejected']);
        }

        // Filter by specific employee (user_id / employee_id)
        $employeeId = $request->input('user_id', $request->input('employee_id'));
        if ($employeeId && $employeeId !== 'all') {
            $query->where('user_id', $employeeId);
        }

        // Search
        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->whereHas('user', function ($uq) use ($search) {
                    $uq->where('name', 'like', "%{$search}%")
                       ->orWhere('employee_id', 'like', "%{$search}%");
                })->orWhere('amount', 'like', "%{$search}%")
                  ->orWhereHas('category', function ($cq) use ($search) {
                      $cq->where('name', 'like', "%{$search}%");
                  });
            });
        }

        $perPage = max(1, min(50, (int) $request->input('per_page', 15)));
        $paginated = $query->orderBy('date', 'desc')->orderBy('created_at', 'desc')->paginate($perPage);

        $actionableIds = DataScope::actionableSubordinateIds('expenses', $user);

        $formattedItems = collect($paginated->items())->map(function ($expense) use ($user, $isSuperAdmin, $isAdmin, $canTempApprove, $canFinalApprove, $actionableIds) {
            $notSelf = ($expense->user_id !== $user->id) || $isSuperAdmin;
            $isSubordinate = ($actionableIds === null || in_array($expense->user_id, $actionableIds, true));

            $isDirectManager = ($expense->user && $expense->user->reporting_to === $user->id)
                || $isSuperAdmin
                || $isAdmin
                || $user->can('expenses.manage')
                || (empty($expense->user?->reporting_to) && $user->can('expenses.view-all'));

            $canL1 = $notSelf && $canTempApprove && $isDirectManager && ($expense->status === 'pending');
            $canL2 = $notSelf && $canFinalApprove && ($expense->status === 'temp_approved');

            if ($isSuperAdmin && in_array($expense->status, ['pending', 'manager_rejected'], true)) {
                $canL2 = true;
            }

            return [
                'id' => $expense->id,
                'user_id' => $expense->user_id,
                'employee_name' => $expense->user?->name ?? 'Unknown',
                'employee_role' => $expense->user?->role_name ?? 'Field Worker',
                'employee_id' => $expense->user?->employee_id ?? '—',
                'amount' => (float) $expense->amount,
                'amount_formatted' => number_format((float) $expense->amount, 2),
                'category_name' => $expense->is_system_generated ? 'System Travel Allowance' : ($expense->category?->name ?? 'General'),
                'is_system_generated' => (bool) $expense->is_system_generated,
                'date' => $expense->date ? $expense->date->format('Y-m-d') : null,
                'date_formatted' => $expense->date ? $expense->date->format('d M Y') : '—',
                'bill_photo_url' => $expense->bill_photo ? asset('storage/' . $expense->bill_photo) : null,
                'is_after_day_end' => (bool) $expense->is_after_day_end,
                'status' => $expense->status,
                'temp_approved_by' => $expense->tempApprovedBy?->name,
                'temp_approved_at' => $expense->temp_approved_at ? $expense->temp_approved_at->format('d M Y, h:i A') : null,
                'temp_approved_note' => $expense->temp_approved_note,
                'approved_by' => $expense->approvedBy?->name,
                'approved_at' => $expense->approved_at ? $expense->approved_at->format('d M Y, h:i A') : null,
                'final_note' => $expense->final_note,
                'rejected_by' => $expense->rejectedBy?->name,
                'rejected_at' => $expense->rejected_at ? $expense->rejected_at->format('d M Y, h:i A') : null,
                'rejection_reason' => $expense->rejection_reason,
                'items' => $expense->items->map(function ($it) {
                    return [
                        'item_name' => $it->item_name,
                        'quantity' => (float) $it->quantity,
                        'unit_price' => (float) $it->unit_price,
                        'total_price' => (float) $it->total_price,
                    ];
                }),
                'can_temp_approve' => $canL1,
                'can_manager_reject' => $canL1,
                'can_final_approve' => $canL2,
                'can_final_reject' => $notSelf && $canFinalApprove && in_array($expense->status, ['temp_approved', 'manager_rejected'], true),
                'created_at' => $expense->created_at?->diffForHumans(),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $formattedItems,
            'current_page' => $paginated->currentPage(),
            'last_page' => $paginated->lastPage(),
            'total' => $paginated->total(),
            'has_more' => $paginated->hasMorePages(),
        ]);
    }

    /**
     * POST /api/admin/approvals/expenses/{id}/action
     * Double approval / rejection handler for expense claims.
     */
    public function actOnExpense(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $isSuperAdmin = $user && $user->hasRole(['Super Admin', 'super_admin']);
        $isAdmin = $user && $user->hasRole(['Admin', 'admin']);
        $canFinalApprove = $user ? ($user->can('expenses.final_approve') || $user->hasRole(['Super Admin', 'Admin', 'Finance', 'HR', 'VP'])) : false;
        $canTempApprove = $user ? ($user->can('expenses.approve') || $user->can('expenses.manage') || $canFinalApprove || $user->hasRole(['Manager', 'VP'])) : false;

        $expense = ExpenseBill::with('user')->findOrFail($id);

        if ($expense->user_id === $user->id && !$isSuperAdmin) {
            return response()->json(['success' => false, 'message' => 'Conflict of Interest: You cannot approve or reject your own expense claim.'], 403);
        }

        $actionableIds = DataScope::actionableSubordinateIds('expenses', $user);
        if ($actionableIds !== null && !in_array($expense->user_id, $actionableIds, true)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized: You can only approve expenses for your subordinates.'], 403);
        }

        $action = $request->input('action');
        $note = trim($request->input('note', ''));

        $isDirectManager = ($expense->user && $expense->user->reporting_to === $user->id)
            || $isSuperAdmin
            || $isAdmin
            || $user->can('expenses.manage')
            || (empty($expense->user?->reporting_to) && $user->can('expenses.view-all'));

        if ($action === 'temp_approve') {
            if (!$canTempApprove) {
                return response()->json(['success' => false, 'message' => 'Unauthorized for Level 1 verification.'], 403);
            }
            if (!$isDirectManager) {
                return response()->json(['success' => false, 'message' => 'Unauthorized: Only the direct reporting manager can verify Level 1.'], 403);
            }
            if ($expense->status !== 'pending' && !$isSuperAdmin) {
                return response()->json(['success' => false, 'message' => 'This expense is already processed or verified.'], 422);
            }

            $expense->update([
                'status' => 'temp_approved',
                'temp_approved_by' => $user->id,
                'temp_approved_at' => now(),
                'temp_approved_note' => $note ?: null,
            ]);

            ActivityNotificationService::notifyExpenseTempApproved($expense);

            AuditLog::logEvent(
                event: 'expense.temp_approved',
                auditable: $expense,
                oldValues: ['status' => 'pending'],
                newValues: ['status' => 'temp_approved', 'temp_approved_by' => $user->id],
                reason: $note ?: 'Expense verified via Mobile by ' . $user->name,
                causerUserId: $user->id
            );

            return response()->json([
                'success' => true,
                'status' => 'temp_approved',
                'message' => 'Expense verified by manager (Level 1). Forwarded for final approval.',
            ]);
        } elseif ($action === 'final_approve' || $action === 'approved') {
            if (!$canFinalApprove) {
                return response()->json(['success' => false, 'message' => 'Unauthorized: Final approval requires VP, HR, or Finance role.'], 403);
            }

            if ($expense->status === 'pending' && !$isSuperAdmin) {
                return response()->json(['success' => false, 'message' => 'Cannot Final Approve: Level 1 manager verification is required first.'], 422);
            }

            $oldStatus = $expense->status;
            $expense->update([
                'status' => 'approved',
                'approved_by' => $user->id,
                'approved_at' => now(),
                'final_note' => $note ?: null,
            ]);

            ActivityNotificationService::notifyExpenseFinalApproved($expense);

            AuditLog::logEvent(
                event: 'expense.approved',
                auditable: $expense,
                oldValues: ['status' => $oldStatus],
                newValues: ['status' => 'approved', 'approved_by' => $user->id],
                reason: $note ?: 'Expense final approved via Mobile by ' . $user->name,
                causerUserId: $user->id
            );

            return response()->json([
                'success' => true,
                'status' => 'approved',
                'message' => 'Expense final approved successfully.',
            ]);
        } elseif ($action === 'manager_reject') {
            if (!$canTempApprove || !$isDirectManager) {
                return response()->json(['success' => false, 'message' => 'Unauthorized to reject expense.'], 403);
            }
            if (empty($note)) {
                return response()->json(['success' => false, 'message' => 'Rejection reason is required.'], 422);
            }

            $expense->update([
                'status' => 'manager_rejected',
                'rejected_by' => $user->id,
                'rejected_at' => now(),
                'rejection_reason' => $note,
            ]);

            ActivityNotificationService::notifyExpenseRejected($expense, $note);

            AuditLog::logEvent(
                event: 'expense.manager_rejected',
                auditable: $expense,
                oldValues: ['status' => 'pending'],
                newValues: ['status' => 'manager_rejected', 'rejected_by' => $user->id],
                reason: $note,
                causerUserId: $user->id
            );

            return response()->json([
                'success' => true,
                'status' => 'manager_rejected',
                'message' => 'Expense rejected by manager.',
            ]);
        } elseif ($action === 'reject' || $action === 'final_reject') {
            if (!$canFinalApprove && !$isSuperAdmin) {
                return response()->json(['success' => false, 'message' => 'Unauthorized to final reject expense.'], 403);
            }
            if (empty($note)) {
                return response()->json(['success' => false, 'message' => 'Rejection reason is required.'], 422);
            }

            $oldStatus = $expense->status;
            $expense->update([
                'status' => 'rejected',
                'rejected_by' => $user->id,
                'rejected_at' => now(),
                'rejection_reason' => $note,
            ]);

            ActivityNotificationService::notifyExpenseRejected($expense, $note);

            AuditLog::logEvent(
                event: 'expense.rejected',
                auditable: $expense,
                oldValues: ['status' => $oldStatus],
                newValues: ['status' => 'rejected', 'rejected_by' => $user->id],
                reason: $note,
                causerUserId: $user->id
            );

            return response()->json([
                'success' => true,
                'status' => 'rejected',
                'message' => 'Expense final rejected.',
            ]);
        }

        return response()->json(['success' => false, 'message' => 'Invalid approval action.'], 422);
    }

    /**
     * PATCH /api/admin/approvals/expenses/{id}
     * Adjust/edit team subordinate's expense claim from Mobile Admin / Approvals Hub.
     */
    public function updateExpense(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $isSuperAdmin = $user->hasRole(['Super Admin', 'super_admin']);
        $isAdmin = $user->hasRole(['Admin', 'admin']);
        $canManage = $user->can('expenses.manage') || $user->can('expenses.approve') || $user->can('expenses.final_approve') || $user->hasRole(['Manager', 'VP', 'Finance', 'HR', 'Admin', 'Super Admin']);

        if (!$canManage) {
            return response()->json(['success' => false, 'message' => 'Unauthorized to edit expenses.'], 403);
        }

        $expense = ExpenseBill::with(['items', 'user'])->findOrFail($id);

        // Conflict of Interest: Cannot edit own expense administratively unless super admin
        if ($expense->user_id === $user->id && !$isSuperAdmin) {
            return response()->json([
                'success' => false,
                'message' => 'Conflict of Interest: You cannot administratively edit your own expense.',
            ], 403);
        }

        // Data scoping: only allow editing subordinates
        if (!$isSuperAdmin) {
            $actionableIds = DataScope::actionableSubordinateIds('expenses', $user);
            if ($actionableIds !== null && !in_array($expense->user_id, $actionableIds, true)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized: You can only edit expenses for subordinates strictly below you.',
                ], 403);
            }
        }

        $validated = $request->validate([
            'amount' => 'required|numeric|min:0',
            'category_id' => 'nullable|exists:expense_categories,id',
            'date' => 'nullable|date',
            'reason' => 'nullable|string|max:500',
            'rejection_reason' => 'nullable|string|max:1000',
            'final_note' => 'nullable|string|max:1000',
            'temp_approved_note' => 'nullable|string|max:1000',
            'items' => 'nullable|array',
            'items.*.item_name' => 'required_with:items|string|max:255',
            'items.*.quantity' => 'nullable|numeric|min:0',
            'items.*.unit_price' => 'nullable|numeric|min:0',
            'items.*.total_price' => 'nullable|numeric|min:0',
        ]);

        $oldValues = [
            'amount' => (float) $expense->amount,
            'category_id' => $expense->category_id,
            'date' => $expense->date?->toDateString(),
        ];

        $updateData = [
            'amount' => (float) $validated['amount'],
        ];

        if (!empty($validated['category_id']) && !$expense->is_system_generated) {
            $updateData['category_id'] = $validated['category_id'];
        }

        if (!empty($validated['date'])) {
            $updateData['date'] = Carbon::parse($validated['date'])->toDateString();
        }

        if (array_key_exists('rejection_reason', $validated)) {
            $updateData['rejection_reason'] = $validated['rejection_reason'];
        }
        if (array_key_exists('final_note', $validated)) {
            $updateData['final_note'] = $validated['final_note'];
        }
        if (array_key_exists('temp_approved_note', $validated)) {
            $updateData['temp_approved_note'] = $validated['temp_approved_note'];
        }

        $expense->update($updateData);

        // Update items if provided
        if (isset($validated['items']) && is_array($validated['items'])) {
            $expense->items()->delete();
            foreach ($validated['items'] as $item) {
                $qty = isset($item['quantity']) ? (float)$item['quantity'] : 1;
                $unitPrice = isset($item['unit_price']) ? (float)$item['unit_price'] : (float)($item['total_price'] ?? 0);
                $tot = isset($item['total_price']) ? (float)$item['total_price'] : ($qty * $unitPrice);
                $expense->items()->create([
                    'item_name' => $item['item_name'],
                    'quantity' => $qty,
                    'unit_price' => $unitPrice,
                    'total_price' => $tot,
                ]);
            }
        }

        // If system-generated travel allowance linked to day start, keep synced
        if ($expense->is_system_generated && $expense->day_start_id) {
            \App\Models\DayStart::where('id', $expense->day_start_id)->update([
                'total_amount' => (float) $validated['amount'],
            ]);
            \App\Models\Trip::where('day_start_id', $expense->day_start_id)->update([
                'total_amount' => (float) $validated['amount'],
            ]);
        }

        ActivityNotificationService::notifyExpenseUpdated($expense, $oldValues['amount'], $user);

        AuditLog::logEvent(
            event: 'expense.updated',
            auditable: $expense,
            oldValues: $oldValues,
            newValues: $updateData,
            reason: $request->input('reason') ?: ('Expense adjusted via Mobile by ' . $user->name),
            causerUserId: $user->id
        );

        $expense->load(['category', 'items', 'user']);

        return response()->json([
            'success' => true,
            'message' => 'Expense claim updated successfully.',
            'data' => $expense,
        ]);
    }
}
