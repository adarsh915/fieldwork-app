<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\DayStart;
use App\Models\ExpenseBill;
use App\Models\User;
use App\Models\Order;
use App\Models\Visit;
use App\Models\Transaction;
use App\Models\PartyEmployeeMap;
use App\Services\DataScope;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Spatie\Permission\Models\Role;

class EmployeeWebController extends Controller
{
    /**
     * Bulk sync assigned parties (customers) for a particular employee.
     * Expects: customer_ids[]
     */
    public function bulkSyncParties(Request $request, User $employee)
    {
        $this->authorize('update', $employee);

        $validated = $request->validate([
            'customer_ids' => ['nullable', 'array'],
            'customer_ids.*' => ['integer', 'distinct', 'exists:customers,id'],
        ]);

        $customerIds = array_values($validated['customer_ids'] ?? []);

        // Check if any of the selected parties are already actively assigned to another employee
        $alreadyAssigned = PartyEmployeeMap::whereIn('party_id', $customerIds)
            ->where('is_active', true)
            ->where('employee_id', '!=', $employee->id)
            ->with(['party', 'employee.roles'])
            ->get();

        if ($alreadyAssigned->isNotEmpty()) {
            $conflictNames = $alreadyAssigned->map(fn($m) => "'{$m->party?->name}' (assigned to {$m->employee?->name_with_role})")->join(', ');
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => "Cannot assign already assigned party: {$conflictNames}. A party cannot be assigned to multiple employees.",
                ], 422);
            }
            return redirect()->back()->withErrors(['customer_ids' => "Cannot assign already assigned party: {$conflictNames}"]);
        }

        $this->syncEmployeeParties($employee->id, $customerIds);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Parties assigned successfully! Total assigned: ' . count($customerIds),
                'assigned_count' => count($customerIds),
                'assigned_party_ids' => $customerIds,
            ]);
        }

        return redirect()->back()->with('status', 'Parties assigned successfully. Updated: ' . count($customerIds));
    }

    private function syncEmployeeParties($employeeId, array $newPartyIds)
    {
        $newParties = collect($newPartyIds);
        $currentParties = PartyEmployeeMap::where('employee_id', $employeeId)
            ->where('is_active', true)
            ->pluck('party_id');
        
        $toRemove = $currentParties->diff($newParties);
        $toAdd = $newParties->diff($currentParties);

        if ($toRemove->isNotEmpty()) {
            PartyEmployeeMap::where('employee_id', $employeeId)
                ->whereIn('party_id', $toRemove)
                ->where('is_active', true)
                ->update([
                    'is_active' => false,
                    'unassigned_at' => now(),
                ]);
        }

        foreach ($toAdd as $partyId) {
            PartyEmployeeMap::create([
                'party_id' => $partyId,
                'employee_id' => $employeeId,
                'assigned_by' => auth()->id() ?? 1,
                'assigned_at' => now(),
                'is_active' => true,
            ]);
        }
    }

    /**
     * Display employee listing page
     */
    public function index(Request $request)
    {
        try {
            $search = $request->get('q');
            $roleFilter = $request->get('role');
            $statusFilter = $request->get('status');

            $query = User::query()
                ->with(['roles', 'reportingManager.roles', 'parties:id,name'])
                ->orderBy('created_at', 'desc');

            // Data scoping: restrict to visible team members
            $visibleIds = DataScope::visibleUserIds('employees');
            if ($visibleIds !== null) {
                $query->whereIn('id', $visibleIds);
            }

            // Search
            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('employee_id', 'like', "%{$search}%");
                });
            }

            // Filter by role
            if ($roleFilter) {
                try {
                    $query->role($roleFilter);
                } catch (\Exception $e) {
                    // If role method fails, skip the filter
                }
            }

            // Filter by status
            if ($statusFilter !== null) {
                $query->where('is_active', $statusFilter === 'active');
            }

            $employees = $query->get();
            $roles = Role::orderBy('name')->get();
            $customers = Customer::active()->orderBy('name')->get(['id', 'name']);
            $managers = User::with('roles')->where('is_active', true)->orderBy('name')->get();

            return view('admin.employees.index', compact('employees', 'roles', 'customers', 'managers', 'search', 'roleFilter', 'statusFilter'));
        } catch (\Exception $e) {
            \Log::error('Employee index error: ' . $e->getMessage());
            return redirect()->route('admin.dashboard')->with('error', 'Unable to load employee list: ' . $e->getMessage());
        }
    }

    /**
     * Show create employee form
     */
    public function create()
    {
        $roles = Role::orderBy('name')->get();
        $customers = Customer::active()->orderBy('name')->get(['id', 'name']);
        $managers = User::with('roles')->where('is_active', true)->orderBy('name')->get();

        return view('admin.employees.create', compact('roles', 'customers', 'managers'));
    }

    /**
     * Store new employee
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
                'password' => ['required', 'min:8'],
                'phone' => ['nullable', 'string', 'max:20'],
                'employee_id' => ['nullable', 'string', 'max:50', 'unique:users,employee_id'],
                'address' => ['nullable', 'string', 'max:500'],
                'date_of_birth' => ['nullable', 'date', 'before:today'],
                'hire_date' => ['nullable', 'date'],
                'department' => ['nullable', 'string', 'max:100'],
                'position' => ['nullable', 'string', 'max:100'],
                'salary' => ['nullable', 'numeric', 'min:0'],
                'reporting_to' => ['nullable', 'exists:users,id'],
                'is_active' => ['nullable', 'boolean'],
                'role' => ['nullable', 'string', 'exists:roles,name'],
                'roles' => ['nullable'],
                'roles.*' => ['exists:roles,name'],
                'parties' => ['nullable', 'array'],
                'parties.*' => ['exists:customers,id'],
            ]);

            // Create employee
            $employee = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'phone' => $validated['phone'] ?? null,
                'employee_id' => $validated['employee_id'] ?? null,
                'address' => $validated['address'] ?? null,
                'date_of_birth' => $validated['date_of_birth'] ?? null,
                'hire_date' => $validated['hire_date'] ?? null,
                'department' => $validated['department'] ?? null,
                'position' => $validated['position'] ?? null,
                'salary' => $validated['salary'] ?? null,
                'reporting_to' => $validated['reporting_to'] ?? null,
                'is_active' => $request->boolean('is_active', true),
            ]);

            // Assign single role if provided
            $roleToAssign = [];
            if (!empty($validated['role'])) {
                $roleToAssign = [$validated['role']];
            } elseif (!empty($validated['roles'])) {
                $rolesArr = is_array($validated['roles']) ? $validated['roles'] : [$validated['roles']];
                $roleToAssign = array_slice(array_filter($rolesArr), 0, 1);
            }

            if (!empty($roleToAssign)) {
                $employee->syncRoles($roleToAssign);
            }

            if (isset($validated['parties'])) {
                $this->syncEmployeeParties($employee->id, $validated['parties']);
            } else {
                $this->syncEmployeeParties($employee->id, []);
            }

            \Log::info('Employee created successfully', [
                'employee_id' => $employee->id,
                'email' => $employee->email,
                'roles' => $validated['roles'] ?? []
            ]);

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Employee created successfully! ID: ' . ($employee->employee_id ?? '#' . $employee->id),
                    'employee_id' => $employee->id,
                    'redirect_url' => route('admin.employees.index'),
                ]);
            }

            return redirect()->route('admin.employees.index')
                ->with('status', 'Employee created successfully! ID: ' . $employee->id);

        } catch (\Illuminate\Validation\ValidationException $e) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Please correct the validation errors below.',
                    'errors' => $e->errors(),
                ], 422);
            }
            throw $e;
        } catch (\Exception $e) {
            \Log::error('Failed to create employee', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'data' => $request->except('password')
            ]);

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to create employee: ' . $e->getMessage(),
                ], 500);
            }

            return redirect()->back()
                ->withInput($request->except('password'))
                ->withErrors(['error' => 'Failed to create employee: ' . $e->getMessage()]);
        }
    }

    /**
     * AJAX endpoint returning fresh active employees / managers
     */
    public function activeDropdown(Request $request)
    {
        $scopedUserIds = DataScope::visibleUserIds('employees');
        $query = User::with('roles')->where('is_active', true)->orderBy('name');
        if ($scopedUserIds !== null) {
            $query->whereIn('id', $scopedUserIds);
        }
        if ($excludeId = $request->query('exclude_id')) {
            $query->where('id', '!=', $excludeId);
        }
        $employees = $query->get()->map(fn($u) => [
            'id'        => $u->id,
            'name'      => $u->name_with_role,
            'role_name' => $u->role_name,
        ]);

        return response()->json([
            'success'   => true,
            'employees' => $employees,
            'managers'  => $employees,
        ]);
    }

    /**
     * Show edit employee form (or return JSON for AJAX modal)
     */
    public function edit(Request $request, $id)
    {
        $employee = User::with(['roles', 'parties'])->findOrFail($id);
        $this->authorize('update', $employee);

        $roles = Role::orderBy('name')->get();
        $customers = Customer::active()->orderBy('name')->get(['id', 'name']);
        $managers = User::with('roles')->where('is_active', true)->where('id', '!=', $id)->orderBy('name')->get();
        $assignedPartyIds = $employee->parties->pluck('id')->all();

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'employee' => [
                    'id' => $employee->id,
                    'name' => $employee->name,
                    'email' => $employee->email,
                    'phone' => $employee->phone,
                    'employee_id' => $employee->employee_id,
                    'address' => $employee->address,
                    'date_of_birth' => $employee->date_of_birth ? $employee->date_of_birth->format('Y-m-d') : null,
                    'hire_date' => $employee->hire_date ? $employee->hire_date->format('Y-m-d') : null,
                    'department' => $employee->department,
                    'position' => $employee->position,
                    'salary' => $employee->salary,
                    'reporting_to' => $employee->reporting_to,
                    'is_active' => (bool)$employee->is_active,
                    'roles' => $employee->roles->pluck('name')->all(),
                    'parties' => $assignedPartyIds,
                ],
                'managers' => $managers->map(fn($m) => [
                    'id'        => $m->id,
                    'name'      => $m->name_with_role,
                    'role_name' => $m->role_name,
                ]),
                'assigned_party_ids' => $assignedPartyIds,
            ]);
        }

        return view('admin.employees.edit', compact('employee', 'roles', 'customers', 'managers', 'assignedPartyIds'));
    }

    /**
     * Update employee
     */
    public function update(Request $request, $id)
    {
        try {
            $employee = User::findOrFail($id);
            $this->authorize('update', $employee);

            $validated = $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($employee->id)],
                'password' => ['nullable', Password::defaults()],
                'phone' => ['nullable', 'string', 'max:20'],
                'employee_id' => ['nullable', 'string', 'max:50', Rule::unique('users')->ignore($employee->id)],
                'address' => ['nullable', 'string', 'max:500'],
                'date_of_birth' => ['nullable', 'date', 'before:today'],
                'hire_date' => ['nullable', 'date'],
                'department' => ['nullable', 'string', 'max:100'],
                'position' => ['nullable', 'string', 'max:100'],
                'salary' => ['nullable', 'numeric', 'min:0'],
                'reporting_to' => ['nullable', 'exists:users,id'],
                'is_active' => ['nullable', 'boolean'],
                'role' => ['nullable', 'string', 'exists:roles,name'],
                'roles' => ['nullable'],
                'roles.*' => ['exists:roles,name'],
                'parties' => ['nullable', 'array'],
                'parties.*' => ['exists:customers,id'],
            ]);

            $updateData = [
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'] ?? null,
                'employee_id' => $validated['employee_id'] ?? null,
                'address' => $validated['address'] ?? null,
                'date_of_birth' => $validated['date_of_birth'] ?? null,
                'hire_date' => $validated['hire_date'] ?? null,
                'department' => $validated['department'] ?? null,
                'position' => $validated['position'] ?? null,
                'salary' => $validated['salary'] ?? null,
                'reporting_to' => $validated['reporting_to'] ?? null,
                'is_active' => $request->boolean('is_active', true),
            ];

            if (!empty($validated['password'])) {
                $updateData['password'] = Hash::make($validated['password']);
            }

            $employee->update($updateData);

            if ($request->has('role') || $request->has('roles')) {
                $roleToSync = [];
                if (!empty($validated['role'])) {
                    $roleToSync = [$validated['role']];
                } elseif (!empty($validated['roles'])) {
                    $rolesArr = is_array($validated['roles']) ? $validated['roles'] : [$validated['roles']];
                    $roleToSync = array_slice(array_filter($rolesArr), 0, 1);
                }
                $employee->syncRoles($roleToSync);
            }

            if (isset($validated['parties'])) {
                $this->syncEmployeeParties($employee->id, $validated['parties']);
            }

            if ($request->ajax() || $request->wantsJson()) {
                $employee->load(['roles', 'parties', 'reportingManager.roles']);
                return response()->json([
                    'success' => true,
                    'message' => 'Employee updated successfully!',
                    'employee' => [
                        'id' => $employee->id,
                        'name' => $employee->name,
                        'email' => $employee->email,
                        'phone' => $employee->phone,
                        'employee_id' => $employee->employee_id,
                        'position' => $employee->position,
                        'department' => $employee->department,
                        'is_active' => (bool)$employee->is_active,
                        'roles' => $employee->roles->pluck('name')->all(),
                        'reporting_to' => $employee->reporting_to,
                        'reporting_to_name' => $employee->reportingManager?->name_with_role ?? '—',
                        'parties_count' => $employee->parties->count(),
                        'assigned_party_ids' => $employee->parties->pluck('id')->all(),
                    ],
                    'redirect_url' => route('admin.employees.index'),
                ]);
            }

            return redirect()->route('admin.employees.index')
                ->with('status', 'Employee updated successfully!');

        } catch (\Illuminate\Validation\ValidationException $e) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Please correct the validation errors below.',
                    'errors' => $e->errors(),
                ], 422);
            }
            throw $e;
        } catch (\Exception $e) {
            \Log::error('Failed to update employee', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to update employee: ' . $e->getMessage(),
                ], 500);
            }

            return redirect()->back()
                ->withInput($request->except('password'))
                ->withErrors(['error' => 'Failed to update employee: ' . $e->getMessage()]);
        }
    }

    /**
     * Delete employee
     */
    public function destroy(Request $request, $id)
    {
        $employee = User::findOrFail($id);
        $this->authorize('delete', $employee);

        if (auth()->id() === $employee->id) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'You cannot delete your own account.'], 422);
            }
            return back()->withErrors(['error' => 'You cannot delete your own account']);
        }

        $name = $employee->name;
        $employee->delete();

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Employee '{$name}' deleted successfully!",
            ]);
        }

        return redirect()->route('admin.employees.index')
            ->with('status', 'Employee deleted successfully!');
    }

    /**
     * Toggle employee status
     */
    public function toggleStatus(Request $request, $id)
    {
        $employee = User::findOrFail($id);
        $this->authorize('update', $employee);

        $employee->update(['is_active' => !$employee->is_active]);

        $statusStr = $employee->is_active ? 'activated' : 'deactivated';

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'is_active' => (bool)$employee->is_active,
                'status_label' => $employee->is_active ? 'Active' : 'Inactive',
                'message' => "Employee '{$employee->name}' {$statusStr} successfully!",
            ]);
        }

        return back()->with('status', 'Employee status updated successfully!');
    }

    /**
     * Show a particular employee's assigned parties (customers) list.
     */
    public function showParties(Request $request, $id)
    {
        $employee = User::with(['roles', 'parties'])->findOrFail($id);
        $this->authorize('view', $employee);

        if ($request->ajax() || $request->wantsJson()) {
            $customers = Customer::active()
                ->with(['activeAssignment.employee.roles'])
                ->orderBy('name')
                ->get();

            $partiesList = $customers->map(function ($c) use ($employee) {
                $activeAssignment = $c->activeAssignment;
                $assignedEmp = $activeAssignment?->employee;
                $isAssignedToThis = $assignedEmp && (int)$assignedEmp->id === (int)$employee->id;
                $isAssignedToOther = $assignedEmp && (int)$assignedEmp->id !== (int)$employee->id;

                return [
                    'id' => $c->id,
                    'name' => $c->name,
                    'city' => $c->city ?? '',
                    'is_assigned_to_this' => $isAssignedToThis,
                    'is_assigned_to_other' => $isAssignedToOther,
                    'assigned_to_name' => $isAssignedToOther ? $assignedEmp->name_with_role : null,
                ];
            });

            return response()->json([
                'success' => true,
                'employee' => [
                    'id' => $employee->id,
                    'name' => $employee->name_with_role,
                ],
                'assigned_party_ids' => $employee->parties->pluck('id')->all(),
                'parties' => $partiesList,
            ]);
        }

        $search = $request->input('q');

        $query = $employee->parties()->with('submittedBy')->orderBy('name');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name',    'like', "%{$search}%")
                  ->orWhere('mobile', 'like', "%{$search}%")
                  ->orWhere('email',  'like', "%{$search}%");
            });
        }

        $parties = $query->paginate(20)->withQueryString();

        return view('admin.employees.parties', compact('employee', 'parties', 'search'));
    }

    /**
     * Show comprehensive fieldworker profile with all metrics and activity.
     */
    public function showProfile($id)
    {
        $employee = User::with(['roles', 'permissions'])->findOrFail($id);
        $this->authorize('view', $employee);

        // Get assigned parties with their latest transactions and visits
        $assignedParties = Customer::query()
            ->whereHas('activeAssignment', fn($q) => $q->where('employee_id', $id))
            ->with(['activeAssignment', 'transactions' => fn($q) => $q->latest()->limit(5)])
            ->withCount(['orders', 'visits', 'transactions'])
            ->get();

        // Overall statistics
        $stats = [
            'total_parties' => $assignedParties->count(),
            'active_parties' => $assignedParties->where('is_active', true)->count(),
            'total_orders' => Order::whereIn('party_id', $assignedParties->pluck('id'))->count(),
            'total_visits' => Visit::where('user_id', $id)->count(),
            'total_revenue' => Order::whereIn('party_id', $assignedParties->pluck('id'))
                ->where('status', '!=', 'cancelled')
                ->sum('total_amount'),
            'pending_payments' => Transaction::whereIn('party_id', $assignedParties->pluck('id'))
                ->where('type', 'debit')
                ->where('status', 'pending')
                ->sum('amount'),
        ];

        // Recent activities (last 30 days)
        $recentOrders = Order::whereIn('party_id', $assignedParties->pluck('id'))
            ->with('party')
            ->latest()
            ->limit(10)
            ->get();

        $recentVisits = Visit::where('user_id', $id)
            ->with('party')
            ->latest()
            ->limit(10)
            ->get();

        $recentTransactions = Transaction::whereIn('party_id', $assignedParties->pluck('id'))
            ->with('customer')
            ->latest()
            ->limit(10)
            ->get();

        // Upcoming visits (scheduled visits)
        $upcomingVisits = Visit::where('user_id', $id)
            ->where('check_in_time', '>=', now())
            ->where('status', 'scheduled')
            ->with('party')
            ->orderBy('check_in_time')
            ->limit(10)
            ->get();

        // Top performing parties (by revenue)
        $topParties = $assignedParties->map(function($party) {
            $party->total_revenue = Order::where('party_id', $party->id)
                ->where('status', '!=', 'cancelled')
                ->sum('total_amount');
            return $party;
        })->sortByDesc('total_revenue')->take(5);

        // Monthly performance (last 6 months)
        $monthlyData = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = now()->subMonths($i);
            $monthlyData[] = [
                'month' => $month->format('M Y'),
                'orders' => Order::whereIn('party_id', $assignedParties->pluck('id'))
                    ->whereYear('created_at', $month->year)
                    ->whereMonth('created_at', $month->month)
                    ->count(),
                'revenue' => Order::whereIn('party_id', $assignedParties->pluck('id'))
                    ->whereYear('created_at', $month->year)
                    ->whereMonth('created_at', $month->month)
                    ->where('status', '!=', 'cancelled')
                    ->sum('total_amount'),
                'visits' => Visit::where('user_id', $id)
                    ->whereYear('check_in_time', $month->year)
                    ->whereMonth('check_in_time', $month->month)
                    ->count(),
            ];
        }

        // Product-wise sales
        $productSales = Order::whereIn('party_id', $assignedParties->pluck('id'))
            ->where('status', '!=', 'cancelled')
            ->with('items.product')
            ->get()
            ->flatMap->items
            ->groupBy('product_id')
            ->map(function($items) {
                return [
                    'product' => $items->first()->product,
                    'quantity' => $items->sum('quantity'),
                    'revenue' => $items->sum('total'),
                    'orders_count' => $items->count(),
                ];
            })
            ->sortByDesc('revenue')
            ->take(10);

        // Daily tracking: day start records with vehicle, visits, expenses (last 30 days)
        $dailyTracking = DayStart::where('user_id', $id)
            ->where('date', '>=', now()->subDays(30))
            ->with(['vehicleType', 'tourPurpose', 'tourType', 'places', 'parties.party'])
            ->orderByDesc('date')
            ->get();

        // Visits grouped by day_start_id for linking to each day
        $visitsByDayStart = Visit::where('user_id', $id)
            ->whereIn('day_start_id', $dailyTracking->pluck('id'))
            ->with('party')
            ->get()
            ->groupBy('day_start_id');

        // Expenses grouped by date
        $expensesByDate = ExpenseBill::where('user_id', $id)
            ->where('date', '>=', now()->subDays(30))
            ->orderByDesc('date')
            ->get()
            ->groupBy(fn($e) => $e->date->format('Y-m-d'));

        return view('admin.employees.profile', compact(
            'employee',
            'assignedParties',
            'stats',
            'recentOrders',
            'recentVisits',
            'recentTransactions',
            'upcomingVisits',
            'topParties',
            'monthlyData',
            'productSales',
            'dailyTracking',
            'visitsByDayStart',
            'expensesByDate'
        ));
    }
}
