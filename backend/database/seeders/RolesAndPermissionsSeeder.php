<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions
        $permissions = [
            'users.view',
            'users.manage',
            'roles.view',
            'roles.manage',
            'employees.view',
            'employees.create',
            'employees.edit',
            'employees.delete',
            'employees.manage',
            'customers.view',
            'customers.manage',
            'transactions.view',
            'transactions.manage',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // Create roles and assign permissions
        
        // Super Admin - Has ALL permissions (cannot be deleted)
        $superAdmin = Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web']);
        $superAdmin->syncPermissions(Permission::all());

        // Admin - Has ALL permissions
        $admin = Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web']);
        $admin->syncPermissions(Permission::all());

        $manager = Role::firstOrCreate(['name' => 'Manager', 'guard_name' => 'web']);
        $manager->syncPermissions([
            'users.view',
            'employees.view',
            'employees.create',
            'employees.edit',
            'employees.manage',
            'customers.view',
            'customers.manage',
            'transactions.view',
            'transactions.manage',
        ]);

        $finance = Role::firstOrCreate(['name' => 'Finance', 'guard_name' => 'web']);
        $finance->syncPermissions([
            'customers.view',
            'transactions.view',
            'transactions.manage',
        ]);

        $employee = Role::firstOrCreate(['name' => 'Employee', 'guard_name' => 'web']);
        $employee->syncPermissions([
            'customers.view',
            'transactions.view',
        ]);

        $fieldWorker = Role::firstOrCreate(['name' => 'Field Worker', 'guard_name' => 'web']);
        $fieldWorker->syncPermissions([
            'customers.view',
            'transactions.view',
        ]);

        $this->command->info('Roles and permissions created successfully!');
        $this->command->info('Super Admin role has all permissions (protected).');
        $this->command->info('Admin role has all permissions.');
        $this->command->info('Manager role has user and customer management permissions.');
    }
}
