<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('vendors', function (Blueprint $table) {
            // DL (Driving License) Fields
            $table->string('dl_number')->nullable()->after('rc_verified');
            $table->string('dl_image')->nullable()->after('dl_number');
            $table->json('dl_verified_data')->nullable()->after('dl_image');
            $table->timestamp('dl_verification_date')->nullable()->after('dl_verified_data');
            $table->boolean('dl_verified')->default(false)->after('dl_verification_date');
        });
    }

    public function down()
    {
        Schema::table('vendors', function (Blueprint $table) {
            $table->dropColumn([
                'dl_number',
                'dl_image',
                'dl_verified_data',
                'dl_verification_date',
                'dl_verified'
            ]);
        });
    }
};
