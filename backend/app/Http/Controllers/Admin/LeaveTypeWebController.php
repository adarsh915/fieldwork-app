<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Leave;
use App\Models\LeaveBalance;
use App\Models\LeaveType;
use App\Models\User;
use Illuminate\Http\Request;

class LeaveTypeWebController extends Controller
{
    /**
     * List all leave types and rules.
     */
    public function index(Request $request)
    {
        $leaveTypes = LeaveType::orderBy('id')->get();

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success'    => true,
                'leaveTypes' => $leaveTypes->map(fn ($t) => $this->formatLeaveType($t)),
            ]);
        }

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
            'carry_forward'     => 'nullable|boolean',
        ]);

        $leaveType = LeaveType::create([
            'name'              => $validated['name'],
            'code'              => strtoupper($validated['code']),
            'days_per_year'     => (int) $validated['days_per_year'],
            'accrual_frequency' => $validated['accrual_frequency'],
            'days_per_month'    => (float) $request->input('days_per_month', 0.00),
            'carry_forward'     => $request->boolean('carry_forward'),
            'is_active'         => true,
        ]);

        $this->syncBalancesToEmployees($leaveType);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success'    => true,
                'message'    => "Leave rule '{$leaveType->name}' ({$leaveType->code}) created successfully.",
                'leave_type' => $this->formatLeaveType($leaveType),
            ]);
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
            'carry_forward'     => 'nullable|boolean',
            'is_active'         => 'nullable|boolean',
        ]);

        $leaveType->update([
            'name'              => $validated['name'],
            'code'              => strtoupper($validated['code']),
            'days_per_year'     => (int) $validated['days_per_year'],
            'accrual_frequency' => $validated['accrual_frequency'],
            'days_per_month'    => (float) $request->input('days_per_month', 0.00),
            'carry_forward'     => $request->boolean('carry_forward'),
            'is_active'         => $request->boolean('is_active', true),
        ]);

        $this->syncBalancesToEmployees($leaveType);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success'    => true,
                'message'    => "Leave rule '{$leaveType->name}' ({$leaveType->code}) updated successfully.",
                'leave_type' => $this->formatLeaveType($leaveType),
            ]);
        }

        return back()->with("success", "Leave rule updated for {$leaveType->name} ({$leaveType->code}).");
    }

    /**
     * Toggle active/disabled status of a leave type.
     */
    public function toggleStatus(Request $request, LeaveType $leaveType)
    {
        $leaveType->update(['is_active' => !$leaveType->is_active]);

        $statusStr = $leaveType->is_active ? 'activated' : 'disabled';

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success'    => true,
                'message'    => "Leave type '{$leaveType->name}' is now {$statusStr}.",
                'leave_type' => $this->formatLeaveType($leaveType),
            ]);
        }

        return back()->with('success', "Leave type {$statusStr}.");
    }

    /**
     * Delete leave type (if no leave applications reference it).
     */
    public function destroy(Request $request, LeaveType $leaveType)
    {
        $leaveCount = Leave::where('leave_type_id', $leaveType->id)->count();

        if ($leaveCount > 0) {
            // Cannot hard delete because of existing leave applications; disable instead
            $leaveType->update(['is_active' => false]);

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success'    => true,
                    'message'    => "Leave type '{$leaveType->name}' has {$leaveCount} existing leave records, so it was deactivated instead of deleted.",
                    'leave_type' => $this->formatLeaveType($leaveType),
                    'deactivated' => true,
                ]);
            }

            return back()->with('success', "Leave type has {$leaveCount} applications and was deactivated instead of deleted.");
        }

        // Delete associated balances and leave type
        LeaveBalance::where('leave_type_id', $leaveType->id)->delete();
        $name = $leaveType->name;
        $leaveType->delete();

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Leave rule '{$name}' deleted successfully.",
                'deleted' => true,
                'id'      => $leaveType->id,
            ]);
        }

        return back()->with('success', "Leave rule '{$name}' deleted successfully.");
    }

    /**
     * Helper to format leave type model for response.
     */
    private function formatLeaveType(LeaveType $t): array
    {
        $quotaDisplay = $t->accrual_frequency === 'monthly'
            ? ($t->days_per_month . ' days / month')
            : ($t->days_per_year == 0 ? 'Unlimited' : ($t->days_per_year . ' days / year'));

        return [
            'id'                => $t->id,
            'name'              => $t->name,
            'code'              => $t->code,
            'days_per_year'     => (int) $t->days_per_year,
            'accrual_frequency' => $t->accrual_frequency ?? 'annual',
            'days_per_month'    => (float) $t->days_per_month,
            'carry_forward'     => (bool) $t->carry_forward,
            'is_active'         => (bool) $t->is_active,
            'quota_display'     => $quotaDisplay,
        ];
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
