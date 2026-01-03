<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        // ✅ Update status enum to include 'searching_vendor'
        DB::statement("ALTER TABLE `truck_bookings` MODIFY `status` ENUM('pending', 'searching_vendor', 'confirmed', 'in_transit', 'completed', 'cancelled') NOT NULL DEFAULT 'pending'");
    }

    public function down()
    {
        // Remove 'searching_vendor' from enum
        DB::statement("ALTER TABLE `truck_bookings` MODIFY `status` ENUM('pending', 'confirmed', 'in_transit', 'completed', 'cancelled') NOT NULL DEFAULT 'pending'");
    }
};
