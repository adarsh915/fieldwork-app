<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DayStart;
use App\Models\User;
use App\Services\DataScope;
use App\Services\ReportingService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Yajra\DataTables\Facades\DataTables;

class ReportController extends Controller
{
    public function __construct(private readonly ReportingService $reportingService) {}

    /**
     * Display Employee 360 Performance KPI Matrix (initial page load only).
     * Filter changes use matrixJson() via AJAX — no full page reload.
     */
    public function index(Request $request)
    {
        $scopedUserIds  = DataScope::visibleUserIds('reports');
        $dateRange      = $this->reportingService->resolveDateRange(
            $request->input('preset', 'this_month'),
            $request->input('from'),
            $request->input('to')
        );

        $selectedUserId = $request->filled('user_id') ? (int) $request->user_id : null;
        if ($selectedUserId && $scopedUserIds !== null && !in_array($selectedUserId, $scopedUserIds)) {
            $selectedUserId = -1;
        }

        $reportData    = $this->reportingService->getEmployeePerformanceMatrix($dateRange, $selectedUserId, $scopedUserIds);
        $employeesList = $this->employeesDropdown($scopedUserIds);

        return view('admin.reports.index', [
            'matrix'        => $reportData['users_matrix'],
            'totals'        => $reportData['totals'],
            'dateRange'     => $dateRange,
            'employeesList' => $employeesList,
            'selectedUser'  => $selectedUserId,
        ]);
    }

    /**
     * AJAX: GET /admin/reports/matrix-json
     * Returns matrix + totals as JSON — called by the filter bar without page reload.
     */
    public function matrixJson(Request $request): JsonResponse
    {
        $scopedUserIds  = DataScope::visibleUserIds('reports');
        $dateRange      = $this->reportingService->resolveDateRange(
            $request->input('preset', 'this_month'),
            $request->input('from'),
            $request->input('to')
        );

        $selectedUserId = $request->filled('user_id') ? (int) $request->user_id : null;
        if ($selectedUserId && $scopedUserIds !== null && !in_array($selectedUserId, $scopedUserIds)) {
            $selectedUserId = -1;
        }

        $reportData = $this->reportingService->getEmployeePerformanceMatrix($dateRange, $selectedUserId, $scopedUserIds);

        return response()->json([
            'matrix'    => $reportData['users_matrix']->values(),
            'totals'    => $reportData['totals'],
            'dateRange' => $dateRange,
        ]);
    }

    private function employeesDropdown(?array $scopedUserIds)
    {
        $q = User::query()->where('is_active', true);
        if ($scopedUserIds !== null) {
            $q->whereIn('id', $scopedUserIds);
        } else {
            $q->where(function ($query) {
                $query->whereHas('roles', function ($rq) {
                    $rq->whereIn('name', ['Field Worker', 'field_worker', 'Employee', 'employee', 'Manager', 'manager', 'Supervisor', 'supervisor', 'Sales', 'sales']);
                })->orWhereNotNull('employee_id');
            });
        }
        return $q->with('roles')->orderBy('name')->get();
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
        $employeesList = $this->employeesDropdown($scopedUserIds);

        return view('admin.reports.daily-ledger', [
            'records'       => $ledgerRecords,
            'dateRange'     => $dateRange,
            'employeesList' => $employeesList,
            'selectedUser'  => $selectedUserId,
        ]);
    }

