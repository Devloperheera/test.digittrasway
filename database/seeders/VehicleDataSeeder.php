<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class VehicleDataSeeder extends Seeder
{
    public function run()
    {
        // ✅ Insert Vehicle Categories
        $categories = [
            [
                'id' => 1,
                'category_key' => 'open_truck',
                'category_name' => 'Open Truck',
                'description' => 'Open body trucks for various cargo types',
                'icon' => '🚚',
                'is_active' => true,
                'display_order' => 1,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'id' => 2,
                'category_key' => 'container',
                'category_name' => 'Container',
                'description' => 'Closed container trucks for secure transport',
                'icon' => '📦',
                'is_active' => true,
                'display_order' => 2,
                'created_at' => now(),
                'updated_at' => now()
            ]
        ];

        DB::table('vehicle_categories')->insert($categories);

        // ✅ Insert Vehicle Models - OPEN TRUCK (Category ID: 1)
        $openTruckModels = [
            ['category_id' => 1, 'model_name' => '3 WHEELER 500 KG', 'vehicle_type_desc' => '3 Wheeler', 'body_length' => 5, 'body_width' => 4, 'body_height' => 4, 'carry_capacity_kgs' => 500, 'carry_capacity_tons' => 0.5, 'is_active' => true, 'display_order' => 1],
            ['category_id' => 1, 'model_name' => 'PICK UP 800 KG', 'vehicle_type_desc' => 'PICK-UP', 'body_length' => 8, 'body_width' => 6, 'body_height' => 6, 'carry_capacity_kgs' => 800, 'carry_capacity_tons' => 0.8, 'is_active' => true, 'display_order' => 2],
            ['category_id' => 1, 'model_name' => 'PICK UP 1MT', 'vehicle_type_desc' => 'PICK-UP', 'body_length' => 8, 'body_width' => 6, 'body_height' => 6, 'carry_capacity_kgs' => 1000, 'carry_capacity_tons' => 1, 'is_active' => true, 'display_order' => 3],
            ['category_id' => 1, 'model_name' => 'PICKUP 1.2MT', 'vehicle_type_desc' => 'PICK-UP', 'body_length' => 8, 'body_width' => 6, 'body_height' => 6, 'carry_capacity_kgs' => 1200, 'carry_capacity_tons' => 1.2, 'is_active' => true, 'display_order' => 4],
            ['category_id' => 1, 'model_name' => '407 & EQUIVALENT 2.5MT', 'vehicle_type_desc' => 'LCV-Tempo', 'body_length' => 9.6, 'body_width' => 6, 'body_height' => 6, 'carry_capacity_kgs' => 2500, 'carry_capacity_tons' => 2.5, 'is_active' => true, 'display_order' => 5],
            ['category_id' => 1, 'model_name' => 'LCV 3.5MT', 'vehicle_type_desc' => 'LCV', 'body_length' => 14, 'body_width' => 6, 'body_height' => 6, 'carry_capacity_kgs' => 3500, 'carry_capacity_tons' => 3.5, 'is_active' => true, 'display_order' => 6],
            ['category_id' => 1, 'model_name' => 'LPT 5MT', 'vehicle_type_desc' => 'LPT', 'body_length' => 17, 'body_width' => 6.6, 'body_height' => 7, 'carry_capacity_kgs' => 5000, 'carry_capacity_tons' => 5, 'is_active' => true, 'display_order' => 7],
            ['category_id' => 1, 'model_name' => 'LPT 7MT', 'vehicle_type_desc' => 'LPT', 'body_length' => 19, 'body_width' => 6.9, 'body_height' => 7, 'carry_capacity_kgs' => 7000, 'carry_capacity_tons' => 7, 'is_active' => true, 'display_order' => 8],
            ['category_id' => 1, 'model_name' => '32\' Open 7MT', 'vehicle_type_desc' => '32 FEET OPEN', 'body_length' => 32, 'body_width' => 8, 'body_height' => 7, 'carry_capacity_kgs' => 7000, 'carry_capacity_tons' => 7, 'is_active' => true, 'display_order' => 9],
            ['category_id' => 1, 'model_name' => '28\' JCB 8MT', 'vehicle_type_desc' => 'JCB', 'body_length' => 28, 'body_width' => 8, 'body_height' => 7, 'carry_capacity_kgs' => 8000, 'carry_capacity_tons' => 8, 'is_active' => true, 'display_order' => 10],
            ['category_id' => 1, 'model_name' => '32\' Open 9MT', 'vehicle_type_desc' => '32 FEET OPEN', 'body_length' => 32, 'body_width' => 8, 'body_height' => 7, 'carry_capacity_kgs' => 9000, 'carry_capacity_tons' => 9, 'is_active' => true, 'display_order' => 11],
            ['category_id' => 1, 'model_name' => 'FTL 9MT', 'vehicle_type_desc' => 'FTL', 'body_length' => 18, 'body_width' => 6.9, 'body_height' => 7, 'carry_capacity_kgs' => 9000, 'carry_capacity_tons' => 9, 'is_active' => true, 'display_order' => 12],
            ['category_id' => 1, 'model_name' => '28\' JCB 10MT', 'vehicle_type_desc' => 'JCB', 'body_length' => 28, 'body_width' => 8, 'body_height' => 7, 'carry_capacity_kgs' => 10000, 'carry_capacity_tons' => 10, 'is_active' => true, 'display_order' => 13],
            ['category_id' => 1, 'model_name' => 'FTL 11MT', 'vehicle_type_desc' => 'FTL', 'body_length' => 18, 'body_width' => 6.9, 'body_height' => 7, 'carry_capacity_kgs' => 11000, 'carry_capacity_tons' => 11, 'is_active' => true, 'display_order' => 14],
            ['category_id' => 1, 'model_name' => '32\' Open 14MT', 'vehicle_type_desc' => '32 FEET OPEN', 'body_length' => 32, 'body_width' => 8, 'body_height' => 7, 'carry_capacity_kgs' => 14000, 'carry_capacity_tons' => 14, 'is_active' => true, 'display_order' => 15],
            ['category_id' => 1, 'model_name' => '28\' JCB 15MT', 'vehicle_type_desc' => 'JCB', 'body_length' => 28, 'body_width' => 8, 'body_height' => 7, 'carry_capacity_kgs' => 15000, 'carry_capacity_tons' => 15, 'is_active' => true, 'display_order' => 16],
            ['category_id' => 1, 'model_name' => 'Taurus 16MT', 'vehicle_type_desc' => 'TAURAS', 'body_length' => 22, 'body_width' => 7, 'body_height' => 7, 'carry_capacity_kgs' => 16000, 'carry_capacity_tons' => 16, 'is_active' => true, 'display_order' => 17],
            ['category_id' => 1, 'model_name' => 'Taurus 19MT', 'vehicle_type_desc' => 'TAURAS', 'body_length' => 22, 'body_width' => 7, 'body_height' => 7, 'carry_capacity_kgs' => 19000, 'carry_capacity_tons' => 19, 'is_active' => true, 'display_order' => 18],
            ['category_id' => 1, 'model_name' => '40\' Trailer 20MT', 'vehicle_type_desc' => 'TRAILOR', 'body_length' => 40, 'body_width' => 8, 'body_height' => 7, 'carry_capacity_kgs' => 20000, 'carry_capacity_tons' => 20, 'is_active' => true, 'display_order' => 19],
            ['category_id' => 1, 'model_name' => 'MXL Taurus 21MT', 'vehicle_type_desc' => 'TAURAS', 'body_length' => 24, 'body_width' => 7, 'body_height' => 7, 'carry_capacity_kgs' => 21000, 'carry_capacity_tons' => 21, 'is_active' => true, 'display_order' => 20],
            ['category_id' => 1, 'model_name' => '40\' Trailer 22MT', 'vehicle_type_desc' => 'TRAILOR', 'body_length' => 40, 'body_width' => 8, 'body_height' => 7, 'carry_capacity_kgs' => 22000, 'carry_capacity_tons' => 22, 'is_active' => true, 'display_order' => 21],
            ['category_id' => 1, 'model_name' => '40\' Trailer 24MT', 'vehicle_type_desc' => 'TRAILOR', 'body_length' => 40, 'body_width' => 8, 'body_height' => 7, 'carry_capacity_kgs' => 24000, 'carry_capacity_tons' => 24, 'is_active' => true, 'display_order' => 22],
            ['category_id' => 1, 'model_name' => 'MXL Taurus 25MT', 'vehicle_type_desc' => 'TAURAS', 'body_length' => 24, 'body_width' => 7, 'body_height' => 7, 'carry_capacity_kgs' => 25000, 'carry_capacity_tons' => 25, 'is_active' => true, 'display_order' => 23],
            ['category_id' => 1, 'model_name' => '40\' Trailer 27MT', 'vehicle_type_desc' => 'TRAILOR', 'body_length' => 40, 'body_width' => 8, 'body_height' => 7, 'carry_capacity_kgs' => 27000, 'carry_capacity_tons' => 27, 'is_active' => true, 'display_order' => 24],
            ['category_id' => 1, 'model_name' => '40\' Trailer 32MT', 'vehicle_type_desc' => 'TRAILOR', 'body_length' => 40, 'body_width' => 8, 'body_height' => 7, 'carry_capacity_kgs' => 32000, 'carry_capacity_tons' => 32, 'is_active' => true, 'display_order' => 25],
            ['category_id' => 1, 'model_name' => 'SIDE OPEN Taurus 16 MT', 'vehicle_type_desc' => 'OTL', 'body_length' => 22, 'body_width' => 7, 'body_height' => 7, 'carry_capacity_kgs' => 16000, 'carry_capacity_tons' => 16, 'is_active' => true, 'display_order' => 26],
            ['category_id' => 1, 'model_name' => 'SIDE OPEN TRUCK 9 MT', 'vehicle_type_desc' => 'OTL', 'body_length' => 18, 'body_width' => 7, 'body_height' => 7, 'carry_capacity_kgs' => 9000, 'carry_capacity_tons' => 9, 'is_active' => true, 'display_order' => 27],
            ['category_id' => 1, 'model_name' => '20 Trailer 20MT', 'vehicle_type_desc' => 'TRAILOR', 'body_length' => 20, 'body_width' => 8, 'body_height' => 7, 'carry_capacity_kgs' => 20000, 'carry_capacity_tons' => 20, 'is_active' => true, 'display_order' => 28],
            ['category_id' => 1, 'model_name' => '20 Trailer 24MT', 'vehicle_type_desc' => 'TRAILOR', 'body_length' => 20, 'body_width' => 8, 'body_height' => 7, 'carry_capacity_kgs' => 24000, 'carry_capacity_tons' => 24, 'is_active' => true, 'display_order' => 29],
            ['category_id' => 1, 'model_name' => '20 Trailer 27MT', 'vehicle_type_desc' => 'TRAILOR', 'body_length' => 20, 'body_width' => 8, 'body_height' => 7, 'carry_capacity_kgs' => 27000, 'carry_capacity_tons' => 27, 'is_active' => true, 'display_order' => 30],
            ['category_id' => 1, 'model_name' => '20 Trailer 32MT', 'vehicle_type_desc' => 'TRAILOR', 'body_length' => 20, 'body_width' => 8, 'body_height' => 7, 'carry_capacity_kgs' => 32000, 'carry_capacity_tons' => 32, 'is_active' => true, 'display_order' => 31],
            ['category_id' => 1, 'model_name' => '20 Trailer 22MT', 'vehicle_type_desc' => 'TRAILOR', 'body_length' => 20, 'body_width' => 8, 'body_height' => 7, 'carry_capacity_kgs' => 22000, 'carry_capacity_tons' => 22, 'is_active' => true, 'display_order' => 32],
            ['category_id' => 1, 'model_name' => 'Low Bed Trailer 22 MT', 'vehicle_type_desc' => 'TRAILOR', 'body_length' => 40, 'body_width' => 9, 'body_height' => 14, 'carry_capacity_kgs' => 22000, 'carry_capacity_tons' => 22, 'is_active' => true, 'display_order' => 33],
            ['category_id' => 1, 'model_name' => 'Low Bed Trailer 27 MT', 'vehicle_type_desc' => 'TRAILOR', 'body_length' => 40, 'body_width' => 9, 'body_height' => 14, 'carry_capacity_kgs' => 27000, 'carry_capacity_tons' => 27, 'is_active' => true, 'display_order' => 34],
            ['category_id' => 1, 'model_name' => 'Low Bed Trailer 32 MT', 'vehicle_type_desc' => 'TRAILOR', 'body_length' => 40, 'body_width' => 9, 'body_height' => 14, 'carry_capacity_kgs' => 32000, 'carry_capacity_tons' => 32, 'is_active' => true, 'display_order' => 35],
            ['category_id' => 1, 'model_name' => 'Hydraukic Axles 50 MT', 'vehicle_type_desc' => 'Hydraulic Axles', 'body_length' => 40, 'body_width' => 10, 'body_height' => 14, 'carry_capacity_kgs' => 50000, 'carry_capacity_tons' => 50, 'is_active' => true, 'display_order' => 36],
            ['category_id' => 1, 'model_name' => 'Hydraukic Axles 75 MT', 'vehicle_type_desc' => 'Hydraulic Axles', 'body_length' => 40, 'body_width' => 10, 'body_height' => 14, 'carry_capacity_kgs' => 80000, 'carry_capacity_tons' => 80, 'is_active' => true, 'display_order' => 37],
        ];

        // ✅ Insert Vehicle Models - CONTAINER (Category ID: 2)
        $containerModels = [
            ['category_id' => 2, 'model_name' => '32\' Container 7MT', 'vehicle_type_desc' => 'CONTAINER', 'body_length' => 32, 'body_width' => 8, 'body_height' => 8, 'carry_capacity_kgs' => 7000, 'carry_capacity_tons' => 7, 'is_active' => true, 'display_order' => 1],
            ['category_id' => 2, 'model_name' => '32\' HQ Container 7MT', 'vehicle_type_desc' => 'CONTAINER', 'body_length' => 32, 'body_width' => 8, 'body_height' => 9.6, 'carry_capacity_kgs' => 7000, 'carry_capacity_tons' => 7, 'is_active' => true, 'display_order' => 2],
            ['category_id' => 2, 'model_name' => '32\' Container 9MT', 'vehicle_type_desc' => 'CONTAINER', 'body_length' => 32, 'body_width' => 8, 'body_height' => 8, 'carry_capacity_kgs' => 9000, 'carry_capacity_tons' => 9, 'is_active' => true, 'display_order' => 3],
            ['category_id' => 2, 'model_name' => '32\' HQ Container 9MT', 'vehicle_type_desc' => 'CONTAINER', 'body_length' => 32, 'body_width' => 8, 'body_height' => 9.6, 'carry_capacity_kgs' => 9000, 'carry_capacity_tons' => 9, 'is_active' => true, 'display_order' => 4],
            ['category_id' => 2, 'model_name' => '32\' Container 15MT', 'vehicle_type_desc' => 'CONTAINER', 'body_length' => 32, 'body_width' => 8, 'body_height' => 8, 'carry_capacity_kgs' => 15000, 'carry_capacity_tons' => 15, 'is_active' => true, 'display_order' => 5],
            ['category_id' => 2, 'model_name' => '32\' HQ Container 15MT', 'vehicle_type_desc' => 'CONTAINER', 'body_length' => 32, 'body_width' => 8, 'body_height' => 9.6, 'carry_capacity_kgs' => 15000, 'carry_capacity_tons' => 15, 'is_active' => true, 'display_order' => 6],
            ['category_id' => 2, 'model_name' => '32\' Container 18MT', 'vehicle_type_desc' => 'CONTAINER', 'body_length' => 32, 'body_width' => 8, 'body_height' => 8, 'carry_capacity_kgs' => 18000, 'carry_capacity_tons' => 18, 'is_active' => true, 'display_order' => 7],
            ['category_id' => 2, 'model_name' => '32\' HQ Container 18MT', 'vehicle_type_desc' => 'CONTAINER', 'body_length' => 32, 'body_width' => 8, 'body_height' => 9.6, 'carry_capacity_kgs' => 18000, 'carry_capacity_tons' => 18, 'is_active' => true, 'display_order' => 8],
            ['category_id' => 2, 'model_name' => '20 Container 6.5 MT', 'vehicle_type_desc' => 'CONTAINER', 'body_length' => 19.5, 'body_width' => 7.5, 'body_height' => 7.5, 'carry_capacity_kgs' => 6500, 'carry_capacity_tons' => 6.5, 'is_active' => true, 'display_order' => 9],
            ['category_id' => 2, 'model_name' => '24 Container 7 MT', 'vehicle_type_desc' => 'CONTAINER', 'body_length' => 24, 'body_width' => 8, 'body_height' => 8, 'carry_capacity_kgs' => 7000, 'carry_capacity_tons' => 7, 'is_active' => true, 'display_order' => 10],
            ['category_id' => 2, 'model_name' => '28 Container 7 MT', 'vehicle_type_desc' => 'CONTAINER', 'body_length' => 28, 'body_width' => 8, 'body_height' => 8, 'carry_capacity_kgs' => 7000, 'carry_capacity_tons' => 7, 'is_active' => true, 'display_order' => 11],
        ];

        foreach ($openTruckModels as $model) {
            $model['created_at'] = now();
            $model['updated_at'] = now();
        }

        foreach ($containerModels as $model) {
            $model['created_at'] = now();
            $model['updated_at'] = now();
        }

        DB::table('vehicle_models')->insert(array_merge($openTruckModels, $containerModels));
    }
}
