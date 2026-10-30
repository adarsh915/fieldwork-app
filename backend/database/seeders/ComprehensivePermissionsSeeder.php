<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class ComprehensivePermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $this->command->info('Creating comprehensive permissions...');

        // Define all permissions
        $permissions = [
            // Dashboard
            'dashboard.view',

            // Role Management
            'roles.view',
            'roles.create',
            'roles.edit',
            'roles.delete',
            'roles.manage',

            // Employee Management
            'employees.view',
            'employees.view-own',
            'employees.view-team',
            'employees.view-all',
            'employees.create',
            'employees.edit',
            'employees.delete',
            'employees.manage',
            'employees.assign-roles',

            // Customer Management (Parties)
            'customers.view',
            'customers.create',
            'customers.edit',
            'customers.delete',
            'customers.manage',
            'parties.assign',
            'parties.view',
            'parties.view-own',
            'parties.view-team',
            'parties.view-all',

            // Transaction Management
            'transactions.view',

            // Orders Management
            'orders.view',
            'orders.view-own',
            'orders.view-team',
            'orders.view-all',
            'orders.create',
            'orders.manage',

            // Payments Management
            'payments.view',
            'payments.view-own',
            'payments.view-team',
            'payments.view-all',

            // Visits Management
            'visits.view',
            'visits.view-own',
            'visits.view-team',
            'visits.view-all',
            'visits.create',
            'visits.manage',

            // Dealers Management
            'dealers.view',
            'dealers.create',
            'dealers.edit',
            'dealers.delete',
            'dealers.manage',

            // Day Start
            'day-start.view',
            'day-start.view-own',
            'day-start.view-team',
            'day-start.view-all',
            'day-start.create',
            'day-start.approve',
            'day-start.manage',

            // Master Data
            'master-data.manage',

            // Locations (Districts & Areas)
            'locations.view',
            'locations.manage',

            // Activities
            'activities.view',

            // Reports
            'reports.view',
            'reports.view-own',
            'reports.view-team',
            'reports.view-all',

            // Expenses
            'expenses.view',
            'expenses.view-own',
            'expenses.view-team',
            'expenses.view-all',
            'expenses.create',
            'expenses.edit',
            'expenses.delete',
            'expenses.manage',
            'expenses.approve',
            'expenses.final_approve',

            // Fuel Rates
            'fuel-rates.view',
            'fuel-rates.manage',

            // Leave Management
            'leaves.view',
            'leaves.view-own',
            'leaves.view-team',
            'leaves.view-all',
            'leaves.manage',
            'leaves.approve',
            'leaves.reject',

            // Meetings
            'meetings.view',
            'meetings.create',
            'meetings.edit',
            'meetings.delete',
            'meetings.manage',

            // Product Demo
            'product-demo.view',
            'product-demo.create',
            'product-demo.edit',
            'product-demo.delete',
            'product-demo.manage',

            // New Deal
            'deals.view',
            'deals.create',
            'deals.edit',
            'deals.delete',
            'deals.manage',

            // Product Management
            'products.view',
            'products.create',
            'products.edit',
            'products.delete',
            'products.manage',

            // Attendance
            'attendance.view',
            'attendance.view-own',
            'attendance.view-team',
            'attendance.view-all',
            'attendance.manage',

            // Audit Logs
            'audit-logs.view',

            // Settings
            'settings.manage',
        ];

        // Create all permissions
        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web'
            ]);
        }

        $this->command->info("Created " . count($permissions) . " permissions!");

        // Update Super Admin - Give ALL permissions
        $superAdmin = Role::where('name', 'Super Admin')->first();
        if ($superAdmin) {
            $superAdmin->syncPermissions(Permission::all());
            $this->command->info('✅ Super Admin: ALL permissions');
        }

        // Update Admin - Give ALL permissions
        $admin = Role::where('name', 'Admin')->first();
        if ($admin) {
            $admin->syncPermissions(Permission::all());
            $this->command->info('✅ Admin: ALL permissions');
        }

        // Update Manager - Give permissions for operations scoped to team, excluding view-all, role admin, and assign-roles
        $manager = Role::where('name', 'Manager')->first();
        if ($manager) {
            $managerPermissions = Permission::where('name', 'not like', 'roles.%')
                ->where('name', 'not like', 'permissions.%')
                ->where('name', 'not like', 'settings.manage')
                ->where('name', '!=', 'employees.assign-roles')
                ->where('name', 'not like', '%.view-all')
                ->where('name', '!=', 'expenses.final_approve')
                ->get();
            $manager->syncPermissions($managerPermissions);
            // Ensure team-scope permissions
            $manager->givePermissionTo([
                'parties.view-team', 'parties.assign', 'employees.view-team',
                'attendance.view-team', 'orders.view-team', 'payments.view-team',
                'visits.view-team', 'leaves.view-team', 'expenses.view-team',
                'day-start.view-team', 'reports.view-team', 'expenses.approve'
            ]);
            $this->command->info('✅ Manager: ' . $manager->fresh()->permissions->count() . ' permissions');
        }

        // Update VP (if exists) - Team scope across entire reporting tree
        $vp = Role::where('name', 'VP')->first();
        if ($vp) {
            $vpPermissions = Permission::where('name', 'not like', '%.view-all')
                ->where('name', '!=', 'employees.assign-roles')
                ->get();
            $vp->syncPermissions($vpPermissions);
            $vp->givePermissionTo([
                'parties.view-team', 'parties.assign', 'employees.view-team',
                'attendance.view-team', 'orders.view-team', 'payments.view-team',
                'visits.view-team', 'leaves.view-team', 'expenses.view-team',
                'day-start.view-team', 'reports.view-team', 'expenses.approve', 'expenses.final_approve'
            ]);
            $this->command->info('✅ VP: ' . $vp->fresh()->permissions->count() . ' permissions');
        }

        // Update Finance - Give financial related permissions
        $finance = Role::where('name', 'Finance')->first();
        if ($finance) {
            $finance->syncPermissions([
                'dashboard.view',
                'customers.view',
                'transactions.view',
                'payments.view',
                'expenses.view',
                'expenses.manage',
                'expenses.final_approve',
                'fuel-rates.view',
                'fuel-rates.manage',
                'reports.view',
                'reports.view-all',
            ]);
            $this->command->info('✅ Finance: Financial permissions');
        }

        // Update HR - Give HR related permissions including company-wide expense final approval
        $hr = Role::firstOrCreate(['name' => 'HR', 'guard_name' => 'web']);
        if ($hr) {
            $hr->syncPermissions([
                'dashboard.view',
                'employees.view',
                'employees.view-all',
                'attendance.view',
                'attendance.view-all',
                'day-start.view',
                'day-start.view-all',
                'leaves.view',
                'leaves.view-all',
                'leaves.approve',
                'leaves.reject',
                'leaves.manage',
                'expenses.view',
                'expenses.view-all',
                'expenses.final_approve',
                'expenses.manage',
                'reports.view',
                'reports.view-all',
            ]);
            $this->command->info('✅ HR: HR & Final Approval permissions');
        }

        // Update Employee - Give basic viewing permissions
        $employee = Role::where('name', 'Employee')->first();
        if ($employee) {
            $employee->syncPermissions([
                'dashboard.view',
                'customers.view',
                'transactions.view',
                'orders.view',
                'orders.view-own',
                'visits.view',
                'visits.view-own',
                'activities.view',
                'reports.view',
                'reports.view-own',
                'attendance.view',
                'attendance.view-own',
                'products.view',
                'expenses.view',
                'expenses.view-own',
                'leaves.view',
                'leaves.view-own',
                'day-start.view',
                'day-start.view-own',
                'employees.view',
                'employees.view-own',
            ]);
            $this->command->info('✅ Employee: View permissions');
        }

        // Update Field Worker - Give field-related permissions
        $fieldWorker = Role::where('name', 'Field Worker')->first();
        if ($fieldWorker) {
            $fieldWorker->syncPermissions([
                'dashboard.view',
                'customers.view',
                'customers.create',
                'customers.edit',
                'parties.view',
                'parties.view-own',
                'visits.view',
                'visits.create',
                'visits.manage',
                'orders.view',
                'orders.view-own',
                'orders.create',
                'day-start.view',
                'day-start.view-own',
                'day-start.create',
                'activities.view',
                'expenses.view',
                'expenses.view-own',
                'expenses.create',
                'leaves.view',
                'leaves.view-own',
                'meetings.view',
                'product-demo.view',
                'product-demo.create',
                'deals.view',
                'deals.create',
                'products.view',
                'attendance.view',
                'attendance.view-own',
                'employees.view',
                'employees.view-own',
                'reports.view-own',
            ]);
            $this->command->info('✅ Field Worker: Field operation permissions');
        } else {
            $this->command->warn('⚠️  Field Worker role not found. Please run RolesAndPermissionsSeeder first.');
        }

        $this->command->info('');
        $this->command->info('🎉 All permissions and roles updated successfully!');
        $this->command->info('Total permissions: ' . Permission::count());
    }
}
