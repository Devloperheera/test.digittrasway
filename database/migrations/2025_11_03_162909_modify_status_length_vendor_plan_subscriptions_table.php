<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vendor_plan_subscriptions', function (Blueprint $table) {
            // Increase status column length
            $table->string('status', 50)->nullable()->change();
            $table->string('subscription_status', 50)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('vendor_plan_subscriptions', function (Blueprint $table) {
            $table->string('status', 20)->nullable()->change();
            $table->string('subscription_status', 20)->nullable()->change();
        });
    }
};
