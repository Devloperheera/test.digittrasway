<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            // Add RC verification data storage ✅
            $table->json('rc_verified_data')->nullable()->after('rc_image');
            $table->timestamp('rc_verification_date')->nullable()->after('rc_verified_data');
            $table->boolean('rc_verified')->default(false)->after('rc_verification_date');
        });
    }

    public function down(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            $table->dropColumn(['rc_verified_data', 'rc_verification_date', 'rc_verified']);
        });
    }
};
