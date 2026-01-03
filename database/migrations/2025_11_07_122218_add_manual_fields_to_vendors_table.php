<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('vendors', function (Blueprint $table) {
            $table->boolean('aadhar_manual')->default(false)->after('aadhar_back');
            $table->boolean('rc_manual')->default(false)->after('rc_verified');
            $table->boolean('dl_manual')->default(false)->after('dl_verified');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('vendors', function (Blueprint $table) {
            $table->dropColumn(['aadhar_manual', 'rc_manual', 'dl_manual']);
        });
    }
};
