<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();

            // Step 1: Contact & OTP
            $table->string('contact_number')->unique();
            $table->string('otp')->nullable();
            $table->timestamp('otp_expires_at')->nullable();
            $table->integer('otp_attempts')->default(0);
            $table->timestamp('last_otp_sent_at')->nullable();
            $table->boolean('is_verified')->default(false);

            // Step 2: Personal details
            $table->string('name')->nullable();
            $table->string('email')->nullable()->unique();
            $table->date('dob')->nullable();
            $table->enum('gender', ['male', 'female', 'other'])->nullable();
            $table->string('emergency_contact')->nullable();

            // Aadhaar
            $table->string('aadhar_number')->nullable();
            $table->string('aadhar_front')->nullable();
            $table->string('aadhar_back')->nullable();

            // PAN
            $table->string('pan_number')->nullable();
            $table->string('pan_image')->nullable();

            // Address
            $table->text('full_address')->nullable();
            $table->string('state')->nullable();
            $table->string('city')->nullable();
            $table->string('pincode')->nullable();
            $table->string('country')->nullable();
            $table->boolean('same_address')->default(false);

            // Flags
            $table->boolean('declaration')->default(false);
            $table->boolean('is_completed')->default(false);

            // Bank Details
            $table->string('bank_name')->nullable();
            $table->string('account_number')->nullable();
            $table->string('ifsc')->nullable();
            $table->string('postal_code')->nullable();

            // JWT login related
            $table->string('password')->nullable();
            $table->rememberToken();

            $table->timestamps();

            // Indexes for performance
            $table->index(['contact_number', 'is_verified']);
            $table->index('otp_expires_at');
            $table->index('is_completed');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
