<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('referral_emp_id')->nullable()->after('contact_number');
            $table->unsignedBigInteger('referred_by_employee_id')->nullable()->after('referral_emp_id');
            $table->timestamp('app_installed_at')->nullable()->after('referred_by_employee_id');

            $table->foreign('referred_by_employee_id')
                  ->references('id')
                  ->on('employees')
                  ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['referred_by_employee_id']);
            $table->dropColumn(['referral_emp_id', 'referred_by_employee_id', 'app_installed_at']);
        });
    }
};
