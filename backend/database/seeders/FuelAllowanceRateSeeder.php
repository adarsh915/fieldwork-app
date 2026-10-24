<?php

namespace Database\Seeders;

use App\Models\FuelAllowanceRate;
use App\Models\VehicleType;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class FuelAllowanceRateSeeder extends Seeder
{
    public function run(): void
    {
        $today = Carbon::today()->toDateString();

        // 1. Ensure Bike and Car exist
        $bike = VehicleType::firstOrCreate(
            ['slug' => 'bike'],
            ['name' => 'Bike', 'requires_vehicle_no' => true, 'is_active' => true, 'rate_per_km' => 3.50]
        );

        $car = VehicleType::firstOrCreate(
            ['slug' => 'car'],
            ['name' => 'Car', 'requires_vehicle_no' => true, 'is_active' => true, 'rate_per_km' => 8.00]
        );

        // 2. Global Base Rates (Tier 3)
        FuelAllowanceRate::updateOrCreate(
            [
                'vehicle_type_id' => $bike->id,
                'scope_type'      => 'global',
                'scope_id'        => null,
            ],
            [
                'rate_per_km'    => 3.50,
                'effective_from' => '2026-01-01',
                'effective_to'   => null,
                'is_active'      => true,
                'notes'          => 'Global standard base rate for bikes',
            ]
        );

        FuelAllowanceRate::updateOrCreate(
            [
                'vehicle_type_id' => $car->id,
                'scope_type'      => 'global',
                'scope_id'        => null,
            ],
            [
                'rate_per_km'    => 8.00,
                'effective_from' => '2026-01-01',
                'effective_to'   => null,
                'is_active'      => true,
                'notes'          => 'Global standard base rate for four wheelers',
            ]
        );

        // 3. Role-based Default Rates (Tier 2)
        $salesRole = Role::where('name', 'like', '%Sales%')->orWhere('name', 'like', '%Field%')->first();
        if ($salesRole) {
            FuelAllowanceRate::updateOrCreate(
                [
                    'vehicle_type_id' => $bike->id,
                    'scope_type'      => 'role',
                    'scope_id'        => $salesRole->id,
                ],
                [
                    'rate_per_km'    => 4.50,
                    'effective_from' => '2026-01-01',
                    'effective_to'   => null,
                    'is_active'      => true,
                    'notes'          => "Default bike allowance rate for {$salesRole->name}",
                ]
            );
        }

        $managerRole = Role::where('name', 'like', '%Manager%')->first();
        if ($managerRole) {
            FuelAllowanceRate::updateOrCreate(
                [
                    'vehicle_type_id' => $bike->id,
                    'scope_type'      => 'role',
                    'scope_id'        => $managerRole->id,
                ],
                [
                    'rate_per_km'    => 6.00,
                    'effective_from' => '2026-01-01',
                    'effective_to'   => null,
                    'is_active'      => true,
                    'notes'          => "Default bike allowance rate for {$managerRole->name}",
                ]
            );

            FuelAllowanceRate::updateOrCreate(
                [
                    'vehicle_type_id' => $car->id,
                    'scope_type'      => 'role',
                    'scope_id'        => $managerRole->id,
                ],
                [
                    'rate_per_km'    => 10.00,
                    'effective_from' => '2026-01-01',
                    'effective_to'   => null,
                    'is_active'      => true,
                    'notes'          => "Default car allowance rate for {$managerRole->name}",
                ]
            );
        }

        $this->command?->info('Fuel allowance rates seeded successfully.');
    }
}
