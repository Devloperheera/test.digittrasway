<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
{
    Schema::table('users', function (Blueprint $table) {
        $table->boolean('aadhaar_verified')->default(false);
        $table->boolean('pan_verified')->default(false);
        $table->boolean('rc_verified')->default(false);
        $table->text('verified_dob')->nullable();
        $table->string('verified_gender')->nullable();
        $table->text('verified_address')->nullable();
        $table->string('verified_pincode')->nullable();
        $table->string('verified_state')->nullable();
        $table->timestamp('verification_completed_at')->nullable();
    });
}

public function down()
{
    Schema::table('users', function (Blueprint $table) {
        $table->dropColumn([
            'aadhaar_verified', 'pan_verified', 'rc_verified',
            'verified_dob', 'verified_gender', 'verified_address',
            'verified_pincode', 'verified_state', 'verification_completed_at'
        ]);
    });
}

};
