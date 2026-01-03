<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class UpdateVendorAvailabilityStatusEnum extends Migration
{
    public function up()
    {
        // Change ENUM values
        DB::statement("ALTER TABLE vendors MODIFY COLUMN availability_status ENUM('in', 'out', 'requested', 'booked') DEFAULT 'in'");
    }

    public function down()
    {
        // Revert back to original
        DB::statement("ALTER TABLE vendors MODIFY COLUMN availability_status ENUM('in', 'out') DEFAULT 'in'");
    }
}
