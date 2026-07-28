<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\User;
use App\Models\PartyEmployeeMap;
use App\Models\AuditLog;
use App\Events\PartyAssigned;
use App\Events\PartyUpdated;
use App\Http\Requests\Admin\Party\PartyUpdateRequest;
use App\Http\Requests\Admin\Party\PartyAssignRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;
use Yajra\DataTables\Facades\DataTables;

/**
 * PartyController - Full CRUD for Parties (Customers) with assignment management.
 */
class PartyController extends Controller
{
    /**
     * List all parties with search and pagination.
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', Customer::class);

        $employees = User::role('Field Worker')
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'employee_id']);

        $partyTypes = ['Retailer', 'Distributor', 'Wholesaler', 'End Customer'];
        $categories = ['A', 'B', 'C', 'D'];

        return view('admin.parties.index', compact('employees', 'partyTypes', 'categories'));
    }

    /**
     * DataTables server-side processing for party list.
     */
    public function data(Request $request)
    {
        $this->authorize('viewAny', Customer::class);

        $query = Customer::query()
            ->with(['activeAssignment.employee', 'auditLogs' => function($q) {
                $q->latest()->limit(1);
            }])
            ->select('customers.*');

        // Filters
        if ($request->filled('search.value') || $request->filled('search')) {
            $search = $request->input('search.value') ?? $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('party_code', 'like', "%{$search}%")
                  ->orWhere('mobile', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('city', 'like', "%{$search}%");
            });
        }

        if ($request->filled('employee_id')) {
            $query->whereHas('activeAssignment', function ($q) use ($request) {
                $q->where('employee_id', $request->employee_id);
            });
        }

