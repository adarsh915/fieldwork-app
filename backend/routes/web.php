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
    // Admin auth (session-based)
    Route::middleware('guest')->get('/login', [AdminAuthController::class, 'showLogin'])->name('login');
    Route::middleware('guest')->post('/login', [AdminAuthController::class, 'login'])->name('login.submit');
    Route::post('/logout', [AdminAuthController::class, 'logout'])->middleware('auth')->name('logout');

    // Admin panel - Allow all authenticated users, permissions control what they see
    Route::middleware(['auth'])->group(function () {
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
        
        // Debug page (remove in production)
        Route::view('/debug-permissions', 'admin.debug-permissions')->name('debug.permissions');
        Route::view('/test-employee-form', 'admin.test-employee-form')->name('test.employee.form');

        // Access control
        Route::get('/users', [UserController::class, 'index'])->middleware('permission:users.view')->name('users.index');
        Route::post('/users/{user}/roles', [UserController::class, 'updateRoles'])->middleware('permission:users.manage')->name('users.roles.update');

        // Employee Management
        Route::prefix('employees')->name('employees.')->group(function () {
            Route::get('/', [EmployeeWebController::class, 'index'])->middleware('permission:employees.view')->name('index');
            Route::get('/create', [EmployeeWebController::class, 'create'])->middleware('permission:employees.create')->name('create');
            Route::post('/', [EmployeeWebController::class, 'store'])->middleware('permission:employees.create')->name('store');
            Route::get('/{id}/edit', [EmployeeWebController::class, 'edit'])->middleware('permission:employees.edit')->name('edit');
            Route::put('/{id}', [EmployeeWebController::class, 'update'])->middleware('permission:employees.edit')->name('update');
            Route::delete('/{id}', [EmployeeWebController::class, 'destroy'])->middleware('permission:employees.delete')->name('destroy');
            Route::patch('/{id}/toggle-status', [EmployeeWebController::class, 'toggleStatus'])->middleware('permission:employees.edit')->name('toggleStatus');

            // View assigned parties for a specific employee
            Route::get('/{id}/parties', [EmployeeWebController::class, 'showParties'])->middleware('permission:employees.view')->name('parties');

            // View comprehensive fieldworker profile
            Route::get('/{id}/profile', [EmployeeWebController::class, 'showProfile'])->middleware('permission:employees.view')->name('profile');

            // Bulk sync assigned parties (customers) to a specific employee
            Route::post('/{employee}/parties/bulk-sync', [EmployeeWebController::class, 'bulkSyncParties'])
                ->middleware('permission:employees.edit')
                ->name('parties.bulkSync');
        });

        // Role & Permission Management - SUPER ADMIN ONLY
        Route::middleware('role:Super Admin')->group(function () {
            Route::get('/roles', [RoleController::class, 'index'])->name('roles.index');
            Route::get('/roles/create', [RoleController::class, 'create'])->name('roles.create');
            Route::post('/roles', [RoleController::class, 'store'])->name('roles.store');
            Route::get('/roles/{id}/edit', [RoleController::class, 'edit'])->name('roles.edit');
            Route::put('/roles/{id}', [RoleController::class, 'update'])->name('roles.update');
            Route::delete('/roles/{id}', [RoleController::class, 'destroy'])->name('roles.destroy');
            
            // Permission Management
            Route::get('/permissions/create', [RoleController::class, 'createPermission'])->name('permissions.create');
            Route::post('/permissions', [RoleController::class, 'storePermission'])->name('permissions.store');
            Route::delete('/permissions/{id}', [RoleController::class, 'destroyPermission'])->name('permissions.destroy');
        });

        // Customer Management
        Route::get('/customers', [CustomerController::class, 'index'])->middleware('permission:customers.view')->name('customers.index');

        // Party Management (full CRUD + employee assignment + bulk import)
        Route::prefix('parties')->name('parties.')->middleware('permission:customers.view')->group(function () {
            Route::get('/',                           [PartyController::class, 'index'])           ->name('index');
            Route::get('/data',                       [PartyController::class, 'data'])            ->name('data');
            Route::post('/bulk-assign',               [PartyController::class, 'bulkAssign'])      ->name('bulk-assign');
            Route::get('/{id}/assignment-history',    [PartyController::class, 'assignmentHistory'])->name('assignment-history');
            Route::get('/export',                     [PartyController::class, 'export'])          ->name('export');
            Route::get('/export-pdf',                 [PartyController::class, 'exportPdf'])       ->name('export-pdf');
            Route::get('/{id}/export-pdf',            [PartyController::class, 'exportSinglePdf'])->name('export-single-pdf');
            Route::get('/{id}/export-excel',          [PartyController::class, 'exportSingleExcel'])->name('export-single-excel');
            Route::get('/create',                     [PartyController::class, 'create'])          ->name('create');
            Route::get('/{id}/show',                  [PartyController::class, 'show'])            ->name('show');
            Route::post('/',                          [PartyController::class, 'store'])           ->name('store');

            // Bulk import — static paths before /{id} wildcards
            Route::get('/bulk-import',                [PartyController::class, 'bulkImportForm']) ->name('bulk-import');
            Route::post('/bulk-import',               [PartyController::class, 'bulkImport'])     ->name('bulk-import.process');
            Route::get('/download-template',          [PartyController::class, 'downloadTemplate'])->name('download-template');

            Route::get('/{id}/edit',                  [PartyController::class, 'edit'])            ->name('edit');
            Route::put('/{id}',                       [PartyController::class, 'update'])          ->name('update');
            Route::delete('/{id}',                    [PartyController::class, 'destroy'])         ->name('destroy');
            Route::get('/{id}/assign-employees',      [PartyController::class, 'assignEmployees']) ->name('assign-employees');
            Route::post('/{id}/save-employees',       [PartyController::class, 'saveEmployees'])   ->name('save-employees');
        });

        // Field Worker Management - View all field workers with stats, edit, and view detailed metrics
        Route::prefix('field-workers')->name('field-workers.')->middleware('permission:employees.view')->group(function () {
            Route::get('/',                                    [FieldWorkerManagementController::class, 'index'])              ->name('index');
            Route::get('/{id}/metrics',                        [FieldWorkerManagementController::class, 'showMetrics'])        ->name('metrics');
            Route::get('/{id}/visits',                         [FieldWorkerManagementController::class, 'showVisits'])         ->name('visits');
            Route::get('/{id}/visits/{visitId}',               [FieldWorkerManagementController::class, 'visitDetails'])       ->name('visit-details');
            Route::get('/{id}/transactions',                   [FieldWorkerManagementController::class, 'showTransactions'])   ->name('transactions');
            Route::get('/{id}/parties',                        [FieldWorkerManagementController::class, 'getAssignedParties'])->name('parties');
            Route::get('/{id}/performance',                    [FieldWorkerManagementController::class, 'getPerformanceMetrics'])->name('performance');
            Route::get('/{id}/export',                         [FieldWorkerManagementController::class, 'exportReport'])       ->name('export');
            Route::get('/{id}/edit',                           [FieldWorkerManagementController::class, 'edit'])               ->middleware('permission:employees.edit')->name('edit');
            Route::put('/{id}',                                [FieldWorkerManagementController::class, 'update'])             ->middleware('permission:employees.edit')->name('update');
        });

        // Transaction Management
        Route::get('/transactions', [TransactionController::class, 'index'])->middleware('permission:transactions.view')->name('transactions.index');

        // Master Data (Tour Purposes, Tour Types, Vehicle Types)
        Route::prefix('master-data')->name('master-data.')->middleware('permission:master-data.manage')->group(function () {
            Route::get('/{type}', [MasterDataController::class, 'index'])->name('index');
            Route::post('/{type}', [MasterDataController::class, 'store'])->name('store');
            Route::put('/{type}/{id}', [MasterDataController::class, 'update'])->name('update');
            Route::delete('/{type}/{id}', [MasterDataController::class, 'destroy'])->name('destroy');
        });

        // Day Start / Tour Plan
        Route::prefix('day-starts')->name('day-starts.')->group(function () {
            Route::get('/', [DayStartController::class, 'index'])->name('index');
            Route::get('/create', [DayStartController::class, 'create'])->name('create')->middleware('permission:day-start.create');
            Route::post('/', [DayStartController::class, 'store'])->name('store')->middleware('permission:day-start.create');
            Route::get('/{id}', [DayStartController::class, 'show'])->name('show');
        });

        // Notifications
        Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');
        Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead'])->name('notifications.read-all');

        // Product Management
        Route::prefix('products')->name('products.')->middleware('permission:customers.view')->group(function () {
            Route::get('/',                 [ProductController::class, 'index'])        ->name('index');
            Route::get('/create',           [ProductController::class, 'create'])       ->name('create');
            Route::post('/',                [ProductController::class, 'store'])        ->name('store');
            Route::get('/{id}/edit',        [ProductController::class, 'edit'])         ->name('edit');
            Route::put('/{id}',             [ProductController::class, 'update'])       ->name('update');
            Route::delete('/{id}',          [ProductController::class, 'destroy'])      ->name('destroy');
            Route::patch('/{id}/toggle',    [ProductController::class, 'toggleStatus']) ->name('toggleStatus');
            
            // Product Images
            Route::post('/{id}/images',             [ProductImageController::class, 'store'])      ->name('images.store');
            Route::delete('/images/{imageId}',      [ProductImageController::class, 'destroy'])    ->name('images.destroy');
            Route::patch('/images/{imageId}/featured', [ProductImageController::class, 'setFeatured']) ->name('images.featured');
            Route::post('/{id}/images/reorder',     [ProductImageController::class, 'reorder'])    ->name('images.reorder');
        });

        // Product Attributes & Variations
        Route::prefix('product-attributes')->name('product-attributes.')->middleware('permission:customers.view')->group(function () {
            Route::get('/',                 [ProductAttributeController::class, 'index'])->name('index');
            Route::get('/create',           [ProductAttributeController::class, 'create'])->name('create');
            Route::post('/',                [ProductAttributeController::class, 'store'])->name('store');
            Route::get('/{id}/edit',        [ProductAttributeController::class, 'edit'])->name('edit');
            Route::put('/{id}',             [ProductAttributeController::class, 'update'])->name('update');
            Route::delete('/{id}',          [ProductAttributeController::class, 'destroy'])->name('destroy');
            Route::post('/{id}/values',     [ProductAttributeController::class, 'addValue'])->name('add-value');
            Route::delete('/values/{valueId}', [ProductAttributeController::class, 'deleteValue'])->name('delete-value');
        });

        // Fieldwork UI Modules (Frontend-only mock data pages)
        Route::resource('orders', \App\Http\Controllers\Admin\OrderController::class)->only(['index', 'show'])->middleware('permission:orders.view');
        Route::patch('orders/{order}/status', [\App\Http\Controllers\Admin\OrderController::class, 'updateStatus'])->name('orders.update-status')->middleware('permission:orders.view');
        Route::get('/payments', fn() => view('admin.payments.index'))->middleware('permission:payments.view')->name('payments.index');
        Route::get('/visits', [\App\Http\Controllers\Admin\VisitController::class, 'index'])->middleware('permission:visits.view')->name('visits.index');
        Route::get('/dealers', fn() => view('admin.dealers.index'))->middleware('permission:dealers.view')->name('dealers.index');
        Route::get('/activities', fn() => view('admin.activities.index'))->middleware('permission:activities.view')->name('activities.index');
        Route::prefix('reports')->name('reports.')->middleware('permission:reports.view')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\ReportController::class, 'index'])->name('index');
            Route::get('/fieldworker-parties', [\App\Http\Controllers\Admin\ReportController::class, 'fieldworkerParties'])->name('fieldworker-parties');
        });
        Route::get('/expenses', [\App\Http\Controllers\Admin\ExpenseController::class, 'index'])->middleware('permission:expenses.view')->name('expenses.index');
        Route::patch('/expenses/{expense}/status', [\App\Http\Controllers\Admin\ExpenseController::class, 'updateStatus'])->middleware('permission:expenses.view')->name('expenses.updateStatus');
        
        // Expense Reports
        Route::get('/expenses/reports/monthly', [\App\Http\Controllers\Admin\ExpenseReportController::class, 'monthlySummary'])->middleware('permission:expenses.view')->name('expenses.reports.monthly');
        Route::get('/expenses/reports/daily/{day_start_id}', [\App\Http\Controllers\Admin\ExpenseReportController::class, 'dailyReceipt'])->middleware('permission:expenses.view')->name('expenses.reports.daily');

        Route::get('/leave', fn() => view('admin.leave.index'))->middleware('permission:leave.view')->name('leave.index');
        Route::get('/meetings', fn() => view('admin.meetings.index'))->middleware('permission:meetings.view')->name('meetings.index');
        Route::get('/product-demo', fn() => view('admin.product-demo.index'))->middleware('permission:product-demo.view')->name('product-demo.index');
        Route::get('/deals', fn() => view('admin.deals.index'))->middleware('permission:deals.view')->name('deals.index');
    });
});
