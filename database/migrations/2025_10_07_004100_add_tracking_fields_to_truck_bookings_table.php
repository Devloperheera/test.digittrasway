<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('truck_bookings', function (Blueprint $table) {
            // ✅ Current vendor location (live tracking)
            $table->decimal('current_vendor_latitude', 10, 8)->nullable()->after('drop_longitude');
            $table->decimal('current_vendor_longitude', 11, 8)->nullable()->after('current_vendor_latitude');
            $table->string('current_vendor_location')->nullable()->after('current_vendor_longitude');
            $table->timestamp('location_updated_at')->nullable()->after('current_vendor_location');

            // ✅ Route tracking
            $table->decimal('distance_covered_km', 10, 2)->default(0)->after('distance_km');
            $table->decimal('distance_remaining_km', 10, 2)->nullable()->after('distance_covered_km');
            $table->integer('estimated_time_remaining_mins')->nullable()->after('distance_remaining_km');

            // ✅ Speed & ETA
            $table->decimal('current_speed_kmph', 8, 2)->nullable()->after('estimated_time_remaining_mins');
            $table->timestamp('estimated_arrival_time')->nullable()->after('current_speed_kmph');

            // ✅ Tracking history (JSON array of locations)
            $table->json('location_history')->nullable()->after('estimated_arrival_time');
        });
    }

    public function down()
    {
        Schema::table('truck_bookings', function (Blueprint $table) {
            $table->dropColumn([
                'current_vendor_latitude',
                'current_vendor_longitude',
                'current_vendor_location',
                'location_updated_at',
                'distance_covered_km',
                'distance_remaining_km',
                'estimated_time_remaining_mins',
                'current_speed_kmph',
                'estimated_arrival_time',
                'location_history'
            ]);
        });
    }
};
