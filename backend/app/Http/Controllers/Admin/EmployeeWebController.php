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
        $validated = $request->validate([
            'customer_ids' => ['required', 'array'],
            'customer_ids.*' => ['integer', 'distinct', 'exists:customers,id'],
        ]);

        $customerIds = array_values($validated['customer_ids']);

        $this->syncEmployeeParties($employee->id, $customerIds);

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
                ->with('roles')
                ->orderBy('created_at', 'desc');

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

            return view('admin.employees.index', compact('employees', 'roles', 'search', 'roleFilter', 'statusFilter'));
        } catch (\Exception $e) {
            // Log the error and show a friendly message
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

        return view('admin.employees.create', compact('roles', 'customers'));
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
                'is_active' => ['nullable', 'boolean'],
                'roles' => ['nullable', 'array'],
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
                'is_active' => $request->boolean('is_active', true),
            ]);

            // Assign roles if provided
            if (isset($validated['roles']) && !empty($validated['roles'])) {
                $employee->assignRole($validated['roles']);
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

            return redirect()->route('admin.employees.index')
                ->with('status', 'Employee created successfully! ID: ' . $employee->id);

        } catch (\Illuminate\Validation\ValidationException $e) {
            // Validation errors - Laravel will handle this automatically
            throw $e;
        } catch (\Exception $e) {
            \Log::error('Failed to create employee', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'data' => $request->except('password')
            ]);

            return redirect()->back()
                ->withInput($request->except('password'))
                ->withErrors(['error' => 'Failed to create employee: ' . $e->getMessage()]);
        }
    }

    /**
     * Show edit employee form
     */
    public function edit($id)
    {
        $employee = User::with(['roles', 'parties'])->findOrFail($id);
        $roles = Role::orderBy('name')->get();
        $customers = Customer::active()->orderBy('name')->get(['id', 'name']);
        $assignedPartyIds = $employee->parties->pluck('id')->all();

        return view('admin.employees.edit', compact('employee', 'roles', 'customers', 'assignedPartyIds'));
    }

    /**
     * Update employee
     */
    public function update(Request $request, $id)
    {
        $employee = User::findOrFail($id);

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
            'is_active' => ['boolean'],
            'roles' => ['nullable', 'array'],
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
            'is_active' => $request->boolean('is_active', true),
        ];

        if (!empty($validated['password'])) {
            $updateData['password'] = Hash::make($validated['password']);
        }

        $employee->update($updateData);

        if (isset($validated['roles'])) {
            $employee->syncRoles($validated['roles']);
        }

        $this->syncEmployeeParties($employee->id, $request->input('parties', []));

        return redirect()->route('admin.employees.index')
            ->with('status', 'Employee updated successfully!');
    }

    /**
     * Delete employee
     */
    public function destroy($id)
    {
        $employee = User::findOrFail($id);

        if (auth()->id() === $employee->id) {
            return back()->withErrors(['error' => 'You cannot delete your own account']);
        }

        $employee->delete();

        return redirect()->route('admin.employees.index')
            ->with('status', 'Employee deleted successfully!');
    }

    /**
     * Toggle employee status
     */
    public function toggleStatus($id)
    {
        $employee = User::findOrFail($id);
        $employee->update(['is_active' => !$employee->is_active]);

        return back()->with('status', 'Employee status updated successfully!');
    }

    /**
     * Show a particular employee's assigned parties (customers) list.
     */
    public function showParties(Request $request, $id)
    {
        $employee = User::with('roles')->findOrFail($id);

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
