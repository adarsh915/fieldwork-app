<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class EmployeeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create roles if they don't exist
        $superAdminRole = Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web']);
        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $managerRole = Role::firstOrCreate(['name' => 'manager', 'guard_name' => 'web']);
        $employeeRole = Role::firstOrCreate(['name' => 'employee', 'guard_name' => 'web']);
        $fieldWorkerRole = Role::firstOrCreate(['name' => 'Field Worker', 'guard_name' => 'web']);

        // Create admin user
        $admin = User::firstOrCreate(
            ['email' => 'admin@gmail.com'],
            [
                'name' => 'System Administrator',
                'password' => Hash::make('123456'),
                'phone' => '+1234567890',
                'employee_id' => 'EMP001',
                'department' => 'IT',
                'position' => 'System Administrator',
                'hire_date' => now()->subYears(3),
                'salary' => 75000,
                'is_active' => true,
            ]
        );
        $admin->assignRole($superAdminRole);

        // Create manager user
        $manager = User::firstOrCreate(
            ['email' => 'manager@example.com'],
            [
                'name' => 'John Manager',
                'password' => Hash::make('password'),
                'phone' => '+1234567891',
                'employee_id' => 'EMP002',
                'department' => 'Operations',
                'position' => 'Operations Manager',
                'hire_date' => now()->subYears(2),
                'salary' => 65000,
                'is_active' => true,
            ]
        );
        $manager->assignRole($managerRole);

        // Create employee users
        $employee1 = User::firstOrCreate(
            ['email' => 'employee1@example.com'],
            [
                'name' => 'Jane Employee',
                'password' => Hash::make('password'),
                'phone' => '+1234567892',
                'employee_id' => 'EMP003',
                'department' => 'Sales',
                'position' => 'Sales Representative',
                'hire_date' => now()->subYear(),
                'salary' => 45000,
                'is_active' => true,
            ]
        );
        $employee1->assignRole($employeeRole);

        // Create field worker
        $fieldWorker = User::firstOrCreate(
            ['email' => 'fieldworker@example.com'],
            [
                'name' => 'Bob Field',
                'password' => Hash::make('password'),
                'phone' => '+1234567893',
                'employee_id' => 'EMP004',
                'department' => 'Field Operations',
                'position' => 'Field Worker',
                'hire_date' => now()->subMonths(6),
                'salary' => 40000,
                'is_active' => true,
            ]
        );
        $fieldWorker->assignRole($fieldWorkerRole);

        $this->command->info('Employees seeded successfully!');
    }
}
