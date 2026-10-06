<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LeaveBalance;
use App\Models\LeaveType;
use App\Models\User;
use App\Services\AttendanceService;
use App\Services\DataScope;
use Illuminate\Http\Request;

class LeaveBalanceWebController extends Controller
{
    public function __construct(private readonly AttendanceService $attendance) {}

    /**
     * View and manage leave balances for all employees.
     */
    public function index(Request $request)
    {
        $year  = (int) $request->input('year', now()->year);
        $scopedUserIds = DataScope::visibleUserIds('leaves');

        $users = User::with('roles')
            ->where('is_active', true)
            ->when($scopedUserIds !== null, fn($q) => $q->whereIn('id', $scopedUserIds))
            ->orderBy('name')
            ->get();

        // Ensure balances exist for all users for this year
        foreach ($users as $user) {
            $this->attendance->getOrCreateBalances($user->id, $year);
        }

        $query = LeaveBalance::with(['user.roles', 'leaveType:id,name,code'])
            ->where('year', $year);

        if ($scopedUserIds !== null) {
            $query->whereIn('user_id', $scopedUserIds);
        }

        if ($request->filled('user_id')) {
            $uId = (int) $request->user_id;
            if ($scopedUserIds === null || in_array($uId, $scopedUserIds)) {
                $query->where('user_id', $uId);
            } else {
                $query->whereRaw('1=0');
            }
        }

        if ($request->filled('leave_type_id')) {
            $query->where('leave_type_id', (int) $request->leave_type_id);
        }

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->whereHas('user', function ($uq) use ($search) {
                    $uq->where('name', 'like', "%{$search}%")
                       ->orWhere('employee_id', 'like', "%{$search}%");
                })->orWhereHas('leaveType', function ($lq) use ($search) {
                    $lq->where('code', 'like', "%{$search}%")
                       ->orWhere('name', 'like', "%{$search}%");
                });
            });
        }

        // Summary totals across entire filtered dataset
        $totalAllocated      = (float) (clone $query)->sum('allocated');
        $totalCarriedForward = (float) (clone $query)->sum('carried_forward');
        $totalUsed           = (float) (clone $query)->sum('used');
        $totalAvailable      = max(0.0, ($totalAllocated + $totalCarriedForward) - $totalUsed);

        $balances = $query->orderBy('user_id')->paginate(25);
        $leaveTypes = LeaveType::orderBy('id')->get(['id', 'name', 'code']);

        $formattedBalances = $balances->map(fn ($b) => $this->formatBalance($b));

        $summaryData = [
            'total_allocated'       => $totalAllocated,
            'total_carried_forward' => $totalCarriedForward,
            'total_used'            => $totalUsed,
            'total_available'       => $totalAvailable,
        ];

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success'    => true,
                'year'       => $year,
                'summary'    => $summaryData,
                'balances'   => $formattedBalances,
                'users'      => $users->map(fn($u) => ['id' => $u->id, 'name' => $u->name, 'role_name' => $u->role_name]),
                'pagination' => [
                    'total'        => $balances->total(),
                    'current_page' => $balances->currentPage(),
                    'last_page'    => $balances->lastPage(),
                    'per_page'     => $balances->perPage(),
                    'from'         => $balances->firstItem(),
                    'to'           => $balances->lastItem(),
                ],
            ]);
        }

        return view('admin.leave-balances.index', compact(
            'balances',
            'formattedBalances',
            'users',
            'leaveTypes',
            'year',
            'summaryData'
        ));
    }

    /**
     * Admin manually adjusts an employee's leave balance.
     */
    public function update(Request $request, LeaveBalance $balance)
    {
        $validated = $request->validate([
            'allocated'       => 'required|numeric|min:0',
            'carried_forward' => 'required|numeric|min:0',
            'used'            => 'required|numeric|min:0',
        ]);

        $balance->update($validated);
        $balance->load(['user.roles', 'leaveType:id,name,code']);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Leave balance for {$balance->user->name} ({$balance->leaveType->code}) updated successfully.",
                'balance' => $this->formatBalance($balance),
            ]);
        }

        return back()->with('success', "Leave balance for {$balance->user->name} ({$balance->leaveType->code}) updated successfully.");
    }

    private function formatBalance(LeaveBalance $b): array
    {
        return [
            'id'                  => $b->id,
            'user_id'             => $b->user_id,
            'user_name'           => $b->user ? $b->user->name_with_role : 'Unknown',
            'user_emp_id'         => $b->user ? $b->user->role_name : '',
            'leave_type_id'       => $b->leave_type_id,
            'leave_type_code'     => $b->leaveType->code ?? 'L',
            'leave_type_name'     => $b->leaveType->name ?? 'Leave',
            'year'                => $b->year,
            'allocated'           => (float) $b->allocated,
            'carried_forward'     => (float) $b->carried_forward,
            'used'                => (float) $b->used,
            'available'           => (float) $b->available,
            'pending_days'        => (float) $b->pending_days,
            'effective_available' => (float) $b->effective_available,
        ];
    }
}
