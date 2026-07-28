<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Visit;
use App\Models\Transaction;
use App\Models\PartyEmployeeMap;
use App\Models\AuditLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class TestPartyModule extends Command
{
    protected $signature = 'test:party-module';
    protected $description = 'Test all Party/Customer module database connections and functionality';

    public function handle()
    {
        $this->info('🧪 Testing Party/Customer Module...');
        $this->newLine();

        // Test 1: Database Connection
        $this->testDatabaseConnection();

        // Test 2: Tables Existence
        $this->testTablesExist();

        // Test 3: Model Connections
        $this->testModelConnections();

        // Test 4: Relationships
        $this->testRelationships();

        // Test 5: Party CRUD Operations
        $this->testPartyCRUD();

        // Test 6: Party Assignment
        $this->testPartyAssignment();

        // Test 7: Controllers & Routes
        $this->testControllersAndRoutes();

        $this->newLine();
        $this->info('✅ All tests completed!');
    }

    protected function testDatabaseConnection()
    {
        $this->info('📊 Testing Database Connection...');
        
        try {
            DB::connection()->getPdo();
            $dbName = DB::connection()->getDatabaseName();
            $this->line("   ✓ Connected to database: {$dbName}");
        } catch (\Exception $e) {
            $this->error("   ✗ Database connection failed: " . $e->getMessage());
            return;
        }
    }

    protected function testTablesExist()
    {
        $this->info('📋 Testing Required Tables...');
        
        $tables = [
            'customers' => 'Parties/Customers table',
            'users' => 'Users/Employees table',
            'orders' => 'Orders table',
            'visits' => 'Visits table',
            'transactions' => 'Transactions table',
            'party_employee_map' => 'Party Assignment History table',
            'audit_logs' => 'Audit Logs table',
        ];

        foreach ($tables as $table => $description) {
            if (Schema::hasTable($table)) {
                $count = DB::table($table)->count();
                $this->line("   ✓ {$description}: {$count} records");
            } else {
                $this->error("   ✗ {$description}: NOT FOUND");
            }
        }
    }

    protected function testModelConnections()
    {
        $this->info('🔗 Testing Model Connections...');
        
        try {
            $customerCount = Customer::count();
            $this->line("   ✓ Customer model: {$customerCount} records");
        } catch (\Exception $e) {
            $this->error("   ✗ Customer model failed: " . $e->getMessage());
        }

        try {
            $userCount = User::count();
            $this->line("   ✓ User model: {$userCount} records");
        } catch (\Exception $e) {
            $this->error("   ✗ User model failed: " . $e->getMessage());
        }

        try {
            $orderCount = Order::count();
            $this->line("   ✓ Order model: {$orderCount} records");
        } catch (\Exception $e) {
            $this->error("   ✗ Order model failed: " . $e->getMessage());
        }

        try {
            $visitCount = Visit::count();
            $this->line("   ✓ Visit model: {$visitCount} records");
        } catch (\Exception $e) {
            $this->error("   ✗ Visit model failed: " . $e->getMessage());
        }

        try {
            $transactionCount = Transaction::count();
            $this->line("   ✓ Transaction model: {$transactionCount} records");
        } catch (\Exception $e) {
            $this->error("   ✗ Transaction model failed: " . $e->getMessage());
        }

        try {
            $assignmentCount = PartyEmployeeMap::count();
            $this->line("   ✓ PartyEmployeeMap model: {$assignmentCount} records");
        } catch (\Exception $e) {
            $this->error("   ✗ PartyEmployeeMap model failed: " . $e->getMessage());
        }
    }

    protected function testRelationships()
    {
        $this->info('🔄 Testing Model Relationships...');

        // Test Customer relationships
        $customer = Customer::first();
        if ($customer) {
            try {
                // Test transactions relationship
                $transactions = $customer->transactions;
                $this->line("   ✓ Customer->transactions: " . $transactions->count() . " records");
            } catch (\Exception $e) {
                $this->error("   ✗ Customer->transactions failed: " . $e->getMessage());
            }

            try {
                // Test orders relationship
                $orders = $customer->orders;
                $this->line("   ✓ Customer->orders: " . $orders->count() . " records");
            } catch (\Exception $e) {
                $this->error("   ✗ Customer->orders failed: " . $e->getMessage());
            }

            try {
                // Test visits relationship
                $visits = $customer->visits;
                $this->line("   ✓ Customer->visits: " . $visits->count() . " records");
            } catch (\Exception $e) {
                $this->error("   ✗ Customer->visits failed: " . $e->getMessage());
            }

            try {
                // Test activeAssignment relationship
                $activeAssignment = $customer->activeAssignment;
                $this->line("   ✓ Customer->activeAssignment: " . ($activeAssignment ? 'Yes' : 'No'));
            } catch (\Exception $e) {
                $this->error("   ✗ Customer->activeAssignment failed: " . $e->getMessage());
            }

            try {
                // Test assignments relationship
                $assignments = $customer->assignments;
                $this->line("   ✓ Customer->assignments: " . $assignments->count() . " records");
            } catch (\Exception $e) {
                $this->error("   ✗ Customer->assignments failed: " . $e->getMessage());
            }
        } else {
            $this->warn("   ⚠ No customers found to test relationships");
        }

        // Test User relationships
        $user = User::first();
        if ($user) {
            try {
                $parties = $user->parties ?? collect();
                $this->line("   ✓ User->parties: " . $parties->count() . " records");
            } catch (\Exception $e) {
                $this->error("   ✗ User->parties failed: " . $e->getMessage());
            }
        }
    }

    protected function testPartyCRUD()
    {
        $this->info('➕ Testing Party CRUD Operations...');

        // Create
        try {
            $testParty = Customer::create([
                'party_code' => 'TEST' . time(),
                'name' => 'Test Party ' . time(),
                'owner_name' => 'Test Owner',
                'mobile' => '9999999999',
                'email' => 'test@example.com',
                'address' => 'Test Address',
                'city' => 'Test City',
                'state' => 'Test State',
                'pincode' => '123456',
                'party_type' => 'Retailer',
                'category' => 'A',
                'credit_limit' => 50000,
                'credit_days' => 30,
                'opening_balance' => 0,
                'is_active' => true,
            ]);
            $this->line("   ✓ CREATE: Party created with ID {$testParty->id}");

            // Read
            $foundParty = Customer::find($testParty->id);
            if ($foundParty) {
                $this->line("   ✓ READ: Party found - {$foundParty->name}");
            }

            // Update
            $foundParty->update(['name' => 'Updated Test Party']);
            $this->line("   ✓ UPDATE: Party updated");

            // Delete
            $foundParty->delete();
            $this->line("   ✓ DELETE: Party deleted");

        } catch (\Exception $e) {
            $this->error("   ✗ CRUD operations failed: " . $e->getMessage());
        }
    }

    protected function testPartyAssignment()
    {
        $this->info('👥 Testing Party Assignment...');

        $customer = Customer::first();
        $user = User::first();

        if ($customer && $user) {
            try {
                // Create assignment
                $assignment = PartyEmployeeMap::create([
                    'party_id' => $customer->id,
                    'employee_id' => $user->id,
                    'assigned_by' => $user->id,
                    'assigned_at' => now(),
                    'is_active' => true,
                ]);
                $this->line("   ✓ Assignment created: Party #{$customer->id} → Employee #{$user->id}");

                // Test assignment retrieval
                $retrieved = PartyEmployeeMap::where('party_id', $customer->id)
                    ->where('is_active', true)
                    ->first();
                
                if ($retrieved) {
                    $this->line("   ✓ Assignment retrieved successfully");
                }

                // Test employee relationship
                if ($retrieved->employee) {
                    $this->line("   ✓ Assignment->employee relationship works");
                }

                // Clean up
                $assignment->delete();
                $this->line("   ✓ Test assignment cleaned up");

            } catch (\Exception $e) {
                $this->error("   ✗ Party assignment failed: " . $e->getMessage());
            }
        } else {
            $this->warn("   ⚠ No parties or users found to test assignment");
        }
    }

    protected function testControllersAndRoutes()
    {
        $this->info('🛣️  Testing Controllers & Routes...');

        // Check if routes are registered
        $routes = [
            'admin.parties.index' => 'Party list page',
            'admin.parties.data' => 'Party DataTables endpoint',
            'admin.parties.show' => 'Party details page',
            'admin.parties.bulk-assign' => 'Bulk assignment endpoint',
            'admin.parties.assignment-history' => 'Assignment history endpoint',
            'admin.employees.profile' => 'Employee profile page',
        ];

        foreach ($routes as $routeName => $description) {
            if (\Route::has($routeName)) {
                $this->line("   ✓ {$description}: route exists");
            } else {
                $this->error("   ✗ {$description}: route NOT FOUND");
            }
        }

        // Check controller existence
        $controllers = [
            'App\Http\Controllers\Admin\PartyController',
            'App\Http\Controllers\Admin\EmployeeWebController',
        ];

        foreach ($controllers as $controller) {
            if (class_exists($controller)) {
                $this->line("   ✓ {$controller}: exists");
            } else {
                $this->error("   ✗ {$controller}: NOT FOUND");
            }
        }
    }
}
