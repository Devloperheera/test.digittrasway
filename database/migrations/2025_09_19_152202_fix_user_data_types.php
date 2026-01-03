<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Make sure login_count is integer
            $table->integer('login_count')->default(0)->change();

            // Make sure other numeric fields are proper type
            $table->integer('otp_attempts')->default(0)->change();
            $table->integer('otp_resend_count')->default(0)->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('login_count')->nullable()->change();
            $table->string('otp_attempts')->nullable()->change();
            $table->string('otp_resend_count')->nullable()->change();
        });
    }
};
