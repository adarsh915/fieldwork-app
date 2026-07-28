<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class LoadTestDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Create 10 Fieldworkers
        $fieldWorkerRole = Role::firstOrCreate(['name' => 'field_worker', 'guard_name' => 'web']);
        $newUsers = [];
        
        $this->command->info("Creating 10 fieldworkers...");
        for ($i = 1; $i <= 10; $i++) {
            $user = User::create([
                'name' => 'Field Worker ' . $i,
                'email' => "fw{$i}_" . time() . "@example.com",
                'password' => Hash::make('password'),
                'phone' => '+1' . rand(1000000000, 9999999999),
                'employee_id' => 'FW-TEST-' . str_pad($i, 3, '0', STR_PAD_LEFT),
                'department' => 'Field Operations',
                'position' => 'Field Worker',
                'hire_date' => now()->subDays(rand(1, 365)),
                'salary' => rand(30000, 50000),
                'is_active' => true,
            ]);
            $user->assignRole($fieldWorkerRole);
            $newUsers[] = $user->id;
        }

        // 2. Create 1000 Parties (Customers)
        $this->command->info("Creating 1000 parties with exact 1 fieldworker assignment...");
        $customers = [];
        $cities = ['Mumbai', 'Delhi', 'Bangalore', 'Hyderabad', 'Ahmedabad', 'Chennai', 'Kolkata', 'Surat', 'Pune', 'Jaipur'];
        
        for ($i = 1; $i <= 1000; $i++) {
            $customers[] = [
                'name'       => 'Party Enterprise ' . $i,
                'mobile'     => '+91' . rand(7000000000, 9999999999),
                'email'      => "party{$i}_" . time() . "@example.com",
                'address'    => 'Plot ' . rand(1, 999) . ', Industrial Area, ' . $cities[array_rand($cities)],
                'user_id'    => $newUsers[array_rand($newUsers)], // Assign single fieldworker directly
                'land_area'  => rand(100, 2000) . ' sqft',
                'latitude'   => rand(8000000, 37000000) / 1000000, // India approximate latitudes
                'longitude'  => rand(68000000, 97000000) / 1000000, // India approximate longitudes
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        // Insert in chunks for performance
        foreach (array_chunk($customers, 250) as $chunk) {
            Customer::insert($chunk);
        }

        $this->command->info("Seeding completed successfully!");
    }
}
