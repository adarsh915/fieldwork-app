<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DayController;
use App\Http\Controllers\Api\MasterController;
use App\Http\Controllers\Api\PartyController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// ─── Public Routes ─────────────────────────────────────────────────────────────
Route::prefix('auth')->name('api.auth.')->group(function () {
    Route::post('login', [AuthController::class, 'login'])->middleware('throttle:10,1')->name('login');
});

// ─── Protected Routes ──────────────────────────────────────────────────────────
Route::middleware('auth:sanctum')->group(function () {

    // Auth
    Route::prefix('auth')->name('api.auth.')->group(function () {
        Route::get('me',             [AuthController::class, 'me'])->name('me');
        Route::post('logout',        [AuthController::class, 'logout'])->name('logout');
        Route::post('fcm-token',     [AuthController::class, 'updateFcmToken'])->name('fcm-token');
    });

    // In-App Notifications
    Route::prefix('notifications')->name('api.notifications.')->group(function () {
        Route::get('/',               [\App\Http\Controllers\Api\NotificationController::class, 'index'])->name('index');
        Route::get('latest',          [\App\Http\Controllers\Api\NotificationController::class, 'latest'])->name('latest');
        Route::get('unread-count',    [\App\Http\Controllers\Api\NotificationController::class, 'unreadCount'])->name('unread-count');
        Route::post('read-all',       [\App\Http\Controllers\Api\NotificationController::class, 'markAllRead'])->name('read-all');
        Route::post('{id}/read',      [\App\Http\Controllers\Api\NotificationController::class, 'markRead'])->name('read');
    });

    // Mobile Reports & MIS
    Route::prefix('reports')->name('api.reports.')->group(function () {
        Route::get('mobile-summary', [\App\Http\Controllers\Api\ReportController::class, 'mobileSummary'])->name('mobile-summary');
    });

    // Master Data (dropdowns / pickers)
    Route::prefix('master')->name('api.master.')->group(function () {
        Route::get('tour-purposes',  [MasterController::class, 'tourPurposes'])->name('tour-purposes');
        Route::get('tour-types',     [MasterController::class, 'tourTypes'])->name('tour-types');
        Route::get('vehicle-types',  [MasterController::class, 'vehicleTypes'])->name('vehicle-types');
        Route::get('locations',        [MasterController::class, 'locations'])->name('locations');
        Route::get('india-locations',  [MasterController::class, 'indiaLocations'])->name('india-locations');
        Route::get('parties',          [MasterController::class, 'parties'])->name('parties');
        Route::get('parties/{id}',   [MasterController::class, 'partyDetails'])->name('party.details');
    });

    // Day Lifecycle
    Route::prefix('day')->name('api.day.')->group(function () {
        Route::get('status', [DayController::class, 'status'])->name('status');
        Route::post('start', [DayController::class, 'start'])->name('start');
        Route::post('end',   [DayController::class, 'end'])->name('end');
    });

    // Catalog
    Route::get('/products', [\App\Http\Controllers\Api\ProductController::class, 'index']);

    // Orders
    Route::get('/orders/config', [\App\Http\Controllers\Api\OrderController::class, 'config']);
    Route::get('/orders', [\App\Http\Controllers\Api\OrderController::class, 'index']);
    Route::get('/orders/{id}', [\App\Http\Controllers\Api\OrderController::class, 'show']);
    Route::post('/orders', [\App\Http\Controllers\Api\OrderController::class, 'store']);

    // Visits
    Route::prefix('visits')->name('api.visits.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\VisitController::class, 'index'])->name('index');
        Route::get('/party/{partyId}/status', [\App\Http\Controllers\Api\VisitController::class, 'status'])->name('status');
        Route::get('/party/{partyId}/history', [\App\Http\Controllers\Api\VisitController::class, 'partyHistory'])->name('party-history');
        Route::post('/check-in', [\App\Http\Controllers\Api\VisitController::class, 'checkIn'])->name('check-in');
        Route::patch('/{id}/check-out', [\App\Http\Controllers\Api\VisitController::class, 'checkOut'])->name('check-out');
        Route::patch('/{id}/notes', [\App\Http\Controllers\Api\VisitController::class, 'updateNotes'])->name('update-notes');
    });

    // Expenses
    Route::prefix('expenses')->name('api.expenses.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\ExpenseController::class, 'index'])->name('index');
        Route::get('/monthly-summary', [\App\Http\Controllers\Api\ExpenseController::class, 'monthlySummary'])->name('monthly-summary');
        Route::get('/statement', [\App\Http\Controllers\Api\ExpenseController::class, 'statement'])->name('statement');
        Route::get('/categories', [\App\Http\Controllers\Api\ExpenseController::class, 'categories'])->name('categories');
        Route::post('/', [\App\Http\Controllers\Api\ExpenseController::class, 'store'])->name('store');
        Route::post('/{expense}', [\App\Http\Controllers\Api\ExpenseController::class, 'update'])->name('update');
    });

    // Parties (field worker: view assigned + submit new)
    Route::get('/parties',  [PartyController::class, 'index'])->name('api.parties.index');
    Route::post('/parties', [PartyController::class, 'store'])->name('api.parties.store');

    // ─── Holidays (read for all authenticated users) ────────────────────────
    Route::get('/holidays', [\App\Http\Controllers\Api\HolidayController::class, 'index'])
        ->name('api.holidays.index');

    // ─── Leave Types (dropdown for apply form) ──────────────────────────────
    Route::get('/leave-types', [\App\Http\Controllers\Api\LeaveController::class, 'types'])
        ->name('api.leave-types.index');

    // ─── My Leaves (employee) ───────────────────────────────────────────────
    Route::prefix('leaves')->name('api.leaves.')->group(function () {
        Route::get('/',               [\App\Http\Controllers\Api\LeaveController::class, 'index'])->name('index');
        Route::get('/balance',        [\App\Http\Controllers\Api\LeaveController::class, 'balance'])->name('balance');
        Route::post('/apply',                  [\App\Http\Controllers\Api\LeaveController::class, 'apply'])->name('apply');
        Route::delete('/{id}/cancel',          [\App\Http\Controllers\Api\LeaveController::class, 'cancel'])->name('cancel');
        Route::post('/{id}/request-cancellation', [\App\Http\Controllers\Api\LeaveController::class, 'requestCancellation'])->name('request-cancellation');
    });

    // ─── My Attendance (employee) ───────────────────────────────────────────
    Route::prefix('attendance')->name('api.attendance.')->group(function () {
        Route::get('/calendar', [\App\Http\Controllers\Api\AttendanceController::class, 'myCalendar'])->name('calendar');
    });

    // Payments
    Route::prefix('payments')->name('api.payments.')->group(function () {
        Route::post('/collect',         [\App\Http\Controllers\Api\PaymentController::class, 'collect'])->name('collect');
        Route::get('/my-collections',   [\App\Http\Controllers\Api\PaymentController::class, 'myCollections'])->name('my-collections');
        Route::get('/settings',         [\App\Http\Controllers\Api\PaymentController::class, 'getSettings'])->name('settings');
    });
    Route::get('/parties/{id}/payments', [\App\Http\Controllers\Api\PaymentController::class, 'partyPayments'])->name('api.parties.payments');

    // ─── Admin Routes ───────────────────────────────────────────────────────
    Route::prefix('admin')->name('api.admin.')->group(function () {
        // Payment Management
        Route::get('/payments/pending',        [\App\Http\Controllers\Api\PaymentController::class, 'pendingQueue'])->name('payments.pending');
        Route::get('/payments',                [\App\Http\Controllers\Api\PaymentController::class, 'adminIndex'])->name('payments.index');
        Route::patch('/payments/{id}/confirm', [\App\Http\Controllers\Api\PaymentController::class, 'confirm'])->name('payments.confirm');
        Route::patch('/payments/{id}/reject',  [\App\Http\Controllers\Api\PaymentController::class, 'reject'])->name('payments.reject');

        // Leave management
        Route::get('/leaves',                          [\App\Http\Controllers\Api\LeaveController::class, 'adminIndex'])->middleware('role_or_permission:Super Admin|leaves.view')->name('leaves.index');
        Route::get('/leaves/conflicts',                [\App\Http\Controllers\Api\LeaveController::class, 'indexConflicts'])->middleware('role_or_permission:Super Admin|leaves.view')->name('leaves.conflicts');
        Route::post('/leaves/{id}/approve',            [\App\Http\Controllers\Api\LeaveController::class, 'approve'])->middleware('role_or_permission:Super Admin|leaves.approve|leaves.manage')->name('leaves.approve');
        Route::post('/leaves/{id}/reject',             [\App\Http\Controllers\Api\LeaveController::class, 'reject'])->middleware('role_or_permission:Super Admin|leaves.reject|leaves.manage')->name('leaves.reject');
        Route::patch('/leaves/{id}/resolve-conflict',  [\App\Http\Controllers\Api\LeaveController::class, 'resolveConflict'])->middleware('role_or_permission:Super Admin|leaves.manage|leaves.approve')->name('leaves.resolve-conflict');

        // Attendance
        Route::get('/attendance',           [\App\Http\Controllers\Api\AttendanceController::class, 'adminCalendar'])->name('attendance.calendar');
        Route::get('/attendance/team',      [\App\Http\Controllers\Api\AttendanceController::class, 'teamSummary'])->name('attendance.team');

        // Holidays (write)
        Route::post('/holidays',            [\App\Http\Controllers\Api\HolidayController::class, 'store'])->name('holidays.store');
        Route::delete('/holidays/{id}',     [\App\Http\Controllers\Api\HolidayController::class, 'destroy'])->name('holidays.destroy');

        // System Settings
        Route::get('/settings',             [\App\Http\Controllers\Api\AdminSettingsController::class, 'index'])->name('settings.index');
        Route::patch('/settings/{key}',     [\App\Http\Controllers\Api\AdminSettingsController::class, 'update'])->name('settings.update');

        // Admin Manual Overrides & Audit Trail
        Route::patch('/attendance/{user_id}/{date}/adjust', [\App\Http\Controllers\Api\AdminAttendanceAdjustmentController::class, 'adjustAttendance'])->name('attendance.adjust');
        Route::patch('/leaves/{id}/edit',                   [\App\Http\Controllers\Api\AdminAttendanceAdjustmentController::class, 'editLeave'])->name('leaves.edit');
        Route::patch('/leave-balances/{id}/adjust',          [\App\Http\Controllers\Api\AdminAttendanceAdjustmentController::class, 'adjustBalance'])->name('leave-balances.adjust');
        Route::get('/audit-logs',                           [\App\Http\Controllers\Api\AdminAttendanceAdjustmentController::class, 'auditLogs'])->name('audit-logs.index');

        // Fuel / Petrol Allowance Rate Management
        Route::get('/fuel-rates',             [\App\Http\Controllers\Admin\FuelRateController::class, 'index'])->name('fuel-rates.index');
        Route::post('/fuel-rates',            [\App\Http\Controllers\Admin\FuelRateController::class, 'store'])->name('fuel-rates.store');
        Route::delete('/fuel-rates/{id}',     [\App\Http\Controllers\Admin\FuelRateController::class, 'destroy'])->name('fuel-rates.destroy');
        Route::post('/fuel-rates/resolve',    [\App\Http\Controllers\Admin\FuelRateController::class, 'resolve'])->name('fuel-rates.resolve');

        // Manager & VP Approvals Hub
        Route::get('/approvals/counts',                  [\App\Http\Controllers\Api\AdminApprovalController::class, 'counts'])->name('approvals.counts');
        Route::get('/approvals/team',                    [\App\Http\Controllers\Api\AdminApprovalController::class, 'teamMembers'])->name('approvals.team');
        Route::get('/approvals/day-starts',              [\App\Http\Controllers\Api\AdminApprovalController::class, 'dayStarts'])->name('approvals.day-starts');
        Route::patch('/approvals/day-starts/{id}',       [\App\Http\Controllers\Api\AdminApprovalController::class, 'updateDayStart'])->name('approvals.day-starts.update');
        Route::post('/approvals/day-starts/{id}/action', [\App\Http\Controllers\Api\AdminApprovalController::class, 'actOnDayStart'])->name('approvals.day-starts.action');
        Route::get('/approvals/expenses',                [\App\Http\Controllers\Api\AdminApprovalController::class, 'expenses'])->name('approvals.expenses');
        Route::patch('/approvals/expenses/{id}',         [\App\Http\Controllers\Api\AdminApprovalController::class, 'updateExpense'])->name('approvals.expenses.update');
        Route::post('/approvals/expenses/{id}/action',   [\App\Http\Controllers\Api\AdminApprovalController::class, 'actOnExpense'])->name('approvals.expenses.action');
    });
});
