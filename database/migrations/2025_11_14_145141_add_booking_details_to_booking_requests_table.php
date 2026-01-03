<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddBookingDetailsToBookingRequestsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('booking_requests', function (Blueprint $table) {
            // Check if columns don't exist before adding
            if (!Schema::hasColumn('booking_requests', 'pickup_datetime')) {
                $table->timestamp('pickup_datetime')->nullable()->after('booking_id');
            }
            if (!Schema::hasColumn('booking_requests', 'pickup_address')) {
                $table->string('pickup_address', 500)->nullable()->after('pickup_datetime');
            }
            if (!Schema::hasColumn('booking_requests', 'drop_address')) {
                $table->string('drop_address', 500)->nullable()->after('pickup_address');
            }
            if (!Schema::hasColumn('booking_requests', 'distance_km')) {
                $table->decimal('distance_km', 10, 2)->nullable()->after('drop_address');
            }
            if (!Schema::hasColumn('booking_requests', 'final_amount')) {
                $table->decimal('final_amount', 10, 2)->nullable()->after('distance_km');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('booking_requests', function (Blueprint $table) {
            $table->dropColumn([
                'pickup_datetime',
                'pickup_address',
                'drop_address',
                'distance_km',
                'final_amount'
            ]);
        });
    }
}
