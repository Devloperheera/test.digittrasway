<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Vendor;
use App\Models\Plan;
use App\Models\UserType;
use App\Models\TruckType;
use App\Models\VehicleModel;
use App\Models\VendorPlan;
use Illuminate\Http\Request;
use Carbon\Carbon;

class WebHomeController extends Controller
{
    public function home()
    {
        // Main Stats
        $totalUsers = User::count();
        $totalVendors = Vendor::count();
        $totalBookings = 0;
        $pendingBookings = 0;
        $activeSubscriptions = 0;
        $totalRevenue = 0;

        // Additional Stats
        $totalPlans = Plan::count();
        $totalUserTypes = UserType::count();
        $totalTruckTypes = TruckType::count();
        $totalVehicleModels = VehicleModel::count();
        $totalVendorPlans = VendorPlan::count();

        // Chart Data - Last 7 Days Users Registration
        $chartData = $this->getUserChartData();

        // Recent Users
        $recentUsers = User::latest()->take(5)->get();

        // Recent Vendors
        $recentVendors = Vendor::latest()->take(5)->get();

        // Monthly Growth
        $currentMonth = User::whereMonth('created_at', Carbon::now()->month)->count();
        $lastMonth = User::whereMonth('created_at', Carbon::now()->subMonth()->month)->count();
        $userGrowth = $lastMonth > 0 ? round((($currentMonth - $lastMonth) / $lastMonth) * 100, 1) : 0;

        return view('Website.home', compact(
            'totalUsers',
            'totalVendors',
            'totalBookings',
            'pendingBookings',
            'activeSubscriptions',
            'totalRevenue',
            'totalPlans',
            'totalUserTypes',
            'totalTruckTypes',
            'totalVehicleModels',
            'totalVendorPlans',
            'chartData',
            'recentUsers',
            'recentVendors',
            'userGrowth'
        ));
    }

    private function getUserChartData()
    {
        $labels = [];
        $data = [];

        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $labels[] = $date->format('D');
            $data[] = User::whereDate('created_at', $date->format('Y-m-d'))->count();
        }

        return [
            'labels' => $labels,
            'data' => $data
        ];
    }

    public function forms()
    {
        return view('Website.forms');
    }

    public function table()
    {
        return view('Website.table');
    }
}
