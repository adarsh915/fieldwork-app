<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $products = [
            ['name' => 'Premium Fertilizer', 'code' => 'FRT-001', 'category_id' => 1, 'price' => 2500, 'unit' => 'Bag'],
            ['name' => 'Organic Seeds', 'code' => 'SD-002', 'category_id' => 2, 'price' => 500, 'unit' => 'Pack'],
            ['name' => 'Tractor Parts', 'code' => 'TRP-003', 'category_id' => 3, 'price' => 15000, 'unit' => 'Piece'],
            ['name' => 'Irrigation Pipes', 'code' => 'IRR-004', 'category_id' => 4, 'price' => 3000, 'unit' => 'Meter'],
            ['name' => 'Pesticide X', 'code' => 'PST-005', 'category_id' => 1, 'price' => 1200, 'unit' => 'Bottle'],
        ];

        foreach ($products as $p) {
            Product::firstOrCreate(
                ['code' => $p['code']],
                [
                    'name' => $p['name'],
                    'category_id' => $p['category_id'],
                    'description' => 'A great product for your fields.',
                    'price' => $p['price'],
                    'unit' => $p['unit'],
                    'status' => 'active',
                ]
            );
        }

        $this->command->info('Products seeded successfully!');
    }
}
