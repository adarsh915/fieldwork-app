<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class DayStartPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            'master-data.manage',
            'day-start.create',
            'day-start.view-own',
            'day-start.view-team',
            'day-start.view-all',
            'day-start.approve',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        $assignments = [
            'Admin' => $permissions,
            'Manager' => [
                'day-start.create',
                'day-start.view-team',
                'day-start.approve',
            ],
            'Employee' => [
                'day-start.create',
                'day-start.view-own',
            ],
            'admin' => $permissions,
            'manager' => [
                'day-start.create',
                'day-start.view-team',
                'day-start.approve',
            ],
            'employee' => [
                'day-start.create',
                'day-start.view-own',
            ],
        ];

        foreach ($assignments as $roleName => $rolePermissions) {
            $role = Role::where('name', $roleName)->first();

            if ($role) {
                $role->givePermissionTo($rolePermissions);
            }
        }

        $superAdmin = Role::where('name', 'Super Admin')->first();
        if ($superAdmin) {
            $superAdmin->givePermissionTo($permissions);
        }

        $this->command?->info('Day Start permissions seeded successfully.');
    }
}
