<?php

namespace Database\Seeders;

use App\Models\TourPurpose;
use App\Models\TourType;
use App\Models\VehicleType;
use Illuminate\Database\Seeder;

class TourMasterDataSeeder extends Seeder
{
    public function run(): void
    {
        $purposes = [
            ['name' => 'Party Visit', 'requires_party' => true],
            ['name' => 'Field Visit', 'requires_party' => false],
            ['name' => 'Office Visit', 'requires_party' => false],
            ['name' => 'Work From Home', 'requires_party' => false],
            ['name' => 'Other', 'requires_party' => false],
        ];

        foreach ($purposes as $purpose) {
            TourPurpose::updateOrCreate(
                ['name' => $purpose['name']],
                ['requires_party' => $purpose['requires_party'], 'is_active' => true]
            );
        }

        $tourTypes = [
            ['name' => 'In Headquarter', 'requires_vehicle' => true],
            ['name' => 'Out of Headquarter', 'requires_vehicle' => true],
            ['name' => 'Same Day Return', 'requires_vehicle' => true],
            ['name' => 'Tour From Home', 'requires_vehicle' => true],
            ['name' => 'Work From Home', 'requires_vehicle' => false],
            ['name' => 'Other', 'requires_vehicle' => true],
        ];

        foreach ($tourTypes as $type) {
            TourType::updateOrCreate(
                ['name' => $type['name']],
                ['requires_vehicle' => $type['requires_vehicle'], 'is_active' => true]
            );
        }

        $vehicleTypes = [
            ['name' => 'Two Wheeler Personal', 'requires_vehicle_no' => true],
            ['name' => 'Four Wheeler Personal', 'requires_vehicle_no' => true],
            ['name' => 'Two Wheeler Company', 'requires_vehicle_no' => true],
            ['name' => 'Four Wheeler Company', 'requires_vehicle_no' => true],
            ['name' => 'Multi Vehicle', 'requires_vehicle_no' => true],
            ['name' => 'Work From Home', 'requires_vehicle_no' => false],
            ['name' => 'Other', 'requires_vehicle_no' => true],
        ];

        foreach ($vehicleTypes as $type) {
            VehicleType::updateOrCreate(
                ['name' => $type['name']],
                ['requires_vehicle_no' => $type['requires_vehicle_no'], 'is_active' => true]
            );
        }

        $this->command?->info('Tour master data seeded successfully.');
    }
}
