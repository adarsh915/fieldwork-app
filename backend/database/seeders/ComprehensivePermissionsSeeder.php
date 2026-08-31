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

            // User Management
            'users.view',
            'users.create',
            'users.edit',
            'users.delete',
            'users.manage',

            // Role & Permission Management
            'roles.view',
            'roles.create',
            'roles.edit',
            'roles.delete',
            'roles.manage',
            'permissions.create',
            'permissions.delete',

            // Employee Management
            'employees.view',
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

            // Transaction Management
            'transactions.view',
            'transactions.create',
            'transactions.edit',
            'transactions.delete',
            'transactions.manage',

            // Orders Management
            'orders.view',
            'orders.create',
            'orders.edit',
            'orders.delete',
            'orders.manage',

            // Payments Management
            'payments.view',
            'payments.create',
            'payments.edit',
            'payments.delete',
            'payments.manage',

            // Visits Management
            'visits.view',
            'visits.create',
            'visits.edit',
            'visits.delete',
            'visits.manage',

            // Dealers Management
            'dealers.view',
            'dealers.create',
            'dealers.edit',
            'dealers.delete',
            'dealers.manage',

            // Day Start
            'day-start.view',
            'day-start.manage',

            // Activities
            'activities.view',
            'activities.create',
            'activities.edit',
            'activities.delete',
            'activities.manage',

            // Reports
            'reports.view',
            'reports.create',
            'reports.edit',
            'reports.delete',
            'reports.manage',

            // Expenses
            'expenses.view',
            'expenses.create',
            'expenses.edit',
            'expenses.delete',
            'expenses.manage',

            // Leave Management
            'leave.view',
            'leave.create',
            'leave.edit',
            'leave.delete',
            'leave.manage',
            'leave.approve',
            'leave.reject',

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

        // Update Manager - Give most permissions except role/permission management
        $manager = Role::where('name', 'Manager')->first();
        if ($manager) {
            $managerPermissions = Permission::where('name', 'not like', 'roles.%')
                ->where('name', 'not like', 'permissions.%')
                ->get();
            $manager->syncPermissions($managerPermissions);
            $this->command->info('✅ Manager: ' . $managerPermissions->count() . ' permissions');
        }

        // Update Finance - Give financial related permissions
        $finance = Role::where('name', 'Finance')->first();
        if ($finance) {
            $finance->syncPermissions([
                'dashboard.view',
                'customers.view',
                'transactions.view',
                'transactions.create',
                'transactions.edit',
                'transactions.manage',
                'payments.view',
                'payments.create',
                'payments.edit',
                'payments.manage',
                'expenses.view',
                'expenses.manage',
                'reports.view',
            ]);
            $this->command->info('✅ Finance: Financial permissions');
        }

        // Update Employee - Give basic viewing permissions
        $employee = Role::where('name', 'Employee')->first();
        if ($employee) {
            $employee->syncPermissions([
                'dashboard.view',
                'customers.view',
                'transactions.view',
                'orders.view',
                'visits.view',
                'activities.view',
                'reports.view',
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
                'visits.view',
                'visits.create',
                'visits.edit',
                'visits.manage',
                'day-start.view',
                'day-start.manage',
                'activities.view',
                'activities.create',
                'expenses.view',
                'expenses.create',
                'leave.view',
                'leave.create',
                'meetings.view',
                'product-demo.view',
                'product-demo.create',
                'deals.view',
                'deals.create',
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
