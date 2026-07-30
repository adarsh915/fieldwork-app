<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$today = date('Y-m-d');
\App\Models\ExpenseBill::where('date', $today)->delete();
\App\Models\Visit::whereDate('created_at', $today)->delete();
\App\Models\DayStart::where('date', $today)->delete();

echo "Deleted all test data for today ({$today}) successfully.\n";
