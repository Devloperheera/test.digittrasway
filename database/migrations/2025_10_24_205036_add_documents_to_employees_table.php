<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('employees', function (Blueprint $table) {
            // Document fields
            $table->string('aadhar_front')->nullable()->after('photo');
            $table->string('aadhar_back')->nullable()->after('aadhar_front');
            $table->string('pan_card')->nullable()->after('aadhar_back');
            $table->string('driving_license')->nullable()->after('pan_card');
            $table->string('address_proof')->nullable()->after('driving_license');

            // Aadhar number (optional)
            $table->string('aadhar_number')->nullable()->after('address_proof');
            $table->string('pan_number')->nullable()->after('aadhar_number');
            $table->string('dl_number')->nullable()->after('pan_number');
        });
    }

    public function down()
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn([
                'aadhar_front',
                'aadhar_back',
                'pan_card',
                'driving_license',
                'address_proof',
                'aadhar_number',
                'pan_number',
                'dl_number'
            ]);
        });
    }
};
