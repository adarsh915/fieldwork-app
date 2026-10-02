<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\DataScope;
use App\Services\ReportingService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function __construct(private readonly ReportingService $reportingService) {}

    /**
     * Display Employee 360 Performance KPI Matrix.
     */
    public function index(Request $request)
    {
        $scopedUserIds = DataScope::visibleUserIds('reports');
        $dateRange = $this->reportingService->resolveDateRange(
            $request->input('preset', 'this_month'),
            $request->input('from'),
            $request->input('to')
        );

        $selectedUserId = $request->filled('user_id') ? (int) $request->user_id : null;

        // Verify if selected user is within scope
        if ($selectedUserId && $scopedUserIds !== null && !in_array($selectedUserId, $scopedUserIds)) {
            $selectedUserId = -1; // No results
        }

        $reportData = $this->reportingService->getEmployeePerformanceMatrix(
            $dateRange,
            $selectedUserId,
            $scopedUserIds
        );

        // Employee filter dropdown list
        $employeeQuery = User::where('is_active', true);
        if ($scopedUserIds !== null) {
            $employeeQuery->whereIn('id', $scopedUserIds);
        }
        $employeesList = $employeeQuery->orderBy('name')->get(['id', 'name', 'employee_id']);

        return view('admin.reports.index', [
            'matrix'        => $reportData['users_matrix'],
            'totals'        => $reportData['totals'],
            'dateRange'     => $dateRange,
            'employeesList' => $employeesList,
            'selectedUser'  => $selectedUserId,
        ]);
    }

    /**
     * Display Deep-Dive Scorecard for a single employee.
     */
    public function employeeScorecard(Request $request, int $id)
    {
        $scopedUserIds = DataScope::visibleUserIds('reports');
        if ($scopedUserIds !== null && !in_array($id, $scopedUserIds)) {
            abort(403, 'Unauthorized access to this employee report.');
        }

        $dateRange = $this->reportingService->resolveDateRange(
            $request->input('preset', 'this_month'),
            $request->input('from'),
            $request->input('to')
        );

        $scorecard = $this->reportingService->getEmployeeScorecard($id, $dateRange);

        return view('admin.reports.employee-scorecard', [
            'scorecard' => $scorecard,
            'dateRange' => $dateRange,
        ]);
    }

    /**
     * Display Daily Field Operations Ledger.
     */
    public function dailyLedger(Request $request)
    {
        $scopedUserIds = DataScope::visibleUserIds('reports');
        $dateRange = $this->reportingService->resolveDateRange(
            $request->input('preset', 'this_month'),
            $request->input('from'),
            $request->input('to')
        );

        $selectedUserId = $request->filled('user_id') ? (int) $request->user_id : null;
        if ($selectedUserId && $scopedUserIds !== null && !in_array($selectedUserId, $scopedUserIds)) {
            $selectedUserId = -1;
        }

        $ledgerRecords = $this->reportingService->getDailyLedger($dateRange, $selectedUserId, $scopedUserIds);

        $employeeQuery = User::where('is_active', true);
        if ($scopedUserIds !== null) {
            $employeeQuery->whereIn('id', $scopedUserIds);
        }
        $employeesList = $employeeQuery->orderBy('name')->get(['id', 'name', 'employee_id']);

        return view('admin.reports.daily-ledger', [
            'records'       => $ledgerRecords,
            'dateRange'     => $dateRange,
            'employeesList' => $employeesList,
            'selectedUser'  => $selectedUserId,
        ]);
    }

    /**
     * Export Employee Matrix to CSV.
     */
    public function exportCsv(Request $request): StreamedResponse
    {
        $scopedUserIds = DataScope::visibleUserIds('reports');
        $dateRange = $this->reportingService->resolveDateRange(
            $request->input('preset', 'this_month'),
            $request->input('from'),
            $request->input('to')
        );

        $selectedUserId = $request->filled('user_id') ? (int) $request->user_id : null;
        if ($selectedUserId && $scopedUserIds !== null && !in_array($selectedUserId, $scopedUserIds)) {
            $selectedUserId = -1;
        }

        $reportData = $this->reportingService->getEmployeePerformanceMatrix(
            $dateRange,
            $selectedUserId,
            $scopedUserIds
        );

        $filename = 'employee_report_' . $dateRange['from'] . '_to_' . $dateRange['to'] . '.csv';

        return response()->streamDownload(function () use ($reportData) {
            $handle = fopen('php://output', 'w');
            
            // Header Row
            fputcsv($handle, [
                'Employee Code',
                'Employee Name',
                'Role',
                'Reporting Manager',
                'Days Started',
                'Total KM',
                'Physical Visits',
                'Avg Visits/Day',
                'Visit Orders',
                'Phone Shift Orders',
                'Phone Night Orders',
                'Total Orders',
                'Total Sales Amount (INR)',
                'Total Collections (INR)',
                'Leaves Taken (Days)',
                'Claimed Expenses (INR)',
                'Approved Expenses (INR)',
            ]);

            foreach ($reportData['users_matrix'] as $row) {
                fputcsv($handle, [
                    $row['employee_code'],
                    $row['name'],
                    $row['role'],
                    $row['manager'],
                    $row['days_started'],
                    $row['total_km'],
                    $row['total_visits'],
                    $row['avg_visits_per_day'],
                    $row['visit_orders_count'],
                    $row['phone_shift_orders'],
                    $row['phone_night_orders'],
                    $row['total_orders'],
                    $row['total_order_amount'],
                    $row['total_collections'],
                    $row['leave_days'],
                    $row['claimed_expenses'],
                    $row['approved_expenses'],
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    /**
     * Display the Fieldworker Parties Report.
     */
    public function fieldworkerParties(Request $request)
    {
        $scopedUserIds = DataScope::visibleUserIds('parties');
        $employeeId = $request->input('employee_id');

        $query = User::has('parties')->with('parties')->orderBy('name');

        if ($scopedUserIds !== null) {
            $query->whereIn('id', $scopedUserIds);
        }

        if ($employeeId) {
            $query->where('id', $employeeId);
        }

        $fieldworkers = $query->get();

        $employeeListQuery = User::orderBy('name');
        if ($scopedUserIds !== null) {
            $employeeListQuery->whereIn('id', $scopedUserIds);
        }
        $employeesList = $employeeListQuery->get(['id', 'name', 'employee_id']);

        return view('admin.reports.fieldworker-parties', compact('fieldworkers', 'employeesList', 'employeeId'));
    }
}
