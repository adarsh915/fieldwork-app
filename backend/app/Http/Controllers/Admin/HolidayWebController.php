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

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'year' => $year,
                'holidays' => $holidays->map(function ($h) {
                    return [
                        'id' => $h->id,
                        'date' => $h->date->format('Y-m-d'),
                        'date_formatted' => $h->date->format('d M Y'),
                        'day_name' => $h->date->format('l'),
                        'name' => $h->name,
                        'type' => $h->type,
                        'type_label' => $h->type === 'national' ? 'National Holiday' : 'Company Holiday',
                        'created_by_name' => $h->createdBy->name ?? 'Admin',
                    ];
                }),
            ]);
        }

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

        $holiday = Holiday::create([
            'date'       => $validated['date'],
            'name'       => $validated['name'],
            'type'       => $validated['type'],
            'created_by' => auth()->id(),
        ]);

        $holiday->load('createdBy');

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Holiday declared successfully.',
                'holiday' => [
                    'id' => $holiday->id,
                    'date' => $holiday->date->format('Y-m-d'),
                    'date_formatted' => $holiday->date->format('d M Y'),
                    'day_name' => $holiday->date->format('l'),
                    'name' => $holiday->name,
                    'type' => $holiday->type,
                    'type_label' => $holiday->type === 'national' ? 'National Holiday' : 'Company Holiday',
                    'created_by_name' => $holiday->createdBy->name ?? auth()->user()->name ?? 'Admin',
                ],
            ]);
        }

        return back()->with('success', 'Holiday added successfully.');
    }

    public function update(Request $request, Holiday $holiday)
    {
        $validated = $request->validate([
            'date' => 'required|date|unique:holidays,date,' . $holiday->id,
            'name' => 'required|string|max:100',
            'type' => 'required|in:national,company',
        ], [
            'date.unique' => 'A holiday already exists for this date.',
        ]);

        $holiday->update([
            'date' => $validated['date'],
            'name' => $validated['name'],
            'type' => $validated['type'],
        ]);

        $holiday->load('createdBy');

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Holiday updated successfully.',
                'holiday' => [
                    'id' => $holiday->id,
                    'date' => $holiday->date->format('Y-m-d'),
                    'date_formatted' => $holiday->date->format('d M Y'),
                    'day_name' => $holiday->date->format('l'),
                    'name' => $holiday->name,
                    'type' => $holiday->type,
                    'type_label' => $holiday->type === 'national' ? 'National Holiday' : 'Company Holiday',
                    'created_by_name' => $holiday->createdBy->name ?? 'Admin',
                ],
            ]);
        }

        return back()->with('success', 'Holiday updated successfully.');
    }

    public function destroy(Request $request, Holiday $holiday)
    {
        $name = $holiday->name;
        $holiday->delete();

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Holiday '{$name}' deleted successfully.",
            ]);
        }

        return back()->with('success', 'Holiday deleted successfully.');
    }
}
