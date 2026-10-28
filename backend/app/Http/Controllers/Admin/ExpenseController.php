<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ExpenseBill;
use App\Models\User;
use App\Services\ActivityNotificationService;
use App\Services\DataScope;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Yajra\DataTables\Facades\DataTables;

class ExpenseController extends Controller
{
    public function index(Request $request)
    {
        $visibleIds = DataScope::visibleUserIds('expenses');

        $users = User::with('roles')
            ->where('is_active', true)
            ->when($visibleIds !== null, fn($q) => $q->whereIn('id', $visibleIds))
            ->orderBy('name')
            ->get();

        $categories = \App\Models\ExpenseCategory::where('is_active', true)->orderBy('name')->get();

        $authUser = auth()->user();
        $isSuperAdmin = $authUser ? $authUser->hasRole(['Super Admin', 'super_admin']) : false;
        $canFinalApprove = $authUser ? ($authUser->can('expenses.final_approve') || $isSuperAdmin) : false;
        $canTempApprove = $authUser ? ($authUser->can('expenses.approve') || $isSuperAdmin) : false;
        $canEdit = $authUser ? ($authUser->can('expenses.edit') || $authUser->can('expenses.manage') || $isSuperAdmin) : false;

        if ($request->wantsJson()) {
            $expensesQuery = ExpenseBill::with(['user.roles', 'category', 'items']);
            if ($visibleIds !== null) {
                $expensesQuery->whereIn('user_id', $visibleIds);
            }
            $expenses = $expensesQuery->orderBy('date', 'desc')->orderBy('id', 'desc')->get();

            return response()->json([
                'success' => true,
                'records' => $expenses->map(function ($expense) use ($canEdit) {
                    return [
                        'id' => $expense->id,
                        'is_system_generated' => (bool) $expense->is_system_generated,
                        'can_edit' => $canEdit,
                        'amount' => (float) $expense->amount,
                        'status' => $expense->status,
                    ];
                }),
            ]);
        }

        return view('admin.expenses.index', compact('users', 'categories', 'canTempApprove', 'canFinalApprove', 'canEdit'));
    }