    /**
     * AJAX endpoint for Daily Field Operations Ledger DataTable.
     */
    public function dailyLedgerData(Request $request): JsonResponse
    {
        $scopedUserIds = DataScope::visibleUserIds('reports');
        $dateRange = $this->reportingService->resolveDateRange(
            $request->input('preset', 'this_month'),
            $request->input('date_from', $request->input('from')),
            $request->input('date_to', $request->input('to'))
        );

        $startDate = $dateRange['start_date']->format('Y-m-d');
        $endDate   = $dateRange['end_date']->format('Y-m-d');

        $query = DayStart::query()
            ->with([
                'user:id,name,employee_id',
                'user.roles:id,name',
                'expenses:id,day_start_id,amount',
                'visits:id,day_start_id',
            ])
            ->whereBetween('date', [$startDate, $endDate])
            ->select('day_starts.*')
            ->orderBy('date', 'desc')
            ->orderBy('id', 'desc');

        if ($scopedUserIds !== null) {
            $query->whereIn('user_id', $scopedUserIds);
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', (int) $request->user_id);
        }

        if ($request->filled('status')) {
            $status = $request->status;
            if ($status === 'auto_closed') {
                $query->where(function ($q) {
                    $q->where('status', 'auto_closed')
                      ->orWhere(function ($sq) {
                          $sq->whereNull('closing_km')->whereDate('date', '<', Carbon::today());
                      });
                });
            } elseif ($status === 'completed') {
                $query->where(function ($q) {
                    $q->where('status', 'completed')
                      ->orWhereNotNull('closing_km');
                });
            } elseif ($status === 'in_progress') {
                $query->whereNull('closing_km')
                      ->where('status', '!=', 'auto_closed')
                      ->whereDate('date', '>=', Carbon::today());
            }
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
                })->orWhere('opening_km', 'like', "%{$search}%")
                  ->orWhere('closing_km', 'like', "%{$search}%")
                  ->orWhere('date', 'like', "%{$search}%");
            });
        }

        return DataTables::of($query)
            ->addColumn('date', function ($ds) {
                return [
                    'formatted' => $ds->date ? $ds->date->format('d M Y') : '—',
                    'day' => $ds->date ? $ds->date->format('D') : '',
                    'raw' => $ds->date ? $ds->date->format('Y-m-d') : '',
                ];
            })
            ->addColumn('employee', function ($ds) {
                return [
                    'name' => $ds->user?->name ?? 'Unknown',
                    'employee_id' => $ds->user?->employee_id && $ds->user->employee_id !== '—' ? $ds->user->employee_id : null,
                    'role' => $ds->user?->role_name ?? ($ds->user?->roles->first()?->name ?? 'Field Worker'),
                ];
            })
            ->addColumn('opening_km', function ($ds) {
                return $ds->opening_km !== null ? number_format((float) $ds->opening_km, 1) : '—';
            })
            ->addColumn('closing_km', function ($ds) {
                return $ds->closing_km !== null ? number_format((float) $ds->closing_km, 1) : '—';
            })
            ->addColumn('distance', function ($ds) {
                $dist = ($ds->closing_km !== null && $ds->opening_km !== null)
                    ? max(0, (float)$ds->closing_km - (float)$ds->opening_km)
                    : null;
                return [
                    'formatted' => $dist !== null ? number_format($dist, 1) . ' KM' : '—',
                    'raw' => $dist ?? 0,
                ];
            })
            ->addColumn('visits_count', function ($ds) {
                return $ds->visits->count();
            })
            ->addColumn('expenses_sum', function ($ds) {
                $sum = (float) $ds->expenses->sum('amount');
                return [
                    'formatted' => '₹' . number_format($sum, 2),
                    'raw' => $sum,
                ];
            })
            ->addColumn('status', function ($ds) {
                if ($ds->status === 'auto_closed' || (!$ds->closing_km && $ds->date < Carbon::today())) {
                    return [
                        'code' => 'auto_closed',
                        'label' => 'Auto Closed',
                        'badge' => 'badge-warning',
                    ];
                } elseif ($ds->closing_km || $ds->status === 'completed') {
                    return [
                        'code' => 'completed',
                        'label' => 'Completed',
                        'badge' => 'badge-success',
                    ];
                } else {
                    return [
                        'code' => 'in_progress',
                        'label' => 'In Progress',
                        'badge' => 'badge-primary',
                    ];
                }
            })
            ->addColumn('actions', function ($ds) {
                return [
                    'id' => $ds->id,
                    'show_url' => route('admin.day-starts.show', $ds->id),
                    'receipt_url' => route('admin.expenses.reports.daily', $ds->id),
                ];
            })
            ->make(true);
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
        $employeesList = $this->employeesDropdown($scopedUserIds);

        return view('admin.reports.fieldworker-parties', compact('fieldworkers', 'employeesList', 'employeeId'));
    }
}
