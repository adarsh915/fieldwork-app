<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\User;
use App\Models\Order;
use App\Models\Visit;
use App\Models\Transaction;
use App\Models\PartyEmployeeMap;
use App\Models\AuditLog;
use App\Events\PartyAssigned;
use App\Events\PartyUpdated;
use App\Http\Requests\Admin\Party\PartyUpdateRequest;
use App\Http\Requests\Admin\Party\PartyAssignRequest;
use App\Services\DataScope;
use Illuminate\Http\JsonResponse;
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

        // Stats counts & Data Scoping
        $visibleIds = DataScope::visibleUserIds('parties');

        // Scoped employees for filter dropdown
        $empQuery = User::with('roles')->where('is_active', true);
        if ($visibleIds !== null) {
            $empQuery->whereIn('id', $visibleIds);
        }
        $employees = $empQuery->orderBy('name')->get();

        // Scoped employees for assignment modals
        $assignableEmployees = DataScope::assignableUsers()
            ->with('roles')
            ->orderBy('name')
            ->get();

        $partyTypes = ['Retailer', 'Distributor', 'Wholesaler', 'End Customer'];
        $categories = ['A', 'B', 'C', 'D'];
        $districts = \App\Models\District::with(['areas' => fn($q) => $q->where('is_active', true)->orderBy('name')])
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('admin.parties.index', compact('employees', 'assignableEmployees', 'partyTypes', 'categories', 'districts'));
    }

    /**
     * DataTables server-side processing for party list.
     */
    public function data(Request $request)
    {
        $this->authorize('viewAny', Customer::class);

        $query = Customer::query()
            ->with(['activeAssignment.employee', 'submittedBy', 'auditLogs' => function($q) {
                $q->latest()->limit(1);
            }])
            ->select('customers.*');

        // Data scoping: strictly restrict party list to parties actively assigned to visible users (hide unassigned parties)
        DataScope::applyParties($query);

        // Filters
        $search = $request->input('search.value');
        if (empty($search) && is_string($request->input('search'))) {
            $search = $request->input('search');
        }
        if (!empty($search) && is_string($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('party_code', 'like', "%{$search}%")
                  ->orWhere('mobile', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('city', 'like', "%{$search}%");
            });
        }

        if ($request->filled('employee_id')) {
            $empId = $request->employee_id;
            $query->where(function ($q) use ($empId) {
                $q->whereHas('assignments', function ($sq) use ($empId) {
                    $sq->where('employee_id', $empId)->where('is_active', true);
                })->orWhere(function ($sq) use ($empId) {
                    $sq->where('submitted_by', $empId)->where('approval_status', 'pending');
                });
            });
        }

        if ($request->filled('party_type')) {
            $query->where('party_type', $request->party_type);
        }

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        if ($request->filled('status')) {
            if ($request->status === 'pending') {
                $query->where('approval_status', 'pending');
            } elseif ($request->status === 'rejected') {
                $query->where('approval_status', 'rejected');
            } elseif ($request->status === 'active') {
                $query->where('approval_status', 'approved')->where('is_active', true);
            } elseif ($request->status === 'inactive') {
                $query->where('approval_status', 'approved')->where('is_active', false);
            }
        }

        return DataTables::of($query)
            ->addColumn('party_info', function ($party) {
                return [
                    'id' => $party->id,
                    'party_code' => $party->party_code ?? '—',
                    'name' => $party->name,
                    'city' => $party->city ?? '—',
                    'submitted_by_name' => $party->submittedBy ? $party->submittedBy->name_with_role : null,
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
                    'employee_name' => $active ? $active->employee->name_with_role : null,
                    'assigned_at' => $active ? $active->assigned_at->format('M d, Y') : null,
                ];
            })
            ->addColumn('status', function ($party) {
                if ($party->approval_status === 'pending') {
                    return 'pending';
                }
                if ($party->approval_status === 'rejected') {
                    return 'rejected';
                }
                return $party->is_active ? 'active' : 'inactive';
            })
            ->addColumn('actions', function ($party) {
                return [
                    'id' => $party->id,
                    'view' => route('admin.parties.show', $party->id),
                    'edit' => route('admin.parties.edit', $party->id),
                    'history' => route('admin.parties.assignment-history', $party->id),
                    'delete' => route('admin.parties.destroy', $party->id),
                    'approve' => route('admin.parties.approve', $party->id),
                    'reject' => route('admin.parties.reject', $party->id),
                    'approval_status' => $party->approval_status,
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
        // Load only summary data on initial page paint — tabs load via AJAX
        $party = Customer::with([
            'activeAssignment.employee',
            'orders' => fn($q) => $q->latest()->limit(5),
        ])->findOrFail($id);

        $purchasedProducts = Order::where('party_id', $id)
            ->with('items.product')
            ->latest()
            ->limit(100)
            ->get()
            ->flatMap->items
            ->groupBy('product_id')
            ->map(function ($items) {
                return (object)[
                    'product'        => $items->first()->product,
                    'total_quantity' => $items->sum('quantity'),
                    'total_spent'    => $items->sum('total'),
                    'times_bought'   => $items->count(),
                    'last_purchased' => $items->sortByDesc(fn($i) => $i->order?->created_at ?? $i->created_at)->first()->order?->created_at,
                ];
            })
            ->sortByDesc('total_quantity');

        return view('admin.parties.show', compact('party', 'purchasedProducts'));
    }

    /**
     * AJAX: GET /admin/parties/{id}/tab-data?tab=visits|orders|transactions
     * Serves paginated tab data for the party profile page.
     */
    public function tabData(Request $request, $id): JsonResponse
    {
        $party = Customer::findOrFail($id);
        $this->authorize('view', $party);

        $tab  = $request->input('tab', 'orders');
        $page = max(1, (int) $request->input('page', 1));

        match ($tab) {
            'visits' => $data = Visit::where('party_id', $id)
                ->with('user:id,name,employee_id')
                ->latest('check_in_time')
                ->paginate(15),
            'transactions' => $data = Transaction::where('party_id', $id)
                ->latest()
                ->paginate(15),
            default => $data = Order::where('party_id', $id)
                ->with('items.product')
                ->latest('order_date')
                ->paginate(15),
        };

        return response()->json($data);
    }

    /**
     * Export parties to CSV.
     */
    public function export(Request $request)
    {
        $search     = $request->input('q');
        $employeeId = $request->input('employee_id');

        $query = Customer::query()->with('activeAssignment.employee');
        DataScope::applyParties($query);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name',    'like', "%{$search}%")
                  ->orWhere('mobile', 'like', "%{$search}%")
                  ->orWhere('email',  'like', "%{$search}%");
            });
        }

        if ($employeeId) {
            $query->whereHas('assignments', function ($sq) use ($employeeId) {
                $sq->where('employee_id', $employeeId)->where('is_active', true);
            });
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
                        $party->activeAssignment?->employee?->name_with_role ?? '—'
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

        $query = Customer::query()->with('activeAssignment.employee');
        DataScope::applyParties($query);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name',    'like', "%{$search}%")
                  ->orWhere('mobile', 'like', "%{$search}%")
                  ->orWhere('email',  'like', "%{$search}%");
            });
        }

        if ($employeeId) {
            $query->whereHas('assignments', function ($sq) use ($employeeId) {
                $sq->where('employee_id', $employeeId)->where('is_active', true);
            });
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
        $employees = DataScope::assignableUsers()->orderBy('name')->get(['id', 'name']);
        $districts = \App\Models\District::with(['areas' => fn($q) => $q->where('is_active', true)->orderBy('name')])
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
        return view('admin.parties.create', compact('employees', 'districts'));
    }

    /**
     * Store a new party.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'         => ['required', 'string', 'max:255'],
            'mobile'       => ['nullable', 'string', 'max:20'],
            'email'        => ['nullable', 'email', 'max:255'],
            'address'      => ['nullable', 'string', 'max:1000'],
            'district_id'  => ['nullable', 'exists:districts,id'],
            'area_id'      => ['nullable', 'exists:areas,id'],
            'user_id'      => ['nullable', 'integer', 'exists:users,id'],
            'land_area'    => ['nullable', 'string', 'max:100'],
            'latitude'     => ['nullable', 'numeric', 'between:-90,90'],
            'longitude'    => ['nullable', 'numeric', 'between:-180,180'],
            'is_active'    => ['nullable', 'boolean'],
            'employee_ids' => ['nullable', 'array'],
            'employee_ids.*' => ['exists:users,id'],
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);
        $validated['approval_status'] = 'approved';
        $validated['submitted_by'] = auth()->id() ?? 1;

        $party = Customer::create(array_filter($validated, function ($k) {
            return $k !== 'employee_ids';
        }, ARRAY_FILTER_USE_KEY));

        // Sync employee assignments, fallback to creator if none specified
        $employeeIds = $request->input('employee_ids', []);
        if (empty($employeeIds) && auth()->id()) {
            $employeeIds = [auth()->id()];
        }
        foreach ($employeeIds as $empId) {
            $assignment = PartyEmployeeMap::create([
                'party_id' => $party->id,
                'employee_id' => $empId,
                'assigned_by' => auth()->id() ?? 1,
                'assigned_at' => now(),
                'is_active' => true,
            ]);
            event(new PartyAssigned($party, $assignment, auth()->user()));
        }

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Party "' . $party->name . '" created successfully!',
                'party' => $party,
                'redirect_url' => route('admin.parties.index'),
            ]);
        }

        return redirect()->route('admin.parties.index')
            ->with('status', 'Party "' . $party->name . '" created successfully!');
    }

    /**
     * Show edit party form.
     */
    public function edit(Request $request, $id)
    {
        $party     = Customer::findOrFail($id);
        $employees = DataScope::assignableUsers()->with('roles')->orderBy('name')->get();

        // Get currently assigned employee IDs
        $assignedEmployeeIds = $party->assignments()
            ->where('is_active', true)
            ->pluck('employee_id')
            ->toArray();

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success'             => true,
                'party'               => $party,
                'assignedEmployeeIds' => $assignedEmployeeIds,
                'employees'           => $employees->map(fn($e) => [
                    'id'        => $e->id,
                    'name'      => $e->name,
                    'role_name' => $e->role_name,
                    'name_with_role' => $e->name_with_role,
                ]),
                'updateUrl'           => route('admin.parties.update', $party->id),
            ]);
        }

        return redirect()->route('admin.parties.index');
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

        $validated = $request->validated();
        if (array_key_exists('credit_days', $validated) && is_null($validated['credit_days'])) {
            $validated['credit_days'] = 0;
        }
        if (array_key_exists('credit_limit', $validated) && is_null($validated['credit_limit'])) {
            $validated['credit_limit'] = 0;
        }

        $party->update($validated);

        // If it was pending, auto-approve it upon saving
        if ($party->approval_status === 'pending') {
            $party->update([
                'approval_status' => 'approved',
                'is_active' => true,
            ]);

            // Activate their assignment to the worker who submitted it
            PartyEmployeeMap::where('party_id', $party->id)
                ->where('employee_id', $party->submitted_by)
                ->update([
                    'is_active' => true,
                    'assigned_by' => auth()->id() ?? 1,
                    'assigned_at' => now(),
                ]);
        }

        // Sync employee assignments
        $newEmployeeIds = $request->input('employee_ids', []);
        
        // Deactivate assignments that are no longer selected
        PartyEmployeeMap::where('party_id', $party->id)
            ->where('is_active', true)
            ->whereNotIn('employee_id', $newEmployeeIds)
            ->update([
                'is_active' => false,
                'unassigned_at' => now(),
            ]);

        // Get currently active employee IDs to avoid creating duplicates
        $currentEmployeeIds = PartyEmployeeMap::where('party_id', $party->id)
            ->where('is_active', true)
            ->pluck('employee_id')
            ->toArray();

        // Create new assignments for newly selected employees
        $employeesToAdd = array_diff($newEmployeeIds, $currentEmployeeIds);
        
        foreach ($employeesToAdd as $empId) {
            $assignment = PartyEmployeeMap::create([
                'party_id' => $party->id,
                'employee_id' => $empId,
                'assigned_by' => auth()->id() ?? 1,
                'assigned_at' => now(),
                'is_active' => true,
            ]);
            event(new PartyAssigned($party, $assignment, auth()->user()));
        }

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
    public function destroy(Request $request, $id)
    {
        $party = Customer::findOrFail($id);
        $this->authorize('delete', $party);

        $name = $party->name;

        DB::beginTransaction();
        try {
            PartyEmployeeMap::where('party_id', $party->id)->delete();
            $party->delete();
            DB::commit();

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => "Party \"{$name}\" deleted successfully.",
                ]);
            }

            return redirect()->route('admin.parties.index')
                ->with('status', "Party \"{$name}\" deleted successfully!");
        } catch (\Exception $e) {
            DB::rollBack();
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to delete party: ' . $e->getMessage(),
                ], 500);
            }
            return back()->withErrors(['error' => 'Failed to delete party.']);
        }
    }


    /**
     * Bulk assign parties to a field worker.
     */
    public function bulkAssign(PartyAssignRequest $request)
    {
        $this->authorize('assign', Customer::class);

        $validated = $request->validated();
        $partyIds   = $validated['party_ids'];
        $employeeId = $validated['employee_id'];
        $assignedBy = auth()->id();

        // Scope check: ensure target employee is within assignable scope
        $assignable = DataScope::assignableUsers()->pluck('id')->toArray();
        if (!empty($assignable) && !in_array($employeeId, $assignable)) {
            abort(403, 'You cannot assign parties to this employee.');
        }

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
            ['party_code', 'name', 'owner_name', 'mobile', 'email', 'address', 'district', 'area', 'city', 'state', 'pincode', 'party_type', 'category', 'credit_limit', 'credit_days', 'opening_balance', 'is_active', 'land_area', 'latitude', 'longitude', 'employee_email'],
            ['PTY-001', 'ABC Traders', 'John Doe', '9876543210', 'abc@example.com', '123 MG Road', 'Ahmedabad', 'Sanand GIDC', 'Sanand', 'Gujarat', '382110', 'Retailer', 'A', '50000', '30', '1500', '1', '500 sqft', '19.0760', '72.8777', 'employee@company.com'],
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
        $employees = DataScope::assignableUsers()->with('roles')->orderBy('name')->get();
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
        $districts = \App\Models\District::all();
        $districtByName = $districts->keyBy(fn($d) => strtolower(trim($d->name)));
        $districtByCode = $districts->whereNotNull('code')->keyBy(fn($d) => strtolower(trim($d->code)));
        $allAreas = \App\Models\Area::all();

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

                $districtInput = trim($data['district'] ?? '');
                $cityInput     = trim($data['city'] ?? '');
                $areaInput     = trim($data['area'] ?? '');

                // Match District: Check explicit district column, fallback to city matching
                $matchedDistrict = null;
                if ($districtInput !== '') {
                    $matchedDistrict = $districtByName->get(strtolower($districtInput))
                        ?? $districtByCode->get(strtolower($districtInput));
                }
                if (!$matchedDistrict && $cityInput !== '') {
                    $matchedDistrict = $districtByName->get(strtolower($cityInput));
                }

                $districtId = null;
                $areaId = null;
                $stateVal = ($data['state'] ?? '') !== '' ? $data['state'] : null;
                $pincodeVal = ($data['pincode'] ?? '') !== '' ? $data['pincode'] : null;

                if ($matchedDistrict) {
                    $districtId = $matchedDistrict->id;
                    if (empty($stateVal)) {
                        $stateVal = $matchedDistrict->state;
                    }
                    if ($areaInput !== '') {
                        $matchedArea = $allAreas->where('district_id', $matchedDistrict->id)
                            ->first(fn($a) => strtolower(trim($a->name)) === strtolower($areaInput));
                        if ($matchedArea) {
                            $areaId = $matchedArea->id;
                            if (empty($pincodeVal) && !empty($matchedArea->pincode)) {
                                $pincodeVal = $matchedArea->pincode;
                            }
                        }
                    }
                }

                $attributes = [
                    'party_code'      => ($data['party_code'] ?? '') !== '' ? $data['party_code'] : null,
                    'name'            => $name,
                    'owner_name'      => ($data['owner_name'] ?? '') !== '' ? $data['owner_name'] : null,
                    'mobile'          => ($data['mobile'] ?? '') !== '' ? $data['mobile'] : null,
                    'email'           => ($data['email'] ?? '') !== '' ? $data['email'] : null,
                    'address'         => ($data['address'] ?? '') !== '' ? $data['address'] : null,
                    'district_id'     => $districtId,
                    'area_id'         => $areaId,
                    'city'            => $cityInput !== '' ? $cityInput : ($matchedDistrict ? $matchedDistrict->name : null),
                    'state'           => $stateVal,
                    'pincode'         => $pincodeVal,
                    'party_type'      => ($data['party_type'] ?? '') !== '' ? $data['party_type'] : 'Retailer',
                    'category'        => ($data['category'] ?? '') !== '' ? $data['category'] : null,
                    'credit_limit'    => ($data['credit_limit'] ?? '') !== '' ? (float) $data['credit_limit'] : 0,
                    'credit_days'     => ($data['credit_days'] ?? '') !== '' ? (int) $data['credit_days'] : 0,
                    'opening_balance' => ($data['opening_balance'] ?? '') !== '' ? (float) $data['opening_balance'] : 0,
                    'is_active'       => ($data['is_active'] ?? '') !== '' ? (bool) $data['is_active'] : true,
                    'approval_status' => 'approved',
                    'submitted_by'    => auth()->id() ?? 1,
                    'land_area'       => ($data['land_area'] ?? '') !== '' ? $data['land_area'] : null,
                    'latitude'        => ($data['latitude'] ?? '') !== '' ? (float) $data['latitude'] : null,
                    'longitude'       => ($data['longitude'] ?? '') !== '' ? (float) $data['longitude'] : null,
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
                    $partyId = $existing->id;
                    $updated++;
                } else {
                    $newParty = Customer::create($attributes);
                    $partyId = $newParty->id;
                    $created++;
                }

                if ($userId) {
                    $currentAssignment = PartyEmployeeMap::where('party_id', $partyId)
                        ->where('is_active', true)
                        ->first();
                        
                    if (!$currentAssignment || $currentAssignment->employee_id !== $userId) {
                        PartyEmployeeMap::where('party_id', $partyId)->update(['is_active' => false]);
                        PartyEmployeeMap::create([
                            'party_id' => $partyId,
                            'employee_id' => $userId,
                            'assigned_by' => auth()->id() ?? 1,
                            'assigned_at' => now(),
                            'is_active' => true,
                        ]);
                    }
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

    /**
     * Approve a pending party submitted by a field worker.
     */
    public function approve($id)
    {
        $party = Customer::findOrFail($id);
        $this->authorize('update', $party);

        if ($party->approval_status !== 'pending') {
            return response()->json(['success' => false, 'message' => 'This party is not pending approval.'], 400);
        }

        DB::beginTransaction();
        try {
            // Update party status
            $party->update([
                'approval_status' => 'approved',
                'is_active' => true,
            ]);

            // Activate their assignment to the worker who submitted it
            PartyEmployeeMap::where('party_id', $party->id)
                ->where('employee_id', $party->submitted_by)
                ->update([
                    'is_active' => true,
                    'assigned_by' => auth()->id() ?? 1,
                    'assigned_at' => now(),
                ]);

            DB::commit();

            return response()->json(['success' => true, 'message' => 'Party approved successfully.']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Failed to approve party.'], 500);
        }
    }

    /**
     * Reject a pending party submitted by a field worker.
     */
    public function reject($id)
    {
        $party = Customer::findOrFail($id);
        $this->authorize('update', $party);

        if ($party->approval_status !== 'pending') {
            return response()->json(['success' => false, 'message' => 'This party is not pending approval.'], 400);
        }

        DB::beginTransaction();
        try {
            // Update party status
            $party->update([
                'approval_status' => 'rejected',
                'is_active' => false,
            ]);

            // Deactivate assignment
            PartyEmployeeMap::where('party_id', $party->id)
                ->where('employee_id', $party->submitted_by)
                ->update([
                    'is_active' => false,
                    'unassigned_at' => now(),
                ]);

            DB::commit();

            return response()->json(['success' => true, 'message' => 'Party rejected successfully.']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Failed to reject party.'], 500);
        }
    }
}


