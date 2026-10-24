<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    /**
     * Display a filtered list of all admin and system audit logs.
     * Enforced by permission 'audit-logs.view'.
     */
    public function index(Request $request)
    {
        $scopedUserIds = \App\Services\DataScope::visibleUserIds('employees');

        $query = AuditLog::with('causer.roles')
            ->orderByDesc('id');

        if ($scopedUserIds !== null) {
            $query->whereIn('causer_id', $scopedUserIds);
        }

        // Search text filter
        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('event', 'like', "%{$search}%")
                  ->orWhere('auditable_type', 'like', "%{$search}%")
                  ->orWhere('reason', 'like', "%{$search}%")
                  ->orWhere('ip_address', 'like', "%{$search}%")
                  ->orWhereHas('causer', function ($uq) use ($search) {
                      $uq->where('name', 'like', "%{$search}%")
                         ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        // Action / Event filter
        if ($request->filled('event')) {
            $eventFilter = $request->event;
            if ($eventFilter === 'created') {
                $query->where('event', 'like', '%.created');
            } elseif ($eventFilter === 'updated') {
                $query->where('event', 'like', '%.updated');
            } elseif ($eventFilter === 'deleted') {
                $query->where('event', 'like', '%.deleted');
            } elseif ($eventFilter === 'approved') {
                $query->where(function ($q) {
                    $q->where('event', 'like', '%.approved')
                      ->orWhere('event', 'like', '%.temp_approved')
                      ->orWhere('event', 'like', '%.verified');
                });
            } elseif ($eventFilter === 'rejected') {
                $query->where('event', 'like', '%.rejected');
            } elseif ($eventFilter === 'cancelled') {
                $query->where('event', 'like', '%.cancelled');
            } else {
                $query->where('event', $eventFilter);
            }
        }

        // Target Model filter
        if ($request->filled('model')) {
            $modelClass = $request->model;
            $query->where(function ($q) use ($modelClass) {
                $q->where('auditable_type', $modelClass)
                  ->orWhere('auditable_type', 'like', "%\\{$modelClass}");
            });
        }

        // Causer / Admin filter
        if ($request->filled('causer_id')) {
            $query->where('causer_id', $request->causer_id);
        }

        // Date range filters
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // KPI Summary Counters
        $today = Carbon::today();
        $startOfWeek = Carbon::now()->startOfWeek();

        $baseCountQuery = AuditLog::query();
        if ($scopedUserIds !== null) {
            $baseCountQuery->whereIn('causer_id', $scopedUserIds);
        }

        $stats = [
            'total'       => (clone $baseCountQuery)->count(),
            'today'       => (clone $baseCountQuery)->whereDate('created_at', $today)->count(),
            'this_week'   => (clone $baseCountQuery)->where('created_at', '>=', $startOfWeek)->count(),
            'admin_count' => (clone $baseCountQuery)->whereNotNull('causer_id')->distinct('causer_id')->count('causer_id'),
        ];

        // Paginate results
        $logs = $query->paginate(25)->withQueryString();

        // Dropdown filter data
        $causersQuery = User::whereIn('id', function ($sub) {
            $sub->select('causer_id')
                ->from('audit_logs')
                ->whereNotNull('causer_id')
                ->distinct();
        });
        if ($scopedUserIds !== null) {
            $causersQuery->whereIn('id', $scopedUserIds);
        }
        $causers = $causersQuery->orderBy('name')->get(['id', 'name', 'email']);

        $knownModels = [
            'DayStart'          => 'Day Start / Tour Plan',
            'Leave'             => 'Leave Request',
            'Product'           => 'Product',
            'User'              => 'Employee / User',
            'Customer'          => 'Customer / Party',
            'Dealer'            => 'Dealer',
            'ExpenseBill'       => 'Expense',
            'Order'             => 'Order',
            'Payment'           => 'Payment',
            'AttendanceAdjustment' => 'Attendance Adjustment',
            'FuelAllowanceRate' => 'Fuel Rate',
            'Holiday'           => 'Holiday',
            'LeaveType'         => 'Leave Policy',
            'Role'              => 'Role',
            'Permission'        => 'Permission',
        ];

        return view('admin.audit-logs.index', compact('logs', 'stats', 'causers', 'knownModels'));
    }
}
