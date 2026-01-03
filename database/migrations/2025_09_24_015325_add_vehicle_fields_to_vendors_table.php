<?php
// database/migrations/2025_01_01_000001_add_vehicle_fields_to_vendors_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('vendors', function (Blueprint $table) {
            // ✅ Vehicle Information Fields (from image)
            $table->string('vehicle_registration_number')->nullable();
            $table->string('vehicle_type')->nullable();           // Mini Truck, Pickup, etc.
            $table->string('vehicle_brand_model')->nullable();     // Tata Ace, etc.
            $table->decimal('vehicle_length', 8, 2)->nullable();   // 8ft, 16ft, 20ft
            $table->string('vehicle_length_unit', 10)->default('ft');
            $table->integer('vehicle_tyre_count')->nullable();     // 5 tyre, 10 tyre
            $table->decimal('weight_capacity', 10, 2)->nullable(); // 20 ton, 30 ton
            $table->string('weight_unit', 10)->default('ton');

            // ✅ Document Upload Fields (from image)
            $table->string('vehicle_image')->nullable();           // Vehicle photo
            $table->string('vehicle_rc_document')->nullable();     // RC document
            $table->string('vehicle_insurance_document')->nullable(); // Insurance document

            // ✅ Vehicle Status
            $table->boolean('vehicle_listed')->default(false);    // Is vehicle listed?
            $table->enum('vehicle_status', ['pending', 'approved', 'rejected', 'active', 'inactive'])->default('pending');
            $table->text('vehicle_rejection_reason')->nullable();
            $table->datetime('vehicle_approved_at')->nullable();
            $table->datetime('vehicle_listed_at')->nullable();
        });
    }

    public function down()
    {
        Schema::table('vendors', function (Blueprint $table) {
            $table->dropColumn([
                'vehicle_registration_number', 'vehicle_type', 'vehicle_brand_model',
                'vehicle_length', 'vehicle_length_unit', 'vehicle_tyre_count',
                'weight_capacity', 'weight_unit', 'vehicle_image', 'vehicle_rc_document',
                'vehicle_insurance_document', 'vehicle_listed', 'vehicle_status',
                'vehicle_rejection_reason', 'vehicle_approved_at', 'vehicle_listed_at'
            ]);
        });
    }
};
