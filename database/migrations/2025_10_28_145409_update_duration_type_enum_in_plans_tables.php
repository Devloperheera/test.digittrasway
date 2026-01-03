<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Update vendor_plans table
        DB::statement("
            ALTER TABLE vendor_plans
            MODIFY COLUMN duration_type
            ENUM('daily', 'weekly', 'monthly', 'quarterly', 'half_yearly', 'yearly')
            NOT NULL
        ");

        // Update plans table (if exists)
        if (DB::getSchemaBuilder()->hasTable('plans')) {
            DB::statement("
                ALTER TABLE plans
                MODIFY COLUMN duration_type
                ENUM('daily', 'weekly', 'monthly', 'quarterly', 'half_yearly', 'yearly')
                NOT NULL
            ");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert vendor_plans table
        DB::statement("
            ALTER TABLE vendor_plans
            MODIFY COLUMN duration_type
            ENUM('daily', 'weekly', 'monthly', 'yearly')
            NOT NULL
        ");

        // Revert plans table (if exists)
        if (DB::getSchemaBuilder()->hasTable('plans')) {
            DB::statement("
                ALTER TABLE plans
                MODIFY COLUMN duration_type
                ENUM('daily', 'weekly', 'monthly', 'yearly')
                NOT NULL
            ");
        }
    }
};
