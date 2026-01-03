<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('plan_subscriptions', function (Blueprint $table) {
            $table->string('duration_type', 20)->change();
        });
    }

    public function down()
    {
        Schema::table('plan_subscriptions', function (Blueprint $table) {
            $table->string('duration_type', 7)->change();
        });
    }
};
