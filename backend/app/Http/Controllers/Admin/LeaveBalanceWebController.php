<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LeaveBalance;
use App\Models\User;
use App\Services\AttendanceService;
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
        $users = User::where('is_active', true)->orderBy('name')->get();

        // Ensure balances exist for all users for this year
        foreach ($users as $user) {
            $this->attendance->getOrCreateBalances($user->id, $year);
        }

        $query = LeaveBalance::with(['user', 'leaveType'])
            ->where('year', $year);

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('leave_type_id')) {
            $query->where('leave_type_id', $request->leave_type_id);
        }

        $balances   = $query->paginate(20);
        $leaveTypes = \App\Models\LeaveType::all();

        return view('admin.leave-balances.index', compact(
            'balances', 'users', 'leaveTypes', 'year'
        ));
    }

    /**
     * Admin manually adjusts an employee's leave balance.
     */
    public function update(Request $request, LeaveBalance $balance)
    {
        $validated = $request->validate([
            'allocated'       => 'required|numeric|min:0',
            'used'            => 'required|numeric|min:0',
            'carried_forward' => 'required|numeric|min:0',
        ]);

        $balance->update($validated);

        return back()->with('success', "Leave balance for {$balance->user->name} ({$balance->leaveType->code}) updated successfully.");
    }
}
