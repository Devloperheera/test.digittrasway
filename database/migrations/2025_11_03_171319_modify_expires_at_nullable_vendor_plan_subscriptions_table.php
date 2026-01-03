<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('vendor_plan_subscriptions', function (Blueprint $table) {
            // Make expires_at nullable
            $table->dateTime('expires_at')->nullable()->change();

            // Also make plan_name nullable (if not already)
            $table->string('plan_name')->nullable()->change();

            // Make status column longer (if needed)
            $table->string('status', 50)->nullable()->change();

            // Make subscription_status column longer
            $table->string('subscription_status', 50)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vendor_plan_subscriptions', function (Blueprint $table) {
            // Revert expires_at
            $table->dateTime('expires_at')->nullable(false)->change();

            // Revert plan_name
            $table->string('plan_name')->nullable(false)->change();

            // Revert status
            $table->string('status', 20)->nullable()->change();

            // Revert subscription_status
            $table->string('subscription_status', 20)->nullable()->change();
        });
    }
};
