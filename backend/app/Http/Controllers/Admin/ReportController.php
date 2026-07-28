<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;

class ReportController extends Controller
{
    /**
     * Display a listing of available reports.
     */
    public function index()
    {
        return view('admin.reports.index');
    }

    /**
     * Display the Fieldworker Parties Report.
     */
    public function fieldworkerParties(Request $request)
    {
        $employeeId = $request->input('employee_id');

        // Query fieldworkers (users) that have assigned parties
        $query = User::has('parties')->with('parties')->orderBy('name');

        if ($employeeId) {
            $query->where('id', $employeeId);
        }

        $fieldworkers = $query->get();

        // Get all users for the filter dropdown
        $employeesList = User::orderBy('name')->get(['id', 'name', 'employee_id']);

        return view('admin.reports.fieldworker-parties', compact('fieldworkers', 'employeesList', 'employeeId'));
    }
}

