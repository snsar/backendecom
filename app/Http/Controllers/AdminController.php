<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{
    public function getDashboardData()
    {
        // Fetch total number of users
        $totalUsers = DB::table('users')->count();

        // Fetch total number of orders with status 'processing'
        $totalProcessingOrders = DB::table('orders')
            ->where('status', 'processing')
            ->count();

        // Fetch revenue for the current month with status 'processing'
        $currentMonthRevenue = DB::table('orders')
            ->whereMonth('created_at', date('m'))
            ->whereYear('created_at', date('Y'))
            ->where('status', 'processing')
            ->sum('total_amount');

        // Fetch daily revenue for the current month
        $dailyRevenue = DB::table('orders')
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('SUM(total_amount) as revenue'))
            ->whereMonth('created_at', date('m'))
            ->whereYear('created_at', date('Y'))
            ->where('status', 'processing')
            ->groupBy('date')
            ->get();

        // Fetch top 5 products with highest average rating
        $topRatedProducts = DB::table('products')
            ->join('reviews', 'products.id', '=', 'reviews.product_id')
            ->select('products.id', 'products.name', 'products.image_url', DB::raw('AVG(reviews.rating) as average_rating'))
            ->groupBy('products.id', 'products.name', 'products.image_url')
            ->orderBy('average_rating', 'desc')
            ->limit(5)
            ->get();

        return response()->json([
            'totalUsers' => $totalUsers,
            'totalProcessingOrders' => $totalProcessingOrders,
            'currentMonthRevenue' => $currentMonthRevenue,
            'dailyRevenue' => $dailyRevenue,
            'topRatedProducts' => $topRatedProducts,
        ]);
    }
}
