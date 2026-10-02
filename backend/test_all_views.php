<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

auth()->login(App\Models\User::first());
view()->share('errors', new Illuminate\Support\ViewErrorBag());

$svc = new App\Services\ReportingService();
$range = $svc->resolveDateRange('this_month');
$matrix = $svc->getEmployeePerformanceMatrix($range);

foreach ($matrix['users_matrix'] as $user) {
    $scorecard = $svc->getEmployeeScorecard($user['user_id'], $range);
    // Render scorecard view in memory to test all blade syntax and date formatting
    $view = view('admin.reports.employee-scorecard', [
        'scorecard' => $scorecard,
        'dateRange' => $range,
    ])->render();
}

// Render daily ledger view in memory
$ledgerRecords = $svc->getDailyLedger($range);
$employeesList = App\Models\User::get(['id', 'name', 'employee_id']);
$viewLedger = view('admin.reports.daily-ledger', [
    'records' => $ledgerRecords,
    'dateRange' => $range,
    'employeesList' => $employeesList,
    'selectedUser' => null,
])->render();

// Render index view in memory
$viewIndex = view('admin.reports.index', [
    'matrix' => $matrix['users_matrix'],
    'totals' => $matrix['totals'],
    'dateRange' => $range,
    'employeesList' => $employeesList,
    'selectedUser' => null,
])->render();

echo "ALL VIEWS RENDERED SUCCESSFULLY WITH 0 ERRORS!" . PHP_EOL;