        if ($request->filled('party_type')) {
            $query->where('party_type', $request->party_type);
        }

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        return DataTables::of($query)
            ->addColumn('party_info', function ($party) {
                return [
                    'id' => $party->id,
                    'party_code' => $party->party_code ?? '—',
                    'name' => $party->name,
                    'city' => $party->city ?? '—',
                ];
            })
            ->addColumn('contact', function ($party) {
                return [
                    'mobile' => $party->mobile ?? '—',
                    'email' => $party->email ?? '—',
                ];
            })
            ->addColumn('classification', function ($party) {
                return [
                    'type' => $party->party_type ?? '—',
                    'category' => $party->category ?? '—',
                ];
            })
            ->addColumn('assignment', function ($party) {
                $active = $party->activeAssignment;
                return [
                    'employee_name' => $active ? $active->employee->name : null,
                    'employee_id' => $active ? $active->employee->employee_id : null,
                    'assigned_at' => $active ? $active->assigned_at->format('M d, Y') : null,
                ];
            })
            ->addColumn('status', function ($party) {
                return $party->is_active ? 'active' : 'inactive';
            })
            ->addColumn('actions', function ($party) {
                return [
                    'view' => route('admin.parties.show', $party->id),
                    'edit' => route('admin.parties.edit', $party->id),
                    'history' => route('admin.parties.assignment-history', $party->id),
                    'delete' => route('admin.parties.destroy', $party->id),
                    'can_edit' => auth()->user()->can('update', $party),
                    'can_delete' => auth()->user()->can('delete', $party),
                ];
            })
            ->rawColumns(['actions'])
            ->make(true);
    }

    /**
     * Show Party Profile Dashboard
     */
    public function show($id)
    {
        $party = Customer::with(['user', 'transactions', 'visits.user', 'orders.items.product'])->findOrFail($id);
        
        $purchasedProducts = $party->orders->flatMap->items
            ->groupBy('product_id')
            ->map(function ($items) {
                return (object)[
                    'product' => $items->first()->product,
                    'total_quantity' => $items->sum('quantity'),
                    'total_spent' => $items->sum('total'),
                    'times_bought' => $items->count(),
                    'last_purchased' => $items->sortByDesc(fn($item) => $item->order->created_at ?? $item->created_at)->first()->order->created_at ?? null,
                ];
            })
            ->sortByDesc('total_quantity');

        return view('admin.parties.show', compact('party', 'purchasedProducts'));
    }

    /**
     * Export parties to CSV.
     */
    public function export(Request $request)
    {
        $search     = $request->input('q');
        $employeeId = $request->input('employee_id');

        $query = Customer::query()->with('user');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name',    'like', "%{$search}%")
                  ->orWhere('mobile', 'like', "%{$search}%")
                  ->orWhere('email',  'like', "%{$search}%");
            });
        }

        if ($employeeId) {
            $query->where('user_id', $employeeId);
        }

        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename="parties_export_' . date('Y-m-d_His') . '.csv"',
            'Pragma'              => 'no-cache',
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            'Expires'             => '0',
        ];

        $callback = function() use ($query) {
            $file = fopen('php://output', 'w');
            
            fputs($file, "\xEF\xBB\xBF");
            fputcsv($file, ['ID', 'Party Name', 'Mobile', 'Email', 'Address', 'Land Area', 'Latitude', 'Longitude', 'Assigned Employee']);

            $query->orderBy('id')->chunk(500, function ($parties) use ($file) {
                foreach ($parties as $party) {
                    fputcsv($file, [
                        $party->id,
                        $party->name,
                        $party->mobile ?? '',
                        $party->email ?? '',
                        $party->address ?? '',
                        $party->land_area ?? '',
                        $party->latitude ?? '',
                        $party->longitude ?? '',
                        $party->user->name ?? ''
                    ]);
                }
            });

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Export parties to PDF.
     */
    public function exportPdf(Request $request)
    {
        $search     = $request->input('q');
        $employeeId = $request->input('employee_id');

        $query = Customer::query()->with('user');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name',    'like', "%{$search}%")
                  ->orWhere('mobile', 'like', "%{$search}%")
                  ->orWhere('email',  'like', "%{$search}%");
            });
        }

        if ($employeeId) {
            $query->where('user_id', $employeeId);
        }

        $parties = $query->orderBy('name')->take(500)->get();

        $pdf = Pdf::loadView('admin.parties.pdf', compact('parties'));
        
        return $pdf->download('parties_export_' . date('Y-m-d_His') . '.pdf');
    }

    /**
     * Export a single party profile to PDF.
     */
    public function exportSinglePdf($id)
    {
        $party = Customer::with(['user', 'transactions', 'visits.user', 'orders.items.product'])->findOrFail($id);
        
        $purchasedProducts = $party->orders->flatMap->items
            ->groupBy('product_id')
            ->map(function ($items) {
                return (object)[
                    'product' => $items->first()->product,
                    'total_quantity' => $items->sum('quantity'),
                    'total_spent' => $items->sum('total'),
                    'times_bought' => $items->count(),
                    'last_purchased' => $items->sortByDesc(fn($item) => $item->order->created_at ?? $item->created_at)->first()->order->created_at ?? null,
                ];
            })
            ->sortByDesc('total_quantity');

        $pdf = Pdf::loadView('admin.parties.single_pdf', compact('party', 'purchasedProducts'));
        return $pdf->download('party_' . str_replace(' ', '_', strtolower($party->name)) . '_' . date('Y-m-d_His') . '.pdf');
    }

    /**
     * Export a single party profile to Excel (CSV).
     */
    public function exportSingleExcel($id)
    {
        $party = Customer::with(['user', 'transactions', 'visits.user', 'orders.items.product'])->findOrFail($id);

        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename="party_' . str_replace(' ', '_', strtolower($party->name)) . '_' . date('Y-m-d_His') . '.csv"',
            'Pragma'              => 'no-cache',
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            'Expires'             => '0',
        ];

        $callback = function() use ($party) {
            $file = fopen('php://output', 'w');
            fputs($file, "\xEF\xBB\xBF"); // BOM for UTF-8 in Excel

            // Customer Details Section
            fputcsv($file, ['CUSTOMER DETAILS']);
            fputcsv($file, ['Customer ID', 'Name', 'Email', 'Phone', 'Address', 'Assigned To']);
            fputcsv($file, [
                $party->id,
                $party->name,
                $party->email ?? 'N/A',
                $party->phone ?? 'N/A',
                $party->address ?? 'N/A',
                $party->user->name ?? 'Unassigned'
            ]);
            fputcsv($file, []);

            // KPI Summary Section
            fputcsv($file, ['KPI SUMMARY']);
            fputcsv($file, ['Total Visits', 'Total Orders', 'Total Transactions', 'Lifetime Revenue']);
            fputcsv($file, [
                $party->visits->count(),
                $party->orders->count(),
                $party->transactions->count(),
                '$' . number_format($party->orders->where('status', '!=', 'cancelled')->sum('total_amount'), 2)
            ]);
            fputcsv($file, []);

            // Products Section
            $purchasedProducts = $party->orders->flatMap->items
                ->groupBy('product_id')
                ->map(function ($items) {
                    return (object)[
                        'product' => $items->first()->product,
                        'total_quantity' => $items->sum('quantity'),
                        'total_spent' => $items->sum('total'),
                        'times_bought' => $items->count(),
                        'last_purchased' => $items->sortByDesc(fn($item) => $item->order->created_at ?? $item->created_at)->first()->order->created_at ?? null,
                    ];
                })
                ->sortByDesc('total_quantity');

            fputcsv($file, ['TOP PRODUCTS PURCHASED']);
            fputcsv($file, ['Product Name', 'SKU', 'Times Bought', 'Total Qty', 'Total Spent', 'Last Purchased']);
            foreach ($purchasedProducts as $item) {
                fputcsv($file, [
                    $item->product->name ?? 'Unknown',
                    $item->product->code ?? 'N/A',
                    $item->times_bought,
                    $item->total_quantity,
                    '$' . number_format($item->total_spent, 2),
                    $item->last_purchased ? $item->last_purchased->format('Y-m-d h:i A') : 'N/A'
                ]);
            }
            fputcsv($file, []);

            // Recent Orders Section
            fputcsv($file, ['RECENT ORDERS']);
            fputcsv($file, ['Date', 'Type', 'Status', 'Amount']);
            foreach ($party->orders->sortByDesc('created_at') as $order) {
                fputcsv($file, [
                    $order->created_at->format('Y-m-d'),
                    $order->order_type ?? 'Standard',
                    ucfirst($order->status),
                    '$' . number_format($order->total_amount, 2)
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $employees = User::orderBy('name')->get(['id', 'name']);
        return view('admin.parties.create', compact('employees'));
    }

    /**
     * Store a new party.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'      => ['required', 'string', 'max:255'],
            'mobile'    => ['nullable', 'string', 'max:20'],
            'email'     => ['nullable', 'email', 'max:255'],
            'address'   => ['nullable', 'string', 'max:1000'],
            'user_id'   => ['nullable', 'integer', 'exists:users,id'],
            'land_area' => ['nullable', 'string', 'max:100'],
            'latitude'  => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
        ]);

        $party = Customer::create($validated);

        return redirect()->route('admin.parties.index')
            ->with('status', 'Party "' . $party->name . '" created successfully!');
    }

    /**
     * Show edit party form.
     */
    public function edit($id)
    {
        $party     = Customer::findOrFail($id);
        $employees = User::orderBy('name')->get(['id', 'name']);
        
        // Get currently assigned employee IDs
        $assignedEmployeeIds = $party->assignments()
            ->where('is_active', true)
            ->pluck('employee_id')
            ->toArray();

        return view('admin.parties.edit', compact('party', 'employees', 'assignedEmployeeIds'));
    }

    /**
     * Update a party.
     */
    public function update(PartyUpdateRequest $request, $id)
    {
        $party = Customer::findOrFail($id);
        $this->authorize('update', $party);

        $oldValues = $party->only([
            'party_code', 'name', 'owner_name', 'mobile', 'email', 'address',
            'city', 'state', 'pincode', 'party_type', 'category',
            'credit_limit', 'credit_days', 'opening_balance', 'is_active'
        ]);

        $party->update($request->validated());

        $newValues = $party->only([
            'party_code', 'name', 'owner_name', 'mobile', 'email', 'address',
            'city', 'state', 'pincode', 'party_type', 'category',
            'credit_limit', 'credit_days', 'opening_balance', 'is_active'
        ]);

        event(new PartyUpdated($party, $oldValues, $newValues, auth()->user()));

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Party updated successfully',
                'party' => $party->fresh(['activeAssignment.employee']),
            ]);
        }

        return redirect()->route('admin.parties.index')
            ->with('status', 'Party "' . $party->name . '" updated successfully!');
    }

    /**
     * Delete a party.
     */
    public function destroy($id)
    {
        $party = Customer::findOrFail($id);
        $this->authorize('delete', $party);

        $name  = $party->name;
        $party->delete();

        return redirect()->route('admin.parties.index')
            ->with('status', 'Party "' . $name . '" deleted successfully!');
    }

    /**
     * Bulk assign parties to a field worker.
     */
    public function bulkAssign(PartyAssignRequest $request)
    {
        $this->authorize('assign', Customer::class);

        $validated = $request->validated();
        $partyIds = $validated['party_ids'];
        $employeeId = $validated['employee_id'];
        $assignedBy = auth()->id();

        DB::beginTransaction();
        try {
            $assignedCount = 0;
            $skippedCount = 0;

            foreach ($partyIds as $partyId) {
                $party = Customer::find($partyId);
                if (!$party) {
                    $skippedCount++;
                    continue;
                }

                // Check if already assigned to same employee
                $existingAssignment = PartyEmployeeMap::where('party_id', $partyId)
                    ->where('employee_id', $employeeId)
                    ->where('is_active', true)
                    ->first();

                if ($existingAssignment) {
                    $skippedCount++;
                    continue;
                }

                // Deactivate current assignment
                PartyEmployeeMap::where('party_id', $partyId)
                    ->where('is_active', true)
                    ->update([
                        'is_active' => false,
                        'unassigned_at' => now(),
                    ]);

                // Create new assignment
                $assignment = PartyEmployeeMap::create([
                    'party_id' => $partyId,
                    'employee_id' => $employeeId,
                    'assigned_by' => $assignedBy,
                    'assigned_at' => now(),
                    'is_active' => true,
                ]);

                event(new PartyAssigned($party, $assignment, auth()->user()));
                $assignedCount++;
            }

            DB::commit();

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => "Successfully assigned {$assignedCount} parties. Skipped {$skippedCount}.",
                    'assigned' => $assignedCount,
                    'skipped' => $skippedCount,
                ]);
            }

            return redirect()->back()
                ->with('status', "Successfully assigned {$assignedCount} parties. Skipped {$skippedCount}.");

        } catch (\Exception $e) {
            DB::rollBack();
            
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Assignment failed: ' . $e->getMessage(),
                ], 500);
            }

            return redirect()->back()
                ->withErrors(['error' => 'Assignment failed: ' . $e->getMessage()]);
        }
    }

    /**
     * Get assignment history for a party.
     */
    public function assignmentHistory($id)
    {
        $party = Customer::findOrFail($id);
        $this->authorize('view', $party);

        $history = PartyEmployeeMap::where('party_id', $id)
            ->with(['employee', 'assignedBy'])
            ->orderBy('assigned_at', 'desc')
            ->get();

        if (request()->expectsJson()) {
            return response()->json([
                'success' => true,
                'party' => $party,
                'history' => $history->map(function ($record) {
                    return [
                        'id' => $record->id,
                        'employee' => [
                            'id' => $record->employee->id,
                            'name' => $record->employee->name,
                            'employee_id' => $record->employee->employee_id,
                        ],
                        'assigned_by' => $record->assignedBy ? $record->assignedBy->name : 'System',
                        'assigned_at' => $record->assigned_at->format('M d, Y h:i A'),
                        'unassigned_at' => $record->unassigned_at ? $record->unassigned_at->format('M d, Y h:i A') : null,
                        'is_active' => $record->is_active,
                        'duration_days' => $record->unassigned_at ? $record->assigned_at->diffInDays($record->unassigned_at) : $record->assigned_at->diffInDays(now()),
                    ];
                }),
            ]);
        }

        return view('admin.parties.assignment-history', compact('party', 'history'));
    }

    // ─── BULK IMPORT ───────────────────────────────────────────────────────────

    public function downloadTemplate()
    {
        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename="parties_import_template.csv"',
            'Pragma'              => 'no-cache',
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            'Expires'             => '0',
        ];

        $rows = [
            ['name', 'mobile', 'email', 'address', 'land_area', 'latitude', 'longitude', 'employee_email'],
            ['ABC Traders',      '9876543210', 'abc@example.com', '123 MG Road', '500 sqft', '19.0760', '72.8777', 'employee@company.com'],
        ];

        $callback = function () use ($rows) {
            $handle = fopen('php://output', 'w');
            foreach ($rows as $row) {
                fputcsv($handle, $row);
            }
            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function bulkImportForm()
    {
        $employees = User::orderBy('name')->get(['id', 'name', 'email']);
        return view('admin.parties.bulk-import', compact('employees'));
    }

    public function bulkImport(Request $request)
    {
        $request->validate([
            'csv_file'           => ['required', 'file', 'mimes:csv,txt', 'max:2048'],
            'assign_employee_id' => ['nullable', 'integer', 'exists:users,id'],
            'duplicate_handling' => ['required', 'in:skip,update'],
        ]);

        $file              = $request->file('csv_file');
        $globalEmployeeId  = $request->input('assign_employee_id');
        $duplicateHandling = $request->input('duplicate_handling', 'skip');

        $emailToId = User::pluck('id', 'email')->toArray();
        $handle = fopen($file->getRealPath(), 'r');
        $rawHeader = fgetcsv($handle);
        if (!$rawHeader) {
            fclose($handle);
            return back()->withErrors(['csv_file' => 'The CSV file appears to be empty.']);
        }
        $header = array_map(
            fn($h) => strtolower(trim(str_replace(["\xEF\xBB\xBF", "\r", "\n"], '', $h))),
            $rawHeader
        );

        if (!in_array('name', $header)) {
            fclose($handle);
            return back()->withErrors(['csv_file' => 'CSV must contain a "name" header column.']);
        }

        $created = 0; $updated = 0; $skipped = 0;
        $rowErrors = []; $rowNum = 1;

        DB::beginTransaction();
        try {
            while (($row = fgetcsv($handle)) !== false) {
                $rowNum++;
                $data = [];
                foreach ($header as $i => $col) {
                    $data[$col] = isset($row[$i]) ? trim($row[$i]) : '';
                }

                $name = $data['name'] ?? '';
                if ($name === '') continue;

                $attributes = [
                    'name'      => $name,
                    'mobile'    => ($data['mobile'] ?? '') !== '' ? $data['mobile'] : null,
                    'email'     => ($data['email'] ?? '') !== '' ? $data['email'] : null,
                    'address'   => ($data['address'] ?? '') !== '' ? $data['address'] : null,
                    'land_area' => ($data['land_area'] ?? '') !== '' ? $data['land_area'] : null,
                    'latitude'  => ($data['latitude'] ?? '') !== '' ? (float) $data['latitude'] : null,
                    'longitude' => ($data['longitude'] ?? '') !== '' ? (float) $data['longitude'] : null,
                ];

                $userId = $globalEmployeeId;
                if (!empty($data['employee_email'])) {
                    $empEmail = trim($data['employee_email']);
                    if (isset($emailToId[$empEmail])) {
                        $userId = $emailToId[$empEmail];
                    } else {
                        $rowErrors[] = "Row {$rowNum}: employee email \"{$empEmail}\" not found.";
                    }
                }
                
                if ($userId) {
                    $attributes['user_id'] = $userId;
                }

                $existing = Customer::where('name', $name)->first();

                if ($existing) {
                    if ($duplicateHandling === 'skip') {
                        $skipped++;
                        continue;
                    }
                    $existing->update($attributes);
                    $updated++;
                } else {
                    Customer::create($attributes);
                    $created++;
                }
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            fclose($handle);
            return back()->withErrors(['csv_file' => 'Import failed: ' . $e->getMessage()]);
        }
        fclose($handle);
        return redirect()->route('admin.parties.bulk-import')
            ->with('import_result', ['created' => $created, 'updated' => $updated, 'skipped' => $skipped, 'errors' => $rowErrors])
            ->with('status', "Import complete — {$created} created, {$updated} updated, {$skipped} skipped.");
    }
}
