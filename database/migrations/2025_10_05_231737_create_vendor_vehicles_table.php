<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('vendor_vehicles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained('vendors')->onDelete('cascade');

            // Vehicle Selection (from existing tables)
            $table->foreignId('vehicle_category_id')->constrained('vehicle_categories')->onDelete('cascade');
            $table->foreignId('vehicle_model_id')->constrained('vehicle_models')->onDelete('cascade');

            // Basic Vehicle Info
            $table->string('vehicle_registration_number')->unique();
            $table->string('vehicle_name')->nullable(); // e.g., "My Truck 1"
            $table->year('manufacturing_year')->nullable();
            $table->string('vehicle_color')->nullable();
            $table->string('chassis_number')->nullable();
            $table->string('engine_number')->nullable();

            // Owner/Document Details
            $table->string('owner_name'); // From RC
            $table->string('rc_number')->unique();
            $table->string('insurance_number')->nullable();
            $table->date('insurance_expiry')->nullable();
            $table->date('fitness_expiry')->nullable();
            $table->date('permit_expiry')->nullable();

            // Document Verification (SurePass API)
            $table->boolean('rc_verified')->default(false);
            $table->json('rc_verified_data')->nullable();
            $table->timestamp('rc_verification_date')->nullable();
            $table->string('rc_verification_status')->nullable(); // success, failed, pending

            // Driver License (if fleet owner is also driver)
            $table->string('dl_number')->nullable();
            $table->boolean('dl_verified')->default(false);
            $table->json('dl_verified_data')->nullable();
            $table->timestamp('dl_verification_date')->nullable();

            // Document Images
            $table->string('vehicle_image')->nullable();
            $table->string('rc_front_image')->nullable();
            $table->string('rc_back_image')->nullable();
            $table->string('insurance_image')->nullable();
            $table->string('fitness_certificate')->nullable();
            $table->string('permit_image')->nullable();
            $table->string('dl_image')->nullable();

            // Vehicle Status
            $table->enum('status', ['pending', 'under_review', 'approved', 'rejected', 'active', 'inactive', 'suspended'])->default('pending');
            $table->text('rejection_reason')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null'); // Admin user

            // Listing Status
            $table->boolean('is_listed')->default(false);
            $table->timestamp('listed_at')->nullable();
            $table->boolean('is_available')->default(true);

            // Availability & Location
            $table->enum('availability_status', ['available', 'on_trip', 'maintenance', 'offline'])->default('offline');
            $table->decimal('current_latitude', 10, 7)->nullable();
            $table->decimal('current_longitude', 10, 7)->nullable();
            $table->string('current_location')->nullable();
            $table->timestamp('last_location_update')->nullable();

            // Booking Management
            $table->boolean('can_accept_bookings')->default(false);
            $table->integer('completed_trips')->default(0);
            $table->integer('cancelled_trips')->default(0);
            $table->decimal('average_rating', 3, 2)->default(0.00);
            $table->integer('total_ratings')->default(0);

            // Additional Info
            $table->text('features')->nullable(); // JSON or comma-separated
            $table->text('description')->nullable();
            $table->boolean('has_gps')->default(false);
            $table->boolean('has_insurance')->default(false);
            $table->integer('display_order')->default(0);

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['vendor_id', 'status']);
            $table->index(['vehicle_registration_number']);
            $table->index(['is_listed', 'is_available']);
            $table->index('availability_status');
        });
    }

    public function down()
    {
        Schema::dropIfExists('vendor_vehicles');
    }
};
