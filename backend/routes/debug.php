<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

// DEBUG ROUTE - Remove in production!
Route::post('/admin/employees/debug-store', function (Request $request) {
    \Log::info('=== DEBUG: Form Submitted ===', [
        'all_data' => $request->except('password'),
        'has_password' => $request->has('password'),
        'password_length' => $request->password ? strlen($request->password) : 0,
        'method' => $request->method(),
        'url' => $request->url(),
        'route' => $request->route()->getName() ?? 'unknown',
        'headers' => $request->headers->all(),
    ]);
    
    return response()->json([
        'success' => true,
        'message' => 'Debug data logged',
        'data' => $request->except('password'),
        'log_location' => 'storage/logs/laravel.log'
    ]);
})->name('admin.employees.debug-store');
