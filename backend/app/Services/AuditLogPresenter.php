<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\LeaveType;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class AuditLogPresenter
{
    protected static array $userCache = [];
    protected static array $leaveTypeCache = [];
    protected static bool $preloaded = false;

    /**
     * Eagerly preload referenced entities across a collection of logs to eliminate N+1 queries.
     */
    public static function preload(iterable $logs): void
    {
        $userIds = [];
        $leaveTypeIds = [];

        foreach ($logs as $log) {
            $allValues = array_merge((array)($log->old_values ?? []), (array)($log->new_values ?? []));
            if (!empty($allValues['user_id'])) {
                $userIds[] = (int)$allValues['user_id'];
            }
            if (!empty($allValues['leave_type_id'])) {
                $leaveTypeIds[] = (int)$allValues['leave_type_id'];
            }
            if (!empty($log->causer_id)) {
                $userIds[] = (int)$log->causer_id;
            }
        }

        $userIds = array_unique(array_filter($userIds));
        $missingUserIds = array_diff($userIds, array_keys(static::$userCache));
        if (!empty($missingUserIds)) {
            $users = User::whereIn('id', $missingUserIds)->get(['id', 'name', 'employee_id']);
            foreach ($users as $u) {
                static::$userCache[$u->id] = $u->name;
            }
        }

        $leaveTypeIds = array_unique(array_filter($leaveTypeIds));
        $missingLeaveTypeIds = array_diff($leaveTypeIds, array_keys(static::$leaveTypeCache));
        if (!empty($missingLeaveTypeIds)) {
            $types = LeaveType::whereIn('id', $missingLeaveTypeIds)->get(['id', 'name']);
            foreach ($types as $t) {
                static::$leaveTypeCache[$t->id] = $t->name;
            }
        }

        static::$preloaded = true;
    }

    /**
     * Resolve a user's name by ID using in-memory cache.
     */
    public static function getUserName(?int $userId): ?string
    {
        if (!$userId) return null;
        if (!isset(static::$userCache[$userId])) {
            $u = User::find($userId, ['id', 'name']);
            static::$userCache[$userId] = $u ? $u->name : "User #{$userId}";
        }
        return static::$userCache[$userId];
    }

    /**
     * Resolve a leave type's name by ID using in-memory cache.
     */
    public static function getLeaveTypeName(?int $typeId): ?string
    {
        if (!$typeId) return null;
        if (!isset(static::$leaveTypeCache[$typeId])) {
            $t = LeaveType::find($typeId, ['id', 'name']);
            static::$leaveTypeCache[$typeId] = $t ? $t->name : "Leave Type #{$typeId}";
        }
        return static::$leaveTypeCache[$typeId];
    }

    /**
     * Returns a human-friendly action badge array: label, css class, icon name.
     */
    public static function formatActionBadge(AuditLog $log): array
    {
        $event = strtolower((string)$log->event);
        $entity = class_basename((string)($log->auditable_type ?? ''));

        // Leave Balance
        if (str_contains($event, 'leavebalance') || $entity === 'LeaveBalance') {
            if (str_ends_with($event, '.created')) return ['label' => 'Leave Quota Added', 'class' => 'bg-emerald-50 text-emerald-700 border-emerald-200', 'icon' => 'calendar-plus'];
            if (str_ends_with($event, '.updated')) return ['label' => 'Leave Quota Adjusted', 'class' => 'bg-sky-50 text-sky-700 border-sky-200', 'icon' => 'calendar-check'];
            if (str_ends_with($event, '.deleted')) return ['label' => 'Leave Quota Removed', 'class' => 'bg-rose-50 text-rose-700 border-rose-200', 'icon' => 'calendar-x'];
        }

        // Leave Requests
        if (str_contains($event, 'leave') || $entity === 'Leave') {
            if (str_contains($event, 'approved')) return ['label' => 'Leave Approved', 'class' => 'bg-emerald-50 text-emerald-700 border-emerald-200', 'icon' => 'check-circle'];
            if (str_contains($event, 'rejected')) return ['label' => 'Leave Rejected', 'class' => 'bg-rose-50 text-rose-700 border-rose-200', 'icon' => 'x-circle'];
            if (str_ends_with($event, '.created')) return ['label' => 'Leave Applied', 'class' => 'bg-amber-50 text-amber-700 border-amber-200', 'icon' => 'clock'];
        }

        // Expense Claims
        if (str_contains($event, 'expense') || $entity === 'ExpenseBill' || $entity === 'Expense') {
            if (str_contains($event, 'approved') || str_contains($event, 'verified')) {
                return ['label' => 'Expense Approved', 'class' => 'bg-emerald-50 text-emerald-700 border-emerald-200', 'icon' => 'receipt'];
            }
            if (str_contains($event, 'rejected')) {
                return ['label' => 'Expense Rejected', 'class' => 'bg-rose-50 text-rose-700 border-rose-200', 'icon' => 'x-circle'];
            }
            if (str_ends_with($event, '.created')) {
                return ['label' => 'Expense Claimed', 'class' => 'bg-amber-50 text-amber-700 border-amber-200', 'icon' => 'file-text'];
            }
            if (str_ends_with($event, '.updated')) {
                return ['label' => 'Expense Edited', 'class' => 'bg-sky-50 text-sky-700 border-sky-200', 'icon' => 'pencil'];
            }
        }

        // Day Starts & Tour Plans
        if (str_contains($event, 'daystart') || $entity === 'DayStart') {
            if (str_contains($event, 'approved') || str_contains($event, 'verified')) {
                return ['label' => 'Tour Day Approved', 'class' => 'bg-emerald-50 text-emerald-700 border-emerald-200', 'icon' => 'calendar-check'];
            }
            if (str_contains($event, 'rejected')) {
                return ['label' => 'Tour Day Rejected', 'class' => 'bg-rose-50 text-rose-700 border-rose-200', 'icon' => 'calendar-x'];
            }
            if (str_ends_with($event, '.created')) {
                return ['label' => 'Day Started', 'class' => 'bg-indigo-50 text-indigo-700 border-indigo-200', 'icon' => 'play-circle'];
            }
            if (str_ends_with($event, '.updated')) {
                return ['label' => 'Tour Plan Updated', 'class' => 'bg-sky-50 text-sky-700 border-sky-200', 'icon' => 'calendar'];
            }
        }

        // Orders
        if (str_contains($event, 'order') || $entity === 'Order') {
            if (str_contains($event, 'approved')) return ['label' => 'Order Approved', 'class' => 'bg-blue-50 text-blue-700 border-blue-200', 'icon' => 'shopping-cart'];
            if (str_contains($event, 'dispatched')) return ['label' => 'Order Dispatched', 'class' => 'bg-indigo-50 text-indigo-700 border-indigo-200', 'icon' => 'truck'];
            if (str_contains($event, 'delivered')) return ['label' => 'Order Delivered', 'class' => 'bg-emerald-50 text-emerald-700 border-emerald-200', 'icon' => 'package-check'];
            if (str_contains($event, 'cancelled')) return ['label' => 'Order Cancelled', 'class' => 'bg-rose-50 text-rose-700 border-rose-200', 'icon' => 'package-x'];
            if (str_ends_with($event, '.created')) return ['label' => 'Order Booked', 'class' => 'bg-teal-50 text-teal-700 border-teal-200', 'icon' => 'shopping-bag'];
        }

        // Users & Employees
        if (str_contains($event, 'user') || $entity === 'User') {
            if (str_ends_with($event, '.created')) return ['label' => 'Employee Added', 'class' => 'bg-emerald-50 text-emerald-700 border-emerald-200', 'icon' => 'user-plus'];
            if (str_ends_with($event, '.updated')) return ['label' => 'Profile Updated', 'class' => 'bg-sky-50 text-sky-700 border-sky-200', 'icon' => 'user-check'];
            if (str_ends_with($event, '.deleted')) return ['label' => 'Employee Removed', 'class' => 'bg-rose-50 text-rose-700 border-rose-200', 'icon' => 'user-x'];
        }

        // Generic Event Types
        if (str_ends_with($event, '.created')) {
            return ['label' => 'Record Created', 'class' => 'bg-emerald-50 text-emerald-700 border-emerald-200', 'icon' => 'plus-circle'];
        }
        if (str_ends_with($event, '.updated') || str_ends_with($event, '.adjusted')) {
            return ['label' => 'Record Updated', 'class' => 'bg-sky-50 text-sky-700 border-sky-200', 'icon' => 'edit'];
        }
        if (str_ends_with($event, '.deleted')) {
            return ['label' => 'Record Deleted', 'class' => 'bg-rose-50 text-rose-700 border-rose-200', 'icon' => 'trash-2'];
        }

        return [
            'label' => Str::headline(str_replace('.', ' ', $event)),
            'class' => 'bg-slate-100 text-slate-700 border-slate-200',
            'icon'  => 'activity'
        ];
    }

    /**
     * Formats a clear, human-readable sentence explaining who did what.
     */
    public static function formatStory(AuditLog $log): string
    {
        $causerName = $log->causer?->name ?: 'System';
        $event = strtolower((string)$log->event);
        $entity = class_basename((string)($log->auditable_type ?? ''));
        $newVals = (array)($log->new_values ?? []);
        $oldVals = (array)($log->old_values ?? []);
        $merged = array_merge($oldVals, $newVals);

        // 1. Leave Balance Allocation / Adjustment
        if (str_contains($event, 'leavebalance') || $entity === 'LeaveBalance') {
            $employeeName = static::getUserName($merged['user_id'] ?? null) ?: 'Employee';
            $leaveTypeName = static::getLeaveTypeName($merged['leave_type_id'] ?? null) ?: 'Leave';
            $year = $merged['year'] ?? (date('Y'));
            $allocated = $newVals['allocated'] ?? $merged['allocated'] ?? null;

            if (str_ends_with($event, '.created')) {
                return "{$causerName} allocated {$allocated} days of {$leaveTypeName} to {$employeeName} for {$year}.";
            }
            if (str_ends_with($event, '.updated')) {
                if (isset($oldVals['allocated']) && isset($newVals['allocated'])) {
                    return "{$causerName} changed {$leaveTypeName} allocation for {$employeeName} from {$oldVals['allocated']} to {$newVals['allocated']} days ({$year}).";
                }
                return "{$causerName} adjusted {$leaveTypeName} quota for {$employeeName} ({$year}).";
            }
            if (str_ends_with($event, '.deleted')) {
                return "{$causerName} removed {$leaveTypeName} allocation for {$employeeName} ({$year}).";
            }
        }

        // 2. Expense Claims & Approvals
        if (str_contains($event, 'expense') || $entity === 'ExpenseBill' || $entity === 'Expense') {
            $employeeName = static::getUserName($merged['user_id'] ?? null) ?: 'Fieldworker';
            $category = Str::headline($merged['category'] ?? 'Expense');
            $amount = isset($merged['amount']) ? ('₹' . number_format((float)$merged['amount'], 2)) : '';

            if (str_contains($event, 'approved') || str_contains($event, 'verified') || ($newVals['status'] ?? '') === 'approved') {
                return "{$causerName} approved {$amount} {$category} claim for {$employeeName}.";
            }
            if (str_contains($event, 'rejected') || ($newVals['status'] ?? '') === 'rejected') {
                $reason = $log->reason ?: ($newVals['rejection_reason'] ?? '');
                $reasonSuffix = $reason && $reason !== 'Updated ExpenseBill' ? " (Reason: {$reason})" : '';
                return "{$causerName} rejected {$amount} {$category} claim for {$employeeName}{$reasonSuffix}.";
            }
            if (str_ends_with($event, '.created')) {
                return "{$employeeName} submitted a {$amount} {$category} expense claim.";
            }
            if (str_ends_with($event, '.updated')) {
                return "{$causerName} updated {$category} expense claim #{$log->auditable_id} for {$employeeName}.";
            }
        }

        // 3. Day Start / Tour Plan
        if (str_contains($event, 'daystart') || $entity === 'DayStart') {
            $employeeName = static::getUserName($merged['user_id'] ?? null) ?: 'Fieldworker';
            $date = isset($merged['date']) ? date('d M Y', strtotime($merged['date'])) : '';

            if (str_contains($event, 'approved') || str_contains($event, 'verified') || ($newVals['status'] ?? '') === 'approved') {
                return "{$causerName} verified Tour Day for {$employeeName}" . ($date ? " ({$date})" : '') . ".";
            }
            if (str_contains($event, 'rejected') || ($newVals['status'] ?? '') === 'rejected') {
                return "{$causerName} rejected Tour Day for {$employeeName}" . ($date ? " ({$date})" : '') . ".";
            }
            if (str_ends_with($event, '.created')) {
                return "{$employeeName} started their fieldwork day" . ($date ? " on {$date}" : '') . ".";
            }
            if (str_ends_with($event, '.updated')) {
                return "{$causerName} updated Tour Day record for {$employeeName}" . ($date ? " ({$date})" : '') . ".";
            }
        }

        // 4. Leave Applications
        if (str_contains($event, 'leave') || $entity === 'Leave') {
            $employeeName = static::getUserName($merged['user_id'] ?? null) ?: 'Employee';
            $leaveType = static::getLeaveTypeName($merged['leave_type_id'] ?? null) ?: 'Leave';
            $days = $merged['days_count'] ?? $merged['days'] ?? '';
            $daysText = $days ? "{$days} days " : '';

            if (str_contains($event, 'approved') || ($newVals['status'] ?? '') === 'approved') {
                return "{$causerName} approved {$daysText}{$leaveType} request for {$employeeName}.";
            }
            if (str_contains($event, 'rejected') || ($newVals['status'] ?? '') === 'rejected') {
                return "{$causerName} rejected {$leaveType} request for {$employeeName}.";
            }
            if (str_ends_with($event, '.created')) {
                return "{$employeeName} applied for {$daysText}{$leaveType}.";
            }
        }

        // 5. Orders
        if (str_contains($event, 'order') || $entity === 'Order') {
            $orderNo = $merged['order_no'] ?? "#{$log->auditable_id}";
            $amount = isset($merged['grand_total']) || isset($merged['total_amount']) 
                ? ('₹' . number_format((float)($merged['grand_total'] ?? $merged['total_amount']), 2)) 
                : '';
            $status = $newVals['status'] ?? $merged['status'] ?? '';

            if ($status) {
                return "{$causerName} marked Order {$orderNo} as " . Str::headline($status) . ($amount ? " ({$amount})" : '') . ".";
            }
            if (str_ends_with($event, '.created')) {
                return "{$causerName} booked new Order {$orderNo}" . ($amount ? " ({$amount})" : '') . ".";
            }
        }

        // 6. User / Employee
        if (str_contains($event, 'user') || $entity === 'User') {
            $userName = $newVals['name'] ?? $oldVals['name'] ?? static::getUserName((int)$log->auditable_id) ?: "User #{$log->auditable_id}";
            if (str_ends_with($event, '.created')) return "{$causerName} added new employee account for {$userName}.";
            if (str_ends_with($event, '.updated')) return "{$causerName} updated profile / role settings for {$userName}.";
            if (str_ends_with($event, '.deleted')) return "{$causerName} deleted employee account {$userName}.";
        }

        // 7. General fallback if reason is provided and meaningful
        if (!empty($log->reason) && !Str::startsWith($log->reason, ['Created ', 'Updated ', 'Deleted '])) {
            return "{$causerName}: {$log->reason}";
        }

        // 8. Graceful default sentence
        $actionVerb = str_ends_with($event, '.created') ? 'created' : (str_ends_with($event, '.deleted') ? 'deleted' : 'updated');
        return "{$causerName} {$actionVerb} {$entity} #{$log->auditable_id}.";
    }

    /**
     * Translates database columns and values into human-readable labels and representations.
     */
    public static function formatDiff(AuditLog $log): array
    {
        $oldVals = (array)($log->old_values ?? []);
        $newVals = (array)($log->new_values ?? []);
        $allKeys = array_unique(array_merge(array_keys($oldVals), array_keys($newVals)));

        // Fields to omit from raw display because they are internal noise
        $ignoredKeys = ['id', 'created_at', 'updated_at', 'remember_token', 'password'];

        $formatted = [];
        foreach ($allKeys as $key) {
            if (in_array($key, $ignoredKeys, true)) continue;

            $oldVal = $oldVals[$key] ?? null;
            $newVal = $newVals[$key] ?? null;

            $label = static::formatFieldLabel($key);
            $formattedOld = static::formatFieldValue($key, $oldVal);
            $formattedNew = static::formatFieldValue($key, $newVal);

            $formatted[] = [
                'raw_key'  => $key,
                'label'    => $label,
                'old'      => $formattedOld,
                'new'      => $formattedNew,
                'has_old'  => $oldVal !== null,
                'has_new'  => $newVal !== null,
            ];
        }

        return $formatted;
    }

    /**
     * Converts a raw database column to a friendly readable label.
     */
    protected static function formatFieldLabel(string $key): string
    {
        $map = [
            'user_id'             => 'Employee',
            'causer_id'           => 'Performed By',
            'leave_type_id'       => 'Leave Type',
            'role_id'             => 'User Role',
            'allocated'           => 'Allocated Quota (Days)',
            'used'                => 'Used Quota (Days)',
            'carried_forward'     => 'Carried Forward (Days)',
            'year'                => 'Year',
            'is_active'           => 'Active Status',
            'amount'              => 'Amount (₹)',
            'grand_total'         => 'Total Amount (₹)',
            'ta_amount'           => 'Travel Allowance (TA)',
            'da_amount'           => 'Daily Allowance (DA)',
            'start_km'            => 'Starting Odometer (KM)',
            'end_km'              => 'Ending Odometer (KM)',
            'total_km'            => 'Total Distance (KM)',
            'status'              => 'Status',
            'category'            => 'Expense Category',
            'rejection_reason'    => 'Rejection Reason',
            'notes'               => 'Notes / Remarks',
            'reason'              => 'Reason',
            'date'                => 'Date',
            'from_date'           => 'From Date',
            'to_date'             => 'To Date',
            'mobile'              => 'Mobile Number',
            'email'               => 'Email Address',
            'name'                => 'Name',
        ];

        return $map[$key] ?? Str::headline(str_replace('_', ' ', $key));
    }

    /**
     * Converts a raw database value to a friendly readable value.
     */
    protected static function formatFieldValue(string $key, mixed $val): ?string
    {
        if ($val === null) return null;

        if (is_bool($val)) {
            return $val ? 'Active / Yes' : 'Inactive / No';
        }

        if ($key === 'is_active') {
            return $val ? 'Active' : 'Inactive';
        }

        if ($key === 'user_id' || $key === 'causer_id') {
            $name = static::getUserName((int)$val);
            return $name ? "{$name} (ID: {$val})" : (string)$val;
        }

        if ($key === 'leave_type_id') {
            $name = static::getLeaveTypeName((int)$val);
            return $name ? "{$name} (ID: {$val})" : (string)$val;
        }

        if (in_array($key, ['amount', 'grand_total', 'total_amount', 'ta_amount', 'da_amount'], true) && is_numeric($val)) {
            return '₹' . number_format((float)$val, 2);
        }

        if (in_array($key, ['start_km', 'end_km', 'total_km'], true) && is_numeric($val)) {
            return number_format((float)$val, 1) . ' km';
        }

        if (is_array($val)) {
            return json_encode($val, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        return (string)$val;
    }

    /**
     * Helper to return target entity URL if available.
     */
    public static function getEntityUrl(AuditLog $log): ?string
    {
        $entity = class_basename((string)($log->auditable_type ?? ''));
        $id = $log->auditable_id;

        if (!$id) return null;

        try {
            return match ($entity) {
                'DayStart'              => \Illuminate\Support\Facades\Route::has('admin.day-starts.show') ? route('admin.day-starts.show', $id) : null,
                'User'                  => \Illuminate\Support\Facades\Route::has('admin.employees.show') ? route('admin.employees.show', $id) : (\Illuminate\Support\Facades\Route::has('admin.employees.index') ? route('admin.employees.index') : null),
                'Leave'                 => \Illuminate\Support\Facades\Route::has('admin.leave.index') ? route('admin.leave.index') : null,
                'LeaveBalance'          => \Illuminate\Support\Facades\Route::has('admin.leave-balances.index') ? route('admin.leave-balances.index') : null,
                'ExpenseBill', 'Expense'=> \Illuminate\Support\Facades\Route::has('admin.expenses.index') ? route('admin.expenses.index') : null,
                'Order'                 => \Illuminate\Support\Facades\Route::has('admin.orders.index') ? route('admin.orders.index') : null,
                'Customer', 'Dealer', 'Party' => \Illuminate\Support\Facades\Route::has('admin.parties.index') ? route('admin.parties.index') : null,
                default                 => null,
            };
        } catch (\Throwable $e) {
            return null;
        }
    }
}
