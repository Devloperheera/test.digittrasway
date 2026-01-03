<?php
// database/migrations/2025_01_01_000005_create_truck_bookings_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('truck_bookings', function (Blueprint $table) {
            $table->id();
            $table->string('booking_id')->unique();

            // ✅ Check if users table exists, otherwise use vendors
            if (Schema::hasTable('users')) {
                $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            } else {
                $table->foreignId('user_id')->constrained('vendors')->onDelete('cascade');
            }

            // Location Details
            $table->string('pickup_address');
            $table->decimal('pickup_latitude', 10, 8);
            $table->decimal('pickup_longitude', 11, 8);
            $table->string('drop_address');
            $table->decimal('drop_latitude', 10, 8);
            $table->decimal('drop_longitude', 11, 8);

            // Material & Truck Details - ✅ Explicit table references
            $table->foreignId('material_id')->constrained('materials')->onDelete('cascade');
            $table->string('material_name');
            $table->foreignId('truck_type_id')->constrained('truck_types')->onDelete('cascade');
            $table->string('truck_type_name');
            $table->foreignId('truck_specification_id')->constrained('truck_specifications')->onDelete('cascade');
            $table->decimal('material_weight', 10, 2);
            $table->decimal('truck_length', 8, 2);
            $table->integer('tyre_count');
            $table->decimal('truck_height', 8, 2)->nullable();

            // Distance & Pricing
            $table->decimal('distance_km', 10, 2);
            $table->decimal('price_per_km', 10, 2);
            $table->decimal('estimated_price', 10, 2);
            $table->decimal('final_price', 10, 2)->nullable();
            $table->json('price_breakdown')->nullable();

            // Status
            $table->enum('status', ['pending', 'confirmed', 'in_transit', 'delivered', 'cancelled'])->default('pending');
            $table->datetime('pickup_datetime')->nullable();
            $table->datetime('drop_datetime')->nullable();
            $table->text('special_instructions')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index('booking_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('truck_bookings');
    }
};
