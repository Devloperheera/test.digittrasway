<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vendors', function (Blueprint $table) {
            $table->id();

            // OTP & Verification Fields
            $table->string('contact_number', 15)->unique();
            $table->string('otp', 10)->nullable();
            $table->timestamp('otp_expires_at')->nullable();
            $table->integer('otp_attempts')->default(0);
            $table->integer('otp_resend_count')->default(0);
            $table->timestamp('last_otp_sent_at')->nullable();
            $table->boolean('is_verified')->default(false);

            // Personal Information
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->date('dob')->nullable();
            $table->enum('gender', ['male', 'female', 'other'])->nullable();
            $table->string('emergency_contact', 15)->nullable();

            // Document Fields
            $table->string('aadhar_number', 12)->nullable();
            $table->string('aadhar_front')->nullable();
            $table->string('aadhar_back')->nullable();
            $table->string('pan_number', 10)->nullable();
            $table->string('pan_image')->nullable();

            // RC Document Fields ✅ New for vendors
            $table->string('rc_number', 20)->nullable();
            $table->string('rc_image')->nullable();

            // Address Information
            $table->text('full_address')->nullable();
            $table->string('state', 100)->nullable();
            $table->string('city', 100)->nullable();
            $table->string('pincode', 6)->nullable();
            $table->string('country', 100)->nullable();
            $table->boolean('same_address')->default(false);

            // Bank Details
            $table->string('bank_name')->nullable();
            $table->string('account_number', 20)->nullable();
            $table->string('ifsc', 11)->nullable();
            $table->string('postal_code', 10)->nullable();

            // Status Fields
            $table->boolean('declaration')->default(false);
            $table->boolean('is_completed')->default(false);

            // Authentication
            $table->string('password')->nullable();
            $table->rememberToken();

            // Login Tracking
            $table->timestamp('last_login_at')->nullable();
            $table->integer('login_count')->default(0);

            $table->timestamps();

            // Indexes
            $table->index(['contact_number', 'is_verified']);
            $table->index(['contact_number', 'last_otp_sent_at']);
            $table->index(['is_verified', 'is_completed']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendors');
    }
};
