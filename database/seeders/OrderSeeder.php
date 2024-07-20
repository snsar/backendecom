<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class OrderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Order::factory()
            ->count(50)
            ->create()
            ->each(function ($order) {
                $orderItems = OrderItem::factory()
                    ->count(rand(1, 5))
                    ->make();
                $order->orderItems()->saveMany($orderItems);
                $order->total_amount = $orderItems->sum(function ($item) {
                    return $item->price * $item->quantity;
                });
                $order->save();
            });
    }
}
