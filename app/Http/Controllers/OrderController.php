<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function index()
    {
        $userId = Auth::id();

        // Lấy thông tin đơn hàng
        $orders = DB::table('orders')
            ->where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->get();

        // Lấy thông tin các mặt hàng trong đơn hàng
        $ordersWithItems = $orders->map(function ($order) {
            $items = DB::table('order_items')
                ->join('products', 'order_items.product_id', '=', 'products.id')
                ->select('order_items.quantity', 'order_items.price', 'products.name', 'products.image_url')
                ->where('order_items.order_id', $order->id)
                ->get();

            $order->items = $items;
            return $order;
        });

        return response()->json($ordersWithItems);
    }
}
