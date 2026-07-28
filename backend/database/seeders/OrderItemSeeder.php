<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;

class OrderItemSeeder extends Seeder
{
    public function run(): void
    {
        $products = Product::pluck('id')->toArray();

        $this->command->info('Seeding order items for existing orders...');
        Order::chunk(500, function($orders) use ($products) {
            foreach ($orders as $order) {
                if ($order->items()->count() === 0) {
                    for ($i = 0; $i < rand(1, 4); $i++) {
                        $qty = rand(1, 20);
                        $rate = rand(100, 1000);
                        $discount = rand(0, 10);
                        
                        $randomProductId = $products[array_rand($products)];

                        OrderItem::create([
                            'order_id' => $order->id,
                            'product_id' => $randomProductId,
                            'qty' => $qty,
                            'rate' => $rate,
                            'discount' => $discount,
                            'total' => ($qty * $rate) - $discount,
                        ]);
                    }
                }
            }
        });

        $this->command->info('Done!');
    }
}
