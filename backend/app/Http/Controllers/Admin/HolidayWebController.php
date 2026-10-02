<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Holiday;
use Illuminate\Http\Request;

class HolidayWebController extends Controller
{
    public function index(Request $request)
    {
        $year = (int) $request->input('year', now()->year);

        $holidays = Holiday::forYear($year)
            ->with('createdBy')
            ->orderBy('date', 'asc')
            ->get();

        return view('admin.holidays.index', compact('holidays', 'year'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'date' => 'required|date|unique:holidays,date',
            'name' => 'required|string|max:100',
            'type' => 'required|in:national,company',
        ], [
            'date.unique' => 'A holiday already exists for this date.',
        ]);

        Holiday::create([
            'date'       => $validated['date'],
            'name'       => $validated['name'],
            'type'       => $validated['type'],
            'created_by' => auth()->id(),
        ]);

        return back()->with('success', 'Holiday added successfully.');
    }

    public function update(Request $request, Holiday $holiday)
    {
        $validated = $request->validate([
            'date' => 'required|date|unique:holidays,date,' . $holiday->id,
            'name' => 'required|string|max:100',
            'type' => 'required|in:national,company',
        ]);

        $holiday->update([
            'date' => $validated['date'],
            'name' => $validated['name'],
            'type' => $validated['type'],
        ]);

        return back()->with('success', 'Holiday updated successfully.');
    }

    public function destroy(Holiday $holiday)
    {
        $holiday->delete();
        return back()->with('success', 'Holiday deleted successfully.');
    }
}
