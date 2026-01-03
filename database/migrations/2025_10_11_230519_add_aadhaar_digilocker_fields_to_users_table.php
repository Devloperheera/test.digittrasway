<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            // ✅ Add these 3 new fields (aadhaar_verified already exists)
            $table->string('aadhaar_digilocker_client_id')->nullable()->after('aadhar_number');
            $table->timestamp('aadhaar_verification_date')->nullable()->after('aadhaar_verified');
            $table->json('aadhaar_verified_data')->nullable()->after('aadhaar_verification_date');
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'aadhaar_digilocker_client_id',
                'aadhaar_verification_date',
                'aadhaar_verified_data'
            ]);
        });
    }
};
