<?php
// database/seeders/VendorSystemSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\VendorPlan;
use App\Models\VendorVehicleType;

class VendorSystemSeeder extends Seeder
{
    public function run()
    {
        // ✅ VENDOR PLANS
        $vendorPlans = [
            [
                'name' => 'Basic Plan',
                'description' => 'Perfect for getting started',
                'price' => 299.00,
                'duration_type' => 'monthly',
                'duration_days' => 30,
                'features' => [
                    'List your vehicle',
                    'Receive booking requests',
                    'Basic support',
                    'Payment processing'
                ],
                'is_popular' => false,
                'button_text' => 'Choose Basic',
                'button_color' => '#4CAF50',
                'sort_order' => 1
            ],
            [
                'name' => 'Pro Plan',
                'description' => 'Most popular choice for vendors',
                'price' => 599.00,
                'duration_type' => 'monthly',
                'duration_days' => 30,
                'features' => [
                    'Everything in Basic',
                    'Priority listing',
                    'Advanced analytics',
                    'Priority support',
                    'Multiple vehicle support'
                ],
                'is_popular' => true,
                'button_text' => 'Choose Pro',
                'button_color' => '#FF9800',
                'sort_order' => 2
            ],
            [
                'name' => 'Premium Plan',
                'description' => 'Complete solution for professionals',
                'price' => 999.00,
                'duration_type' => 'monthly',
                'duration_days' => 30,
                'features' => [
                    'Everything in Pro',
                    'Premium listing position',
                    'Detailed analytics',
                    '24/7 support',
                    'Custom branding',
                    'API access'
                ],
                'is_popular' => false,
                'button_text' => 'Choose Premium',
                'button_color' => '#9C27B0',
                'sort_order' => 3
            ]
        ];

        foreach ($vendorPlans as $planData) {
            VendorPlan::create($planData);
        }

        // ✅ VENDOR VEHICLE TYPES
        $vehicleTypes = [
            [
                'name' => 'Mini Truck',
                'description' => 'Small trucks for city deliveries',
                'available_lengths' => [8, 10],
                'available_capacities' => [2, 3, 5],
                'tyre_variants' => [5],
                'sort_order' => 1
            ],
            [
                'name' => 'Pickup',
                'description' => 'Pickup trucks for medium loads',
                'available_lengths' => [8, 14, 16],
                'available_capacities' => [5, 10],
                'tyre_variants' => [5, 10],
                'sort_order' => 2
            ],
            [
                'name' => 'Truck',
                'description' => 'Standard trucks for heavy loads',
                'available_lengths' => [20, 22, 24],
                'available_capacities' => [15, 20, 25],
                'tyre_variants' => [10],
                'sort_order' => 3
            ],
            [
                'name' => 'Container',
                'description' => 'Container trucks for secure transport',
                'available_lengths' => [20, 24, 28],
                'available_capacities' => [20, 25, 30],
                'tyre_variants' => [10],
                'sort_order' => 4
            ]
        ];

        foreach ($vehicleTypes as $typeData) {
            VendorVehicleType::create($typeData);
        }
    }
}
