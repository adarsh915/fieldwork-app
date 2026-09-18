<?php
require 'vendor/autoload.php';
 = require_once 'bootstrap/app.php';
->make('Illuminate\\Contracts\\Console\\Kernel')->bootstrap();

 = \App\Models\User::where('name', 'like', '%Bob%')->first();
if () {
    echo 'EXPENSES FOR ' . ->name . ' (ID ' . ->id . '):
';
     = \App\Models\ExpenseBill::with('category')->where('user_id', ->id)->get();
    echo 'Total Expense Records: ' . ->count() . '
';
    foreach ( as ) {
        echo 'ID: ' . ->id . ' | Date: ' . ->date . ' | Amount: ' . ->amount . ' | Status: ' . ->status . ' | Category: ' . (->category ? ->category->name : 'N/A') . ' | SysGen: ' . (->is_system_generated ? 'Yes' : 'No') . '
';
    }
} else {
    echo 'Bob Field not found
';
}
