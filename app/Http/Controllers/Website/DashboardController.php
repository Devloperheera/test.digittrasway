<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Vendor;

class WebHomeController extends Controller
{
    public function home()
    {
        $totalUsers = 0;
        $totalVendors = 0;

        try {
            $totalUsers = User::count();
        } catch (\Exception $e) {
            $totalUsers = 0;
        }

        try {
            $totalVendors = Vendor::count();
        } catch (\Exception $e) {
            $totalVendors = 0;
        }

        return view('Website.home', [
            'totalUsers' => $totalUsers,
            'totalVendors' => $totalVendors,
            'totalBookings' => 0,
            'pendingBookings' => 0,
            'activeSubscriptions' => 0,
            'totalRevenue' => 0,
            'bookingGrowth' => 0,
            'recentBookings' => collect([]),
            'bookingStats' => [
                'labels' => ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                'data' => [0, 0, 0, 0, 0, 0, 0]
            ],
            'recentActivities' => collect([])
        ]);
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
