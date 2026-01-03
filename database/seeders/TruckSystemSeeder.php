<?php
// database/seeders/TruckSystemSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Material;
use App\Models\TruckType;
use App\Models\TruckSpecification;
use App\Models\PricingRule;

class TruckSystemSeeder extends Seeder
{
    public function run()
    {
        // ✅ PRICING RULES - MAIN PRICE SOURCE
        $pricingRules = [
            [
                'name' => 'Local Delivery',
                'price_per_km' => 8.0,              // ₹8/km
                'distance_from' => 0,
                'distance_to' => 25,
                'minimum_charge' => 200.0,
                'description' => 'Local city delivery',
                'sort_order' => 1
            ],
            [
                'name' => 'Short Distance',
                'price_per_km' => 12.0,             // ₹12/km
                'distance_from' => 25,
                'distance_to' => 100,
                'minimum_charge' => 500.0,
                'description' => 'Short distance transport',
                'sort_order' => 2
            ],
            [
                'name' => 'Medium Distance',
                'price_per_km' => 15.0,             // ₹15/km
                'distance_from' => 100,
                'distance_to' => 500,
                'minimum_charge' => 1500.0,
                'description' => 'Medium distance transport',
                'sort_order' => 3
            ],
            [
                'name' => 'Long Distance',
                'price_per_km' => 18.0,             // ₹18/km
                'distance_from' => 500,
                'distance_to' => null,             // Unlimited
                'minimum_charge' => 5000.0,
                'description' => 'Long distance transport',
                'sort_order' => 4
            ]
        ];

        foreach ($pricingRules as $rule) {
            PricingRule::create($rule);
        }

        // Materials (same as before)
        $materials = [
            ['name' => 'Food & Beverages', 'sort_order' => 1],
            ['name' => 'Electronics', 'sort_order' => 2],
            ['name' => 'Furniture', 'sort_order' => 3],
            ['name' => 'Construction Materials', 'sort_order' => 4],
            ['name' => 'Textiles', 'sort_order' => 5],
            ['name' => 'Machinery', 'sort_order' => 6]
        ];

        foreach ($materials as $material) {
            Material::create($material);
        }

        // Truck Types
        $openTruck = TruckType::create([
            'name' => 'Open Truck',
            'description' => 'Standard open truck for general cargo',
            'sort_order' => 1
        ]);

        $container = TruckType::create([
            'name' => 'Container',
            'description' => 'Closed container truck for secure transport',
            'sort_order' => 2
        ]);

        // Truck Specifications (NO PRICE - Only specifications)
        $specifications = [
            // Open Truck specs
            ['truck_type_id' => $openTruck->id, 'length' => 22.0, 'tyre_count' => 5, 'height' => 5.0, 'max_weight' => 15.0],
            ['truck_type_id' => $openTruck->id, 'length' => 24.0, 'tyre_count' => 10, 'height' => 6.5, 'max_weight' => 15.0],
            ['truck_type_id' => $openTruck->id, 'length' => 25.0, 'tyre_count' => 10, 'height' => 9.0, 'max_weight' => 20.0],
            ['truck_type_id' => $openTruck->id, 'length' => 28.0, 'tyre_count' => 10, 'height' => 9.5, 'max_weight' => 25.0],

            // Container specs
            ['truck_type_id' => $container->id, 'length' => 22.0, 'tyre_count' => 10, 'height' => 8.0, 'max_weight' => 20.0],
            ['truck_type_id' => $container->id, 'length' => 24.0, 'tyre_count' => 10, 'height' => 8.5, 'max_weight' => 22.0],
            ['truck_type_id' => $container->id, 'length' => 25.0, 'tyre_count' => 10, 'height' => 9.0, 'max_weight' => 25.0],
            ['truck_type_id' => $container->id, 'length' => 28.0, 'tyre_count' => 10, 'height' => 9.5, 'max_weight' => 30.0]
        ];

        foreach ($specifications as $spec) {
            TruckSpecification::create($spec);
        }
    }
}
