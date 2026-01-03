<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vendor_plan_subscriptions', function (Blueprint $table) {
            $table->string('plan_name')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('vendor_plan_subscriptions', function (Blueprint $table) {
            $table->string('plan_name')->nullable(false)->change();
        });
    }
};
