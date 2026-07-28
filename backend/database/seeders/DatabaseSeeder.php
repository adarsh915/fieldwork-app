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
        // Create an admin user first so RolesAndPermissionsSeeder can assign the Admin role to it
        $adminUser = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@gmail.com',
            'password' => bcrypt('123456'),
        ]);

        $this->call(RolesAndPermissionsSeeder::class);
        
        // Assign the Super Admin role to the admin user
        $adminUser->assignRole('Super Admin');

        $this->call([
            TourMasterDataSeeder::class,
            DayStartPermissionsSeeder::class,
            EmployeeSeeder::class,
            LoadTestDataSeeder::class,
            ProductSeeder::class,
            PartyDetailSeeder::class,
            OrderItemSeeder::class,
            NewSchemaDummyDataSeeder::class,
        ]);

        // \App\Models\User::factory(10)->create();
    }
}
