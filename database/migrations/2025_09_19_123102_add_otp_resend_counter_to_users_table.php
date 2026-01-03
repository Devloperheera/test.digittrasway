<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->integer('otp_resend_count')->default(0)->after('otp_attempts');
            $table->index(['contact_number', 'updated_at']); // For daily limit check
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['contact_number', 'updated_at']);
            $table->dropColumn('otp_resend_count');
        });
    }
};
