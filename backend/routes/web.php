<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\AuthController as AdminAuthController;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DayStartController;
use App\Http\Controllers\Admin\EmployeeWebController;
use App\Http\Controllers\Admin\FieldWorkerManagementController;
use App\Http\Controllers\Admin\MasterDataController;
use App\Http\Controllers\Admin\NotificationController;
use App\Http\Controllers\Admin\PartyController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\TransactionController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\ProductImageController;
use App\Http\Controllers\Admin\ProductAttributeController;

Route::get('/', function () {
    return redirect()->route('admin.login');
});

Route::prefix('admin')->name('admin.')->group(function () {

    // ─── Auth (no auth needed) ────────────────────────────────────────────────
    Route::middleware('guest')->group(function () {
        Route::get('/login', [AdminAuthController::class, 'showLogin'])->name('login');
        Route::post('/login', [AdminAuthController::class, 'login'])->name('login.submit');
    });
    Route::post('/logout', [AdminAuthController::class, 'logout'])->middleware('auth')->name('logout');

    // ─── All protected routes ─────────────────────────────────────────────────
    Route::middleware(['auth'])->group(function () {

        // Dashboard — any authenticated user
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
        Route::get('/dashboard/stats', [DashboardController::class, 'statsJson'])->name('dashboard.stats');

        // Notifications — any authenticated user (read-only self-service)
        Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
        Route::get('/notifications/feed', [NotificationController::class, 'unreadFeed'])->name('notifications.feed');
        Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');
        Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead'])->name('notifications.read-all');
        Route::delete('/notifications/{id}', [NotificationController::class, 'destroy'])->name('notifications.destroy');
        Route::post('/notifications/clear-read', [NotificationController::class, 'clearRead'])->name('notifications.clear-read');

        // Push Notifications (Admin / Settings Manager)
        Route::middleware('permission:settings.manage')->group(function () {
            Route::get('/push-notifications', [\App\Http\Controllers\Admin\PushNotificationController::class, 'index'])->name('push-notifications.index');
            Route::post('/push-notifications', [\App\Http\Controllers\Admin\PushNotificationController::class, 'send'])->name('push-notifications.send');
        });

        // ── User Management ──────────────────────────────────────────────────
        Route::get('/users', [UserController::class, 'index'])->middleware('role_or_permission:Super Admin|employees.view|employees.view-team|employees.view-all|employees.view-own')->name('users.index');
        Route::post('/users/{user}/roles', [UserController::class, 'updateRoles'])->middleware('role_or_permission:Super Admin|employees.assign-roles')->name('users.roles.update');

        // ── Role & Permission Management ─────────────────────────────────────
        Route::get('/roles', [RoleController::class, 'index'])->middleware('role_or_permission:Super Admin|roles.view|roles.manage')->name('roles.index');
        Route::get('/roles/create', [RoleController::class, 'create'])->middleware('role_or_permission:Super Admin|roles.create|roles.manage')->name('roles.create');
        Route::post('/roles', [RoleController::class, 'store'])->middleware('role_or_permission:Super Admin|roles.create|roles.manage')->name('roles.store');
        Route::get('/roles/{id}/edit', [RoleController::class, 'edit'])->middleware('role_or_permission:Super Admin|roles.edit|roles.manage')->name('roles.edit');
        Route::put('/roles/{id}', [RoleController::class, 'update'])->middleware('role_or_permission:Super Admin|roles.edit|roles.manage')->name('roles.update');
        Route::delete('/roles/{id}', [RoleController::class, 'destroy'])->middleware('role_or_permission:Super Admin|roles.delete|roles.manage')->name('roles.destroy');

        Route::middleware('role:Super Admin')->group(function () {
            Route::get('/permissions/create', [RoleController::class, 'createPermission'])->name('permissions.create');
            Route::post('/permissions', [RoleController::class, 'storePermission'])->name('permissions.store');
            Route::delete('/permissions/{id}', [RoleController::class, 'destroyPermission'])->name('permissions.destroy');
        });

        // ── Employee Management ───────────────────────────────────────────────
        Route::prefix('employees')->name('employees.')->group(function () {
            Route::get('/', [EmployeeWebController::class, 'index'])->middleware('permission:employees.view|employees.view-team|employees.view-all|employees.view-own|employees.manage')->name('index');
            Route::get('/active-dropdown', [EmployeeWebController::class, 'activeDropdown'])->name('active-dropdown');
            Route::get('/create', [EmployeeWebController::class, 'create'])->middleware('permission:employees.create')->name('create');
            Route::post('/', [EmployeeWebController::class, 'store'])->middleware('permission:employees.create')->name('store');
            Route::get('/{id}/edit', [EmployeeWebController::class, 'edit'])->middleware('permission:employees.edit')->name('edit');
            Route::put('/{id}', [EmployeeWebController::class, 'update'])->middleware('permission:employees.edit')->name('update');
            Route::delete('/{id}', [EmployeeWebController::class, 'destroy'])->middleware('permission:employees.delete')->name('destroy');
            Route::patch('/{id}/toggle-status', [EmployeeWebController::class, 'toggleStatus'])->middleware('permission:employees.edit')->name('toggleStatus');
            Route::get('/{id}/parties', [EmployeeWebController::class, 'showParties'])->middleware('permission:employees.view|employees.view-team|employees.view-all|employees.view-own')->name('parties');
            Route::get('/{id}/profile', [EmployeeWebController::class, 'showProfile'])->middleware('permission:employees.view|employees.view-team|employees.view-all|employees.view-own')->name('profile');
            Route::post('/{employee}/parties/bulk-sync', [EmployeeWebController::class, 'bulkSyncParties'])->middleware('permission:employees.edit')->name('parties.bulkSync');
        });

        // ── Field Worker Management ───────────────────────────────────────────
        Route::prefix('field-workers')->name('field-workers.')->middleware('permission:employees.view|employees.view-team|employees.view-all|employees.view-own|employees.manage')->group(function () {
            Route::get('/', [FieldWorkerManagementController::class, 'index'])->name('index');
            Route::get('/{id}/stats', [FieldWorkerManagementController::class, 'statsJson'])->name('stats');  // AJAX
            Route::get('/{id}/metrics', [FieldWorkerManagementController::class, 'showMetrics'])->name('metrics');
            Route::get('/{id}/visits', [FieldWorkerManagementController::class, 'showVisits'])->name('visits');
            Route::get('/{id}/visits/{visitId}', [FieldWorkerManagementController::class, 'visitDetails'])->name('visit-details');
            Route::get('/{id}/transactions', [FieldWorkerManagementController::class, 'showTransactions'])->name('transactions');
            Route::get('/{id}/parties', [FieldWorkerManagementController::class, 'getAssignedParties'])->name('parties');
            Route::get('/{id}/performance', [FieldWorkerManagementController::class, 'getPerformanceMetrics'])->name('performance');
            Route::get('/{id}/export', [FieldWorkerManagementController::class, 'exportReport'])->name('export');
            Route::get('/{id}/edit', [FieldWorkerManagementController::class, 'edit'])->middleware('permission:employees.edit')->name('edit');
            Route::put('/{id}', [FieldWorkerManagementController::class, 'update'])->middleware('permission:employees.edit')->name('update');
        });

        // ── Customer Management ───────────────────────────────────────────────
        Route::get('/customers', [CustomerController::class, 'index'])->middleware('permission:customers.view|customers.manage|parties.view|parties.view-team|parties.view-all|parties.view-own')->name('customers.index');

        // ── Party Management ──────────────────────────────────────────────────
        Route::prefix('parties')->name('parties.')->group(function () {
            // Read-only — requires customers.view or parties.view-team/view-all/view-own
            Route::middleware('permission:customers.view|customers.manage|parties.view|parties.view-team|parties.view-all|parties.view-own')->group(function () {
                Route::get('/', [PartyController::class, 'index'])->name('index');
                Route::get('/data', [PartyController::class, 'data'])->name('data');
                Route::get('/export', [PartyController::class, 'export'])->name('export');
                Route::get('/export-pdf', [PartyController::class, 'exportPdf'])->name('export-pdf');
                Route::get('/download-template', [PartyController::class, 'downloadTemplate'])->name('download-template');
                Route::get('/{id}/show', [PartyController::class, 'show'])->name('show');
                Route::get('/{id}/tab-data', [PartyController::class, 'tabData'])->name('tab-data');  // AJAX
                Route::get('/{id}/export-pdf', [PartyController::class, 'exportSinglePdf'])->name('export-single-pdf');
                Route::get('/{id}/export-excel', [PartyController::class, 'exportSingleExcel'])->name('export-single-excel');
                Route::get('/{id}/assignment-history', [PartyController::class, 'assignmentHistory'])->name('assignment-history');
                Route::get('/{id}/assign-employees', [PartyController::class, 'assignEmployees'])->name('assign-employees');
            });

            // Create — requires customers.create
            Route::middleware('permission:customers.create')->group(function () {
                Route::get('/create', [PartyController::class, 'create'])->name('create');
                Route::post('/', [PartyController::class, 'store'])->name('store');
                Route::get('/bulk-import', [PartyController::class, 'bulkImportForm'])->name('bulk-import');
                Route::post('/bulk-import', [PartyController::class, 'bulkImport'])->name('bulk-import.process');
            });

            // Edit — requires customers.edit
            Route::middleware('permission:customers.edit')->group(function () {
                Route::get('/{id}/edit', [PartyController::class, 'edit'])->name('edit');
                Route::put('/{id}', [PartyController::class, 'update'])->name('update');
                Route::post('/{id}/save-employees', [PartyController::class, 'saveEmployees'])->name('save-employees');
                Route::patch('/{id}/approve', [PartyController::class, 'approve'])->name('approve');
                Route::patch('/{id}/reject', [PartyController::class, 'reject'])->name('reject');
                Route::post('/bulk-assign', [PartyController::class, 'bulkAssign'])->name('bulk-assign');
            });

            // Delete — requires customers.delete
            Route::delete('/{id}', [PartyController::class, 'destroy'])->middleware('permission:customers.delete')->name('destroy');
        });

        // ── Transaction Management ────────────────────────────────────────────
        Route::get('/transactions', [TransactionController::class, 'index'])->middleware('permission:transactions.view')->name('transactions.index');

        // ── Orders ────────────────────────────────────────────────────────────
        Route::middleware('permission:orders.view|orders.view-team|orders.view-all|orders.view-own|orders.manage')->group(function () {
            Route::get('/orders/data', [\App\Http\Controllers\Admin\OrderController::class, 'data'])->name('orders.data');
            Route::resource('orders', \App\Http\Controllers\Admin\OrderController::class)->only(['index', 'show']);
            Route::patch('/orders/{order}/status', [\App\Http\Controllers\Admin\OrderController::class, 'updateStatus'])->name('orders.update-status');
        });

        // ── Payments ─────────────────────────────────────────────────────────
        Route::middleware('permission:payments.view|payments.view-team|payments.view-all|payments.view-own|payments.manage')->group(function () {
            Route::get('/payments', [\App\Http\Controllers\Admin\PaymentWebController::class, 'index'])->name('payments.index');
        });
        Route::middleware('permission:payments.manage')->group(function () {
            Route::patch('/payments/{payment}/confirm', [\App\Http\Controllers\Admin\PaymentWebController::class, 'confirm'])->name('payments.confirm');
            Route::patch('/payments/{payment}/reject', [\App\Http\Controllers\Admin\PaymentWebController::class, 'reject'])->name('payments.reject');
        });

        // ── Visits ────────────────────────────────────────────────────────────
        Route::get('/visits', [\App\Http\Controllers\Admin\VisitController::class, 'index'])->middleware('permission:visits.view|visits.view-team|visits.view-all|visits.view-own|visits.manage')->name('visits.index');

        // ── Day Start / Tour Plan ─────────────────────────────────────────────
        Route::prefix('day-starts')->name('day-starts.')->group(function () {
            Route::get('/', [DayStartController::class, 'index'])->middleware('permission:day-start.view|day-start.view-team|day-start.view-all|day-start.view-own|day-start.manage')->name('index');
            Route::get('/data', [DayStartController::class, 'data'])->middleware('permission:day-start.view|day-start.view-team|day-start.view-all|day-start.view-own|day-start.manage')->name('data');
            Route::get('/create', [DayStartController::class, 'create'])->middleware('permission:day-start.create')->name('create');
            Route::post('/', [DayStartController::class, 'store'])->middleware('permission:day-start.create')->name('store');
            Route::get('/{id}', [DayStartController::class, 'show'])->middleware('permission:day-start.view|day-start.view-team|day-start.view-all|day-start.view-own|day-start.manage')->name('show');
            Route::post('/{id}/status', [DayStartController::class, 'updateStatus'])->middleware('permission:day-start.approve')->name('updateStatus');
            Route::post('/{id}/edit-closing', [DayStartController::class, 'updateClosing'])->middleware('role_or_permission:Super Admin|Admin|Manager|day-start.approve')->name('updateClosing');
            Route::post('/{id}/edit-start', [DayStartController::class, 'updateStart'])->middleware('role_or_permission:Super Admin|Admin|Manager|day-start.approve')->name('updateStart');
        });

        // ── Attendance ────────────────────────────────────────────────────────
        Route::middleware('permission:attendance.view|attendance.view-team|attendance.view-all|attendance.view-own|attendance.manage')->group(function () {
            Route::get('/attendance', [\App\Http\Controllers\Admin\AttendanceWebController::class, 'index'])->name('attendance.index');
        });
        Route::post('/attendance/adjust', [\App\Http\Controllers\Admin\AttendanceAdjustmentWebController::class, 'adjust'])
            ->middleware('permission:attendance.manage')
            ->name('attendance.adjust');

        // ── Audit Logs ────────────────────────────────────────────────────────
        Route::get('/audit-logs', [\App\Http\Controllers\Admin\AuditLogController::class, 'index'])
            ->middleware('permission:audit-logs.view')
            ->name('audit-logs.index');

        // ── Leave Management ──────────────────────────────────────────────────
        Route::get('/leave', [\App\Http\Controllers\Admin\LeaveWebController::class, 'index'])
            ->middleware('role_or_permission:Super Admin|leaves.view|leaves.view-team|leaves.view-all|leaves.view-own|leaves.manage')
            ->name('leave.index');

        Route::get('/leave/data', [\App\Http\Controllers\Admin\LeaveWebController::class, 'data'])
            ->middleware('role_or_permission:Super Admin|leaves.view|leaves.view-team|leaves.view-all|leaves.view-own|leaves.manage')
            ->name('leave.data');

        Route::get('/leave/conflicts-data', [\App\Http\Controllers\Admin\LeaveWebController::class, 'conflictsData'])
            ->middleware('role_or_permission:Super Admin|leaves.view|leaves.view-team|leaves.view-all|leaves.view-own|leaves.manage')
            ->name('leave.conflicts-data');

        Route::post('/leave/{leave}/approve', [\App\Http\Controllers\Admin\LeaveWebController::class, 'approve'])
            ->middleware('role_or_permission:Super Admin|leaves.approve|leaves.manage')
            ->name('leave.approve');

        Route::post('/leave/{leave}/reject', [\App\Http\Controllers\Admin\LeaveWebController::class, 'reject'])
            ->middleware('role_or_permission:Super Admin|leaves.reject|leaves.manage')
            ->name('leave.reject');

        Route::post('/leave/{leave}/cancel', [\App\Http\Controllers\Admin\LeaveWebController::class, 'cancelApproved'])
            ->middleware('role_or_permission:Super Admin|leaves.manage|leaves.approve')
            ->name('leave.cancel');

        Route::post('/leave/{leave}/resolve-conflict', [\App\Http\Controllers\Admin\LeaveWebController::class, 'resolveConflict'])
            ->middleware('role_or_permission:Super Admin|leaves.manage|leaves.approve')
            ->name('leave.resolve-conflict');

        // ── Leave Types (Admin/Super Admin) ───────────────────────────────────
        Route::middleware('permission:leaves.manage')->group(function () {
            Route::get('/leave-types', [\App\Http\Controllers\Admin\LeaveTypeWebController::class, 'index'])->name('leave-types.index');
            Route::post('/leave-types', [\App\Http\Controllers\Admin\LeaveTypeWebController::class, 'store'])->name('leave-types.store');
            Route::put('/leave-types/{leaveType}', [\App\Http\Controllers\Admin\LeaveTypeWebController::class, 'update'])->name('leave-types.update');
            Route::post('/leave-types/{leaveType}/toggle', [\App\Http\Controllers\Admin\LeaveTypeWebController::class, 'toggleStatus'])->name('leave-types.toggle');
            Route::delete('/leave-types/{leaveType}', [\App\Http\Controllers\Admin\LeaveTypeWebController::class, 'destroy'])->name('leave-types.destroy');
        });

        // ── Leave Balances (Admin/Super Admin) ────────────────────────────────
        Route::middleware('permission:leaves.manage')->group(function () {
            Route::get('/leave-balances', [\App\Http\Controllers\Admin\LeaveBalanceWebController::class, 'index'])->name('leave-balances.index');
            Route::put('/leave-balances/{balance}', [\App\Http\Controllers\Admin\LeaveBalanceWebController::class, 'update'])->name('leave-balances.update');
        });

        // ── Holidays ──────────────────────────────────────────────────────────
        Route::middleware('permission:master-data.manage')->group(function () {
            Route::get('/holidays', [\App\Http\Controllers\Admin\HolidayWebController::class, 'index'])->name('holidays.index');
            Route::post('/holidays', [\App\Http\Controllers\Admin\HolidayWebController::class, 'store'])->name('holidays.store');
            Route::put('/holidays/{holiday}', [\App\Http\Controllers\Admin\HolidayWebController::class, 'update'])->name('holidays.update');
            Route::delete('/holidays/{holiday}', [\App\Http\Controllers\Admin\HolidayWebController::class, 'destroy'])->name('holidays.destroy');
        });

        // ── Master Data ───────────────────────────────────────────────────────
        Route::prefix('master-data')->name('master-data.')->middleware('permission:master-data.manage')->group(function () {
            Route::get('/{type}', [MasterDataController::class, 'index'])->name('index');
            Route::post('/{type}', [MasterDataController::class, 'store'])->name('store');
            Route::put('/{type}/{id}', [MasterDataController::class, 'update'])->name('update');
            Route::delete('/{type}/{id}', [MasterDataController::class, 'destroy'])->name('destroy');
        });

        // ── Locations Master (Districts & Areas) ───────────────────────────────
        Route::prefix('locations')->name('locations.')->middleware('permission:locations.view|locations.manage')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\LocationController::class, 'index'])->name('index');
            Route::post('/districts', [\App\Http\Controllers\Admin\LocationController::class, 'storeDistrict'])->name('districts.store');
            Route::put('/districts/{district}', [\App\Http\Controllers\Admin\LocationController::class, 'updateDistrict'])->name('districts.update');
            Route::delete('/districts/{district}', [\App\Http\Controllers\Admin\LocationController::class, 'destroyDistrict'])->name('districts.destroy');

            Route::post('/areas', [\App\Http\Controllers\Admin\LocationController::class, 'storeArea'])->name('areas.store');
            Route::put('/areas/{area}', [\App\Http\Controllers\Admin\LocationController::class, 'updateArea'])->name('areas.update');
            Route::delete('/areas/{area}', [\App\Http\Controllers\Admin\LocationController::class, 'destroyArea'])->name('areas.destroy');
        });

        // ── Reports ───────────────────────────────────────────────────────────
        Route::prefix('reports')->name('reports.')->middleware('permission:reports.view|reports.view-team|reports.view-all|reports.view-own|reports.manage')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\ReportController::class, 'index'])->name('index');
            Route::get('/matrix-json', [\App\Http\Controllers\Admin\ReportController::class, 'matrixJson'])->name('matrix-json');  // AJAX
            Route::get('/scorecard/{id}', [\App\Http\Controllers\Admin\ReportController::class, 'employeeScorecard'])->name('scorecard');
            Route::get('/daily-ledger', [\App\Http\Controllers\Admin\ReportController::class, 'dailyLedger'])->name('daily-ledger');
            Route::get('/export-csv', [\App\Http\Controllers\Admin\ReportController::class, 'exportCsv'])->name('export-csv');
            Route::get('/fieldworker-parties', [\App\Http\Controllers\Admin\ReportController::class, 'fieldworkerParties'])->name('fieldworker-parties');
        });

        // ── Expenses ──────────────────────────────────────────────────────────
        Route::middleware('permission:expenses.view|expenses.view-team|expenses.view-all|expenses.view-own|expenses.approve|expenses.final_approve|expenses.manage')->group(function () {
            Route::get('/expenses/data', [\App\Http\Controllers\Admin\ExpenseController::class, 'data'])->name('expenses.data');
            Route::get('/expenses', [\App\Http\Controllers\Admin\ExpenseController::class, 'index'])->name('expenses.index');
            Route::get('/expenses/reports/monthly', [\App\Http\Controllers\Admin\ExpenseReportController::class, 'monthlySummary'])->name('expenses.reports.monthly');
            Route::get('/expenses/reports/statement/{user_id}', [\App\Http\Controllers\Admin\ExpenseReportController::class, 'workerStatement'])->name('expenses.reports.statement');
            Route::get('/expenses/reports/daily/{day_start_id}', [\App\Http\Controllers\Admin\ExpenseReportController::class, 'dailyReceipt'])->name('expenses.reports.daily');
        });
        Route::patch('/expenses/{expense}/status', [\App\Http\Controllers\Admin\ExpenseController::class, 'updateStatus'])
            ->middleware('permission:expenses.approve|expenses.final_approve|expenses.manage')
            ->name('expenses.updateStatus');
        Route::match(['patch', 'post'], '/expenses/{expense}', [\App\Http\Controllers\Admin\ExpenseController::class, 'update'])
            ->middleware('permission:expenses.approve|expenses.final_approve|expenses.manage|expenses.edit')
            ->name('expenses.update');

        // ── Fuel & Petrol Allowance Rates ────────────────────────────────────
        Route::prefix('fuel-rates')->name('fuel-rates.')->middleware('permission:fuel-rates.view|fuel-rates.manage')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\FuelRateWebController::class, 'index'])->name('index');
            Route::middleware('permission:fuel-rates.manage')->group(function () {
                Route::post('/', [\App\Http\Controllers\Admin\FuelRateWebController::class, 'store'])->name('store');
                Route::delete('/{id}', [\App\Http\Controllers\Admin\FuelRateWebController::class, 'destroy'])->name('destroy');
                Route::post('/resolve', [\App\Http\Controllers\Admin\FuelRateWebController::class, 'resolve'])->name('resolve');
            });
        });

        // ── Products ──────────────────────────────────────────────────────────
        Route::prefix('products')->name('products.')->group(function () {
            Route::get('/', [ProductController::class, 'index'])->middleware('permission:products.view')->name('index');
            Route::get('/create', [ProductController::class, 'create'])->middleware('permission:products.create')->name('create');
            Route::post('/', [ProductController::class, 'store'])->middleware('permission:products.create')->name('store');
            Route::get('/{id}/edit', [ProductController::class, 'edit'])->middleware('permission:products.edit')->name('edit');
            Route::put('/{id}', [ProductController::class, 'update'])->middleware('permission:products.edit')->name('update');
            Route::delete('/{id}', [ProductController::class, 'destroy'])->middleware('permission:products.delete')->name('destroy');
            Route::patch('/{id}/toggle', [ProductController::class, 'toggleStatus'])->middleware('permission:products.edit')->name('toggleStatus');

            // Product Images
            Route::post('/{id}/images', [ProductImageController::class, 'store'])->middleware('permission:products.edit')->name('images.store');
            Route::delete('/images/{imageId}', [ProductImageController::class, 'destroy'])->middleware('permission:products.delete')->name('images.destroy');
            Route::patch('/images/{imageId}/featured', [ProductImageController::class, 'setFeatured'])->middleware('permission:products.edit')->name('images.featured');
            Route::post('/{id}/images/reorder', [ProductImageController::class, 'reorder'])->middleware('permission:products.edit')->name('images.reorder');
        });

        // ── Product Attributes ────────────────────────────────────────────────
        Route::prefix('product-attributes')->name('product-attributes.')->middleware('permission:master-data.manage')->group(function () {
            Route::get('/', [ProductAttributeController::class, 'index'])->name('index');
            Route::get('/create', [ProductAttributeController::class, 'create'])->name('create');
            Route::post('/', [ProductAttributeController::class, 'store'])->name('store');
            Route::get('/{id}/edit', [ProductAttributeController::class, 'edit'])->name('edit');
            Route::put('/{id}', [ProductAttributeController::class, 'update'])->name('update');
            Route::delete('/{id}', [ProductAttributeController::class, 'destroy'])->name('destroy');
            Route::post('/{id}/values', [ProductAttributeController::class, 'addValue'])->name('add-value');
            Route::delete('/values/{valueId}', [ProductAttributeController::class, 'deleteValue'])->name('delete-value');
        });

        // ── Dealers, Activities, Meetings, Product Demo, Deals ───────────────
        Route::get('/dealers', fn() => view('admin.dealers.index'))->middleware('permission:dealers.view')->name('dealers.index');
        Route::get('/activities', fn() => view('admin.activities.index'))->middleware('permission:activities.view')->name('activities.index');
        Route::get('/meetings', fn() => view('admin.meetings.index'))->middleware('permission:meetings.view')->name('meetings.index');
        Route::get('/product-demo', fn() => view('admin.product-demo.index'))->middleware('permission:product-demo.view')->name('product-demo.index');
        Route::get('/deals', fn() => view('admin.deals.index'))->middleware('permission:deals.view')->name('deals.index');

        // ── System Settings (Admin & Super Admin only) ────────────────────────
        Route::middleware('permission:settings.manage')->group(function () {
            Route::get('/settings', [\App\Http\Controllers\Admin\SettingWebController::class, 'index'])->name('settings.index');
            Route::post('/settings', [\App\Http\Controllers\Admin\SettingWebController::class, 'update'])->name('settings.update');
        });

    }); // end auth middleware
}); // end admin prefix
