<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RolesAndPermissionsSeeder::class,
            ComprehensivePermissionsSeeder::class,
            DayStartPermissionsSeeder::class,
        ]);

        // Create or find admin user and assign Super Admin role
        $adminUser = User::firstOrCreate(
            ['email' => 'admin@gmail.com'],
            [
                'name' => 'Admin User',
                'password' => bcrypt('123456'),
                'is_active' => true,
            ]
        );
        $adminUser->assignRole('Super Admin');

        $this->call([
            TourMasterDataSeeder::class,
            EmployeeSeeder::class,
            ProductSeeder::class,
            PaymentSettingsSeeder::class,
            FuelAllowanceRateSeeder::class,
            PartyDetailSeeder::class,
        ]);

        // \App\Models\User::factory(10)->create();
    }
}
