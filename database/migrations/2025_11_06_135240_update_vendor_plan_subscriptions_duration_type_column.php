<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('vendor_plan_subscriptions', function (Blueprint $table) {
            // ✅ INCREASE COLUMN SIZE
            $table->string('duration_type', 20)->nullable()->change(); // Change से VARCHAR(20) बना दो
        });
    }

    public function down()
    {
        Schema::table('vendor_plan_subscriptions', function (Blueprint $table) {
            $table->string('duration_type', 10)->nullable()->change();
        });
    }
};
