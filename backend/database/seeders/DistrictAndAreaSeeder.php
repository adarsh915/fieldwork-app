<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\District;
use App\Models\Area;
use App\Models\Customer;

class DistrictAndAreaSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            'Ahmedabad' => [
                'code' => 'GJ-AHM',
                'areas' => [
                    ['name' => 'Vatva GIDC', 'pincode' => '382445'],
                    ['name' => 'Naroda GIDC', 'pincode' => '382330'],
                    ['name' => 'Odhav Industrial Area', 'pincode' => '382415'],
                    ['name' => 'Sanand GIDC', 'pincode' => '382110'],
                    ['name' => 'Changodar Industrial Estate', 'pincode' => '382213'],
                    ['name' => 'Bavla', 'pincode' => '382220'],
                    ['name' => 'Sarkhej - SG Highway', 'pincode' => '380054'],
                    ['name' => 'Maninagar', 'pincode' => '380008'],
                ],
            ],
            'Surat' => [
                'code' => 'GJ-SUR',
                'areas' => [
                    ['name' => 'Ring Road Textile Market', 'pincode' => '395002'],
                    ['name' => 'Pandesara GIDC', 'pincode' => '394221'],
                    ['name' => 'Sachin GIDC', 'pincode' => '394230'],
                    ['name' => 'Katargam Diamond Zone', 'pincode' => '395004'],
                    ['name' => 'Varachha', 'pincode' => '395006'],
                    ['name' => 'Hazira Industrial Belt', 'pincode' => '394270'],
                ],
            ],
            'Rajkot' => [
                'code' => 'GJ-RAJ',
                'areas' => [
                    ['name' => 'Shapar GIDC', 'pincode' => '360024'],
                    ['name' => 'Metoda GIDC', 'pincode' => '360021'],
                    ['name' => 'Aji GIDC', 'pincode' => '360003'],
                    ['name' => 'Bhaktinagar Station Plot', 'pincode' => '360002'],
                    ['name' => 'Kuvadva GIDC', 'pincode' => '360023'],
                ],
            ],
            'Vadodara' => [
                'code' => 'GJ-BRD',
                'areas' => [
                    ['name' => 'Makarpura GIDC', 'pincode' => '390010'],
                    ['name' => 'Nandesari GIDC', 'pincode' => '391340'],
                    ['name' => 'Manjusar GIDC (Savli)', 'pincode' => '391775'],
                    ['name' => 'Waghodia GIDC', 'pincode' => '391760'],
                    ['name' => 'Gorwa Industrial Estate', 'pincode' => '390016'],
                ],
            ],
            'Morbi' => [
                'code' => 'GJ-MOR',
                'areas' => [
                    ['name' => 'Lakhdhirpur Road Ceramic Zone', 'pincode' => '363642'],
                    ['name' => 'Pipali Road Ceramic Zone', 'pincode' => '363642'],
                    ['name' => '8-A National Highway', 'pincode' => '363641'],
                    ['name' => 'Halvad Road Industrial Area', 'pincode' => '363641'],
                ],
            ],
            'Gandhinagar' => [
                'code' => 'GJ-GND',
                'areas' => [
                    ['name' => 'Kalol GIDC', 'pincode' => '382721'],
                    ['name' => 'Sector 25 / 26 Electronic Zone', 'pincode' => '382028'],
                    ['name' => 'Khatraj Industrial Area', 'pincode' => '382721'],
                ],
            ],
            'Anand' => [
                'code' => 'GJ-AND',
                'areas' => [
                    ['name' => 'Vitthal Udyognagar GIDC', 'pincode' => '388121'],
                    ['name' => 'Vallabh Vidyanagar', 'pincode' => '388120'],
                    ['name' => 'Anand City Market', 'pincode' => '388001'],
                ],
            ],
            'Bharuch' => [
                'code' => 'GJ-BHR',
                'areas' => [
                    ['name' => 'Ankleshwar GIDC', 'pincode' => '393002'],
                    ['name' => 'Panoli GIDC', 'pincode' => '394116'],
                    ['name' => 'Dahej PCPIR SEZ', 'pincode' => '392130'],
                    ['name' => 'Jhagadia GIDC', 'pincode' => '393110'],
                ],
            ],
        ];

        foreach ($data as $distName => $distInfo) {
            $district = District::firstOrCreate(
                ['name' => $distName],
                [
                    'state' => 'Gujarat',
                    'code' => $distInfo['code'],
                    'is_active' => true,
                ]
            );

            foreach ($distInfo['areas'] as $areaInfo) {
                Area::firstOrCreate(
                    [
                        'district_id' => $district->id,
                        'name' => $areaInfo['name'],
                    ],
                    [
                        'pincode' => $areaInfo['pincode'],
                        'is_active' => true,
                    ]
                );
            }
        }

        // Link existing customers to matching districts based on city field
        $districts = District::all();
        foreach ($districts as $d) {
            Customer::whereNull('district_id')
                ->where(function ($q) use ($d) {
                    $q->where('city', 'like', "%{$d->name}%")
                      ->orWhere('address', 'like', "%{$d->name}%");
                })
                ->update(['district_id' => $d->id]);
        }
    }
}