    public function data(Request $request)
    {
        $query = ExpenseBill::with(['user.roles', 'category', 'items', 'tempApprovedBy', 'approvedBy', 'rejectedBy'])
            ->select('expense_bills.*');

        // Data scoping: restrict to visible team members
        $visibleIds = DataScope::visibleUserIds('expenses');
        if ($visibleIds !== null) {
            $query->whereIn('user_id', $visibleIds);
        }

        if ($request->filled('user_id')) {
            if ($visibleIds === null || in_array($request->user_id, $visibleIds)) {
                $query->where('user_id', $request->user_id);
            } else {
                $query->whereRaw('1=0');
            }
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('timing')) {
            if ($request->timing === 'after_day_end') {
                $query->where('is_after_day_end', true);
            } elseif ($request->timing === 'during_shift') {
                $query->where('is_after_day_end', false);
            }
        }

        if ($request->filled('month')) {
            $month = Carbon::parse($request->month);
            $query->whereMonth('date', $month->month)
                ->whereYear('date', $month->year);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('date', '<=', $request->date_to);
        }

        $search = $request->input('search.value');
        if (empty($search) && is_string($request->input('search'))) {
            $search = $request->input('search');
        }
        if (!empty($search) && is_string($search)) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('user', fn($uq) => $uq->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('category', fn($cq) => $cq->where('name', 'like', "%{$search}%"))
                    ->orWhere('amount', 'like', "%{$search}%");
            });
        }

        $authUser = auth()->user();
        $isSuperAdmin = $authUser ? $authUser->hasRole(['Super Admin', 'super_admin']) : false;
        $canFinalApprove = $authUser ? ($authUser->can('expenses.final_approve') || $isSuperAdmin) : false;
        $canTempApprove = $authUser ? ($authUser->can('expenses.approve') || $isSuperAdmin) : false;
        $canEdit = $authUser ? ($authUser->can('expenses.edit') || $authUser->can('expenses.manage') || $isSuperAdmin) : false;
        $actionableIds = DataScope::actionableSubordinateIds('expenses');

        return DataTables::of($query)
            ->addColumn('date', function ($expense) {
                return [
                    'formatted' => $expense->date ? $expense->date->format('d M Y') : '—',
                    'raw' => $expense->date ? $expense->date->toDateString() : '',
                ];
            })
            ->addColumn('worker', function ($expense) {
                return [
                    'name' => $expense->user ? $expense->user->name_with_role : 'Unknown',
                    'role' => $expense->user?->role_name ?? ($expense->user?->roles->first()?->name ?? 'Field Worker'),
                ];
            })
            ->addColumn('category', function ($expense) {
                return [
                    'name' => $expense->is_system_generated ? 'System Travel' : ($expense->category?->name ?? 'N/A'),
                    'is_system_generated' => (bool) $expense->is_system_generated,
                ];
            })
            ->addColumn('amount', function ($expense) {
                return [
                    'formatted' => number_format((float) $expense->amount, 2),
                    'raw' => (float) $expense->amount,
                ];
            })
            ->addColumn('timing', function ($expense) {
                return [
                    'is_after_day_end' => (bool) $expense->is_after_day_end,
                ];
            })
            ->addColumn('photo', function ($expense) {
                return [
                    'url' => $expense->bill_photo ? asset('storage/' . $expense->bill_photo) : null,
                    'is_system_generated' => (bool) $expense->is_system_generated,
                ];
            })
            ->addColumn('status', function ($expense) {
                return [
                    'code' => $expense->status,
                    'temp_approved_by' => $expense->tempApprovedBy?->name,
                    'temp_approved_at' => $expense->temp_approved_at ? $expense->temp_approved_at->format('d M Y, h:i A') : null,
                    'temp_approved_note' => $expense->temp_approved_note,
                    'approved_by' => $expense->approvedBy?->name,
                    'approved_at' => $expense->approved_at ? $expense->approved_at->format('d M Y, h:i A') : null,
                    'final_note' => $expense->final_note,
                    'rejected_by' => $expense->rejectedBy?->name,
                    'rejected_at' => $expense->rejected_at ? $expense->rejected_at->format('d M Y, h:i A') : null,
                    'rejection_reason' => $expense->rejection_reason,
                ];
            })
            ->addColumn('actions', function ($expense) use ($authUser, $isSuperAdmin, $canTempApprove, $canFinalApprove, $canEdit, $actionableIds) {
                $notSelf = ($expense->user_id !== $authUser->id) || $isSuperAdmin;
                $isSubordinate = ($actionableIds === null || in_array($expense->user_id, $actionableIds, true));
                $canAct = $notSelf && $isSubordinate;

                // Strict Direct Reporting Manager Check for Level 1 Verification:
                // Must be the direct reporting manager (reporting_to == authUser->id),
                // OR employee has no reporting manager assigned and user is Super Admin/Admin.
                $isDirectManager = ($expense->user && $expense->user->reporting_to === $authUser->id)
                    || ($isSuperAdmin)
                    || (empty($expense->user?->reporting_to) && $authUser->can('expenses.view-all'));

                $canL1 = $notSelf && $canTempApprove && $isDirectManager && ($expense->status === 'pending');

                // Level 2 Final Approval:
                // If expense has a direct reporting manager and is pending, Level 1 verification is required first.
                // Available when:
                // 1) Already verified by reporting manager (status === 'temp_approved')
                // 2) Overriding a manager rejection (status === 'manager_rejected')
                // 3) Employee has no reporting manager assigned (hasNoManager)
                // 4) User is Super Admin (emergency direct approval override)
                $hasNoManager = empty($expense->user?->reporting_to);
                $isEligibleForL2 = ($expense->status === 'temp_approved')
                    || ($expense->status === 'manager_rejected')
                    || ($expense->status === 'pending' && ($hasNoManager || $isSuperAdmin));

                $canL2 = $notSelf && $canFinalApprove && $isEligibleForL2;

                return [
                    'id' => $expense->id,
                    'user_name' => $expense->user ? $expense->user->name_with_role : 'Unknown',
                    'amount_formatted' => number_format((float) $expense->amount, 2),
                    'raw_amount' => (float) $expense->amount,
                    'category_id' => $expense->category_id,
                    'category_name' => $expense->is_system_generated ? 'System Travel' : ($expense->category?->name ?? 'N/A'),
                    'is_system_generated' => (bool) $expense->is_system_generated,
                    'raw_date' => $expense->date ? $expense->date->toDateString() : '',
                    'date_formatted' => $expense->date ? $expense->date->format('d M Y') : '—',
                    'is_after_day_end' => (bool) $expense->is_after_day_end,
                    'status' => $expense->status,
                    'rejection_reason' => $expense->rejection_reason,
                    'final_note' => $expense->final_note,
                    'temp_approved_note' => $expense->temp_approved_note,
                    'temp_approved_by' => $expense->tempApprovedBy?->name,
                    'temp_approved_at' => $expense->temp_approved_at ? $expense->temp_approved_at->format('d M Y, h:i A') : null,
                    'approved_by' => $expense->approvedBy?->name,
                    'approved_at' => $expense->approved_at ? $expense->approved_at->format('d M Y, h:i A') : null,
                    'rejected_by' => $expense->rejectedBy?->name,
                    'rejected_at' => $expense->rejected_at ? $expense->rejected_at->format('d M Y, h:i A') : null,
                    'bill_photo' => $expense->bill_photo,
                    'bill_photo_url' => $expense->bill_photo ? asset('storage/' . $expense->bill_photo) : null,
                    'day_start_id' => $expense->day_start_id,
                    'day_report_url' => $expense->day_start_id ? route('admin.expenses.reports.daily', $expense->day_start_id) : null,
                    'update_status_url' => route('admin.expenses.updateStatus', $expense->id),
                    'update_url' => route('admin.expenses.update', $expense->id),
                    'can_edit' => $canEdit,
                    'is_direct_manager' => $isDirectManager,
                    'can_temp_approve' => $canL1,
                    'can_manager_reject' => $canL1,
                    'can_final_approve' => $canL2,
                    'can_final_reject' => $notSelf && $canFinalApprove && in_array($expense->status, ['temp_approved', 'pending', 'manager_rejected'], true),
                ];
            })
            ->rawColumns(['actions'])
            ->make(true);
    }

    public function updateStatus(Request $request, ExpenseBill $expense)
    {
        $user = auth()->user();

        if ($expense->user_id === $user->id && !$user->hasRole(['Super Admin', 'super_admin'])) {
            return response()->json([
                'success' => false,
                'message' => 'Conflict of Interest: You cannot approve or reject your own expense.',
            ], 403);
        }

        $actionableIds = DataScope::actionableSubordinateIds('expenses');
        if ($actionableIds !== null && !in_array($expense->user_id, $actionableIds, true)) {
            abort(403, 'Unauthorized: You can only approve or reject expenses for subordinates strictly below you.');
        }

        $action = $request->input('action') ?? $request->input('status');
        $note = $request->input('note') ?? $request->input('reason');

        $isSuperAdmin = $user && $user->hasRole(['Super Admin', 'super_admin']);
        $canFinalApprove = $user ? ($user->can('expenses.final_approve') || $isSuperAdmin) : false;
        $canTempApprove = $user ? ($user->can('expenses.approve') || $isSuperAdmin) : false;

        $isDirectManager = ($expense->user && $expense->user->reporting_to === $user->id)
            || ($isSuperAdmin)
            || (empty($expense->user?->reporting_to) && $user->can('expenses.view-all'));

        if ($action === 'temp_approve') {
            if (!$canTempApprove) {
                return response()->json(['success' => false, 'message' => 'Unauthorized for Level 1 verification.'], 403);
            }
            if (!$isDirectManager) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized: Only the employee\'s direct reporting manager can perform Level 1 verification.',
                ], 403);
            }
            if ($expense->status !== 'pending' && !$isSuperAdmin) {
                return response()->json([
                    'success' => false,
                    'message' => 'This expense is already processed or verified.',
                ], 422);
            }

            $expense->update([
                'status' => 'temp_approved',
                'temp_approved_by' => $user->id,
                'temp_approved_at' => now(),
                'temp_approved_note' => $note,
            ]);

            ActivityNotificationService::notifyExpenseTempApproved($expense);

            $msg = 'Expense verified by reporting manager (Level 1). Forwarded to HR for final approval.';
        } elseif ($action === 'final_approve' || $action === 'approved') {
            if (!$canFinalApprove) {
                return response()->json(['success' => false, 'message' => 'Unauthorized: Final approval requires final approval permission.'], 403);
            }

            // Strict Gatekeeping: Cannot final-approve while pending if employee has a reporting manager who hasn't verified yet
            $hasReportingManager = !empty($expense->user?->reporting_to);
            if ($expense->status === 'pending' && $hasReportingManager && !$isSuperAdmin) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot Final Approve: This claim requires Level 1 verification from the direct reporting manager first.',
                ], 422);
            }

            $expense->update([
                'status' => 'approved',
                'approved_by' => $user->id,
                'approved_at' => now(),
                'final_note' => $note,
            ]);

            ActivityNotificationService::notifyExpenseFinalApproved($expense);

            $msg = 'Expense approved successfully (Final Approval). Sent for reimbursement payout.';
        } elseif ($action === 'manager_reject') {
            if (!$canTempApprove || !$isDirectManager) {
                return response()->json(['success' => false, 'message' => 'Unauthorized to reject expense.'], 403);
            }
            if (empty(trim($note ?? ''))) {
                return response()->json([
                    'success' => false,
                    'message' => 'Please provide a reason for the rejection.',
                    'errors' => ['note' => ['Please provide a reason for the rejection.']]
                ], 422);
            }
            $expense->update([
                'status' => 'manager_rejected',
                'rejected_by' => $user->id,
                'rejected_at' => now(),
                'rejection_reason' => $note,
            ]);

            \App\Models\UserNotification::create([
                'user_id' => $expense->user_id,
                'title' => 'Expense Rejected by Manager',
                'body' => "Your expense of ₹" . number_format((float) $expense->amount, 2) . " (" . ($expense->category?->name ?? 'Claim') . ") was rejected. Reason: " . $note,
                'screen' => 'ExpenseList',
                'is_read' => false,
            ]);

            $msg = 'Expense rejected by manager.';
        } elseif ($action === 'rejected' || $action === 'final_reject') {
            if (empty(trim($note ?? ''))) {
                return response()->json([
                    'success' => false,
                    'message' => 'Please provide a reason for the rejection.',
                    'errors' => ['note' => ['Please provide a reason for the rejection.']]
                ], 422);
            }
            if (!$canFinalApprove && !$isSuperAdmin) {
                return response()->json(['success' => false, 'message' => 'Unauthorized: Final rejection requires HR or Admin role.'], 403);
            }

            $expense->update([
                'status' => 'rejected',
                'rejected_by' => $user->id,
                'rejected_at' => now(),
                'rejection_reason' => $note,
            ]);

            ActivityNotificationService::notifyExpenseFinalRejected($expense, $note);

            $msg = 'Expense rejected by HR.';
        } else {
            return response()->json(['success' => false, 'message' => 'Invalid approval action specified.'], 422);
        }

        $expense->load(['tempApprovedBy', 'approvedBy', 'rejectedBy']);

        if ($user && in_array($expense->status, ['approved', 'rejected', 'manager_rejected'])) {
            try {
                $user->unreadNotifications()
                    ->where('data->expense_id', $expense->id)
                    ->get()
                    ->each->markAsRead();
            } catch (\Throwable $e) {
                // Silently ignore notification sync error
            }
        }

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'status' => $expense->status,
                'message' => $msg,
                'record' => [
                    'id' => $expense->id,
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
                    'bill_photo' => $expense->bill_photo,
                    'bill_photo_url' => $expense->bill_photo ? asset('storage/' . $expense->bill_photo) : null,
                ]
            ]);
        }

        return back()->with('success', $msg);
    }

    public function update(Request $request, ExpenseBill $expense)
    {
        $user = auth()->user();

        if ($expense->user_id === $user->id && !$user->hasRole(['Super Admin', 'super_admin'])) {
            return response()->json([
                'success' => false,
                'message' => 'Conflict of Interest: You cannot administrative-edit your own expense.',
            ], 403);
        }

        $actionableIds = DataScope::actionableSubordinateIds('expenses');
        if ($actionableIds !== null && !in_array($expense->user_id, $actionableIds, true)) {
            abort(403, 'Unauthorized: You can only edit expenses for subordinates strictly below you.');
        }

        $validated = $request->validate([
            'amount' => 'required|numeric|min:0',
            'category_id' => 'nullable|exists:expense_categories,id',
            'date' => 'nullable|date',
            'status' => 'nullable|in:pending,temp_approved,approved,manager_rejected,rejected',
            'bill_photo' => 'nullable|image|max:20480',
            'rejection_reason' => 'nullable|string|max:1000',
            'final_note' => 'nullable|string|max:1000',
            'temp_approved_note' => 'nullable|string|max:1000',
        ]);

        $updateData = [
            'amount' => (float) $validated['amount'],
        ];

        if (isset($validated['category_id']) && !$expense->is_system_generated) {
            $updateData['category_id'] = $validated['category_id'];
        }

        if (isset($validated['date'])) {
            $updateData['date'] = Carbon::parse($validated['date'])->toDateString();
        }

        if ($request->hasFile('bill_photo')) {
            $updateData['bill_photo'] = $request->file('bill_photo')->store('expenses', 'public');
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

        if (isset($validated['status'])) {
            $updateData['status'] = $validated['status'];
            if ($validated['status'] === 'temp_approved') {
                $updateData['temp_approved_by'] = auth()->id();
                $updateData['temp_approved_at'] = now();
            } elseif ($validated['status'] === 'approved') {
                $updateData['approved_by'] = auth()->id();
                $updateData['approved_at'] = now();
            } elseif (in_array($validated['status'], ['rejected', 'manager_rejected'])) {
                $updateData['rejected_by'] = auth()->id();
                $updateData['rejected_at'] = now();
                if (!empty($validated['rejection_reason'])) {
                    \App\Models\UserNotification::create([
                        'user_id' => $expense->user_id,
                        'title' => 'Expense Rejected',
                        'body' => "Your expense of ₹" . number_format((float) $expense->amount, 2) . " was rejected. Reason: " . $validated['rejection_reason'],
                        'screen' => 'ExpenseList',
                        'is_read' => false,
                    ]);
                }
            }
        }

        $oldAmount = (float) $expense->amount;

        $expense->update($updateData);

        if ($expense->is_system_generated && $expense->day_start_id) {
            \App\Models\DayStart::where('id', $expense->day_start_id)->update([
                'total_amount' => (float) $validated['amount'],
            ]);
            \App\Models\Trip::where('day_start_id', $expense->day_start_id)->update([
                'total_amount' => (float) $validated['amount'],
            ]);
        }

        $expense->load(['category', 'tempApprovedBy', 'approvedBy', 'rejectedBy']);

        ActivityNotificationService::notifyExpenseUpdated($expense, $oldAmount, auth()->user());

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Expense updated successfully.',
                'record' => [
                    'id' => $expense->id,
                    'date_formatted' => $expense->date ? $expense->date->format('d M Y') : '—',
                    'raw_date' => $expense->date ? $expense->date->toDateString() : '',
                    'user_name' => $expense->user ? $expense->user->name_with_role : 'Unknown',
                    'category_id' => $expense->category_id,
                    'category_name' => $expense->is_system_generated ? 'System Travel' : ($expense->category?->name ?? 'N/A'),
                    'is_system_generated' => (bool) $expense->is_system_generated,
                    'can_edit' => true,
                    'raw_amount' => (float) $expense->amount,
                    'amount_formatted' => number_format((float) $expense->amount, 2),
                    'is_after_day_end' => (bool) $expense->is_after_day_end,
                    'bill_photo' => $expense->bill_photo,
                    'bill_photo_url' => $expense->bill_photo ? asset('storage/' . $expense->bill_photo) : null,
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
                    'day_start_id' => $expense->day_start_id,
                    'day_report_url' => $expense->day_start_id ? route('admin.expenses.reports.daily', $expense->day_start_id) : null,
                    'update_status_url' => route('admin.expenses.updateStatus', $expense->id),
                    'update_url' => route('admin.expenses.update', $expense->id),
                ],
            ]);
        }

        return back()->with('success', 'Expense updated successfully.');
    }
}
