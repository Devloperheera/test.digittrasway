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
        // For MySQL: Modify ENUM to add new values
        DB::statement("ALTER TABLE vendor_plans MODIFY COLUMN duration_type ENUM('daily', 'weekly', 'monthly', 'quarterly', 'half_yearly', 'yearly') NOT NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert back to old ENUM values
        DB::statement("ALTER TABLE vendor_plans MODIFY COLUMN duration_type ENUM('daily', 'weekly', 'monthly', 'yearly') NOT NULL");
    }
};
