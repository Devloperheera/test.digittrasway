<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddMissingLoginColumnsToVendorsTable extends Migration
{
    public function up()
    {
        Schema::table('vendors', function (Blueprint $table) {
            // Add only missing columns
            $table->timestamp('last_login_attempt')->nullable();
            $table->timestamp('last_logout_at')->nullable();
        });
    }

    public function down()
    {
        Schema::table('vendors', function (Blueprint $table) {
            $table->dropColumn(['last_login_attempt', 'last_logout_at']);
        });
    }
}
