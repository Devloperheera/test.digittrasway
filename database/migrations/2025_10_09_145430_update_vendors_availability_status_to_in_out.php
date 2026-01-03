<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Change availability_status to ENUM('in', 'out')
        DB::statement("ALTER TABLE vendors MODIFY COLUMN availability_status ENUM('in', 'out') DEFAULT 'in'");

        // Update all existing vendors to 'in'
        DB::table('vendors')->update(['availability_status' => 'in']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert back to original (if needed)
        DB::statement("ALTER TABLE vendors MODIFY COLUMN availability_status VARCHAR(255) NULL");
    }
};
