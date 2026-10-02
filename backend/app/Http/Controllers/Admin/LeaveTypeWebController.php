<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LeaveBalance;
use App\Models\LeaveType;
use App\Models\User;
use Illuminate\Http\Request;

class LeaveTypeWebController extends Controller
{
    /**
     * List all leave types and rules.
     */
    public function index()
    {
        $leaveTypes = LeaveType::orderBy('id')->get();
        return view('admin.leave-types.index', compact('leaveTypes'));
    }

    /**
     * Store new leave type.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'              => 'required|string|max:100',
            'code'              => 'required|string|max:10|unique:leave_types,code',
            'days_per_year'     => 'required|integer|min:0',
            'accrual_frequency' => 'required|string|in:annual,monthly',
            'days_per_month'    => 'nullable|numeric|min:0',
            'carry_forward'     => 'boolean',
        ]);

        $leaveType = LeaveType::create([
            'name'              => $validated['name'],
            'code'              => strtoupper($validated['code']),
            'days_per_year'     => $validated['days_per_year'],
            'accrual_frequency' => $validated['accrual_frequency'],
            'days_per_month'    => $request->input('days_per_month', 0.00),
            'carry_forward'     => $request->has('carry_forward'),
            'is_active'         => true,
        ]);

        if ($leaveType->accrual_frequency === 'annual') {
            $this->syncBalancesToEmployees($leaveType);
        }

        return back()->with('success', 'Leave type created successfully.');
    }

    /**
     * Update leave type rules (e.g. days per year, accrual frequency, days per month).
     */
    public function update(Request $request, LeaveType $leaveType)
    {
        $validated = $request->validate([
            'name'              => 'required|string|max:100',
            'code'              => 'required|string|max:10|unique:leave_types,code,' . $leaveType->id,
            'days_per_year'     => 'required|integer|min:0',
            'accrual_frequency' => 'required|string|in:annual,monthly',
            'days_per_month'    => 'nullable|numeric|min:0',
            'carry_forward'     => 'boolean',
            'is_active'         => 'boolean',
        ]);

        $leaveType->update([
            'name'              => $validated['name'],
            'code'              => strtoupper($validated['code']),
            'days_per_year'     => $validated['days_per_year'],
            'accrual_frequency' => $validated['accrual_frequency'],
            'days_per_month'    => $request->input('days_per_month', 0.00),
            'carry_forward'     => $request->has('carry_forward'),
            'is_active'         => $request->has('is_active'),
        ]);

        if ($leaveType->accrual_frequency === 'annual') {
            $this->syncBalancesToEmployees($leaveType);
        } else {
            $this->syncBalancesToEmployees($leaveType);
        }

        return back()->with("success", "Leave rule updated for {$leaveType->name} ({$leaveType->code}).");
    }

    /**
     * Helper to sync allocated days of a leave type to all active users' leave_balances for current year.
     */
    private function syncBalancesToEmployees(LeaveType $leaveType): void
    {
        $year  = now()->year;
        $users = User::where('is_active', true)->pluck('id');
        $quota = $leaveType->accrual_frequency === 'monthly' ? $leaveType->days_per_month : $leaveType->days_per_year;

        foreach ($users as $userId) {
            LeaveBalance::updateOrCreate(
                [
                    'user_id'       => $userId,
                    'leave_type_id' => $leaveType->id,
                    'year'          => $year,
                ],
                [
                    'allocated' => $quota,
                ]
            );
        }
    }
}
