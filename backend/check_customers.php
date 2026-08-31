<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$customers = \App\Models\Customer::orderBy('id', 'desc')->take(5)->get();
foreach ($customers as $c) {
    echo "ID: {$c->id}, Name: {$c->name}, Approval: {$c->approval_status}, Active: " . ($c->is_active ? 'Yes' : 'No') . ", Submitted By: {$c->submitted_by}\n";
}
