<?php
// database/migrations/xxxx_xx_xx_add_availability_fields_to_vendors_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('vendors', function (Blueprint $table) {
            // ✅ Availability Status Fields
            $table->enum('availability_status', ['in', 'out'])->default('out')->after('vehicle_listed_at');
            $table->datetime('last_in_time')->nullable()->after('availability_status');
            $table->datetime('last_out_time')->nullable()->after('last_in_time');
            $table->decimal('current_latitude', 10, 8)->nullable()->after('last_out_time');
            $table->decimal('current_longitude', 11, 8)->nullable()->after('current_latitude');
            $table->string('current_location')->nullable()->after('current_longitude');
            $table->boolean('is_available_for_booking')->default(false)->after('current_location');

            // Index for faster queries
            $table->index('availability_status');
            $table->index('is_available_for_booking');
        });
    }

    public function down()
    {
        Schema::table('vendors', function (Blueprint $table) {
            $table->dropIndex(['availability_status']);
            $table->dropIndex(['is_available_for_booking']);

            $table->dropColumn([
                'availability_status',
                'last_in_time',
                'last_out_time',
                'current_latitude',
                'current_longitude',
                'current_location',
                'is_available_for_booking'
            ]);
        });
    }
};
