<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up()
    {
        Schema::table('truck_bookings', function (Blueprint $table) {
            // ✅ Vendor Assignment
            $table->foreignId('assigned_vendor_id')->nullable()->after('user_id')
                  ->constrained('vendors')->onDelete('set null');
            $table->string('vendor_name')->nullable();
            $table->string('vendor_vehicle_number')->nullable();
            $table->string('vendor_contact')->nullable();

            // ✅ Vehicle Model (from vehicle_models table)
            $table->foreignId('vehicle_model_id')->nullable()->after('truck_specification_id')
                  ->constrained('vehicle_models')->onDelete('set null');

            // ✅ Payment Details
            $table->enum('payment_method', ['pickup', 'drop'])->default('pickup')->after('estimated_price');
            $table->decimal('adjusted_price', 10, 2)->nullable()->comment('User can edit price');
            $table->decimal('final_amount', 10, 2)->nullable()->comment('Final confirmed amount');
            $table->enum('payment_status', ['pending', 'paid', 'failed'])->default('pending');
            $table->timestamp('payment_completed_at')->nullable();

            // ✅ Set default status if not set
            if (!Schema::hasColumn('truck_bookings', 'status')) {
                $table->string('status')->default('pending');
            } else {
                DB::statement("UPDATE truck_bookings SET status = 'pending' WHERE status IS NULL");
            }

            // ✅ Booking Lifecycle Timestamps
            $table->timestamp('vendor_accepted_at')->nullable();
            $table->timestamp('trip_started_at')->nullable();
            $table->timestamp('trip_completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancellation_reason')->nullable();
        });
    }

    public function down()
    {
        Schema::table('truck_bookings', function (Blueprint $table) {
            $table->dropForeign(['assigned_vendor_id']);
            $table->dropForeign(['vehicle_model_id']);
            $table->dropColumn([
                'assigned_vendor_id', 'vendor_name', 'vendor_vehicle_number', 'vendor_contact',
                'vehicle_model_id', 'payment_method', 'adjusted_price', 'final_amount',
                'payment_status', 'payment_completed_at', 'vendor_accepted_at',
                'trip_started_at', 'trip_completed_at', 'cancelled_at', 'cancellation_reason'
            ]);
        });
    }
};
