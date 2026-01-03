<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UserTypeSeeder extends Seeder
{
    public function run()
    {
        $userTypes = [
            [
                'type_key' => 'fleet_owner',
                'title' => 'Fleet Owner',
                'subtitle' => 'BUSINESS & OPERATIONS',
                'description' => 'Manage your fleet, track performance, optimize routes, and grow your business with powerful analytics tools.',
                'icon' => '🏢',
                'features' => json_encode([
                    'Comprehensive Fleet Management',
                    'Real-time Driver Analytics',
                    'Advanced Revenue Tracking',
                    'Route Optimization Engine'
                ]),
                'is_active' => true,
                'display_order' => 1,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'type_key' => 'professional_driver',
                'title' => 'Professional Driver',
                'subtitle' => 'ON THE ROAD',
                'description' => 'Get assignments, navigate efficiently, track earnings, and build your driving career with our comprehensive tools.',
                'icon' => '👨‍💼',
                'features' => json_encode([
                    'Get Instant Job Assignments',
                    'Real-time Navigation',
                    'Track Your Earnings',
                    'Build Your Career Profile'
                ]),
                'is_active' => true,
                'display_order' => 2,
                'created_at' => now(),
                'updated_at' => now()
            ]
        ];

        DB::table('user_types')->insert($userTypes);
    }
}
