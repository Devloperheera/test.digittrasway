<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('booking_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained('truck_bookings')->onDelete('cascade');
            $table->foreignId('vendor_id')->constrained('vendors')->onDelete('cascade');
            $table->enum('status', ['pending', 'accepted', 'rejected', 'expired'])->default('pending');
            $table->timestamp('sent_at')->nullable(); // ✅ FIXED
            $table->timestamp('expires_at')->nullable(); // ✅ FIXED
            $table->timestamp('responded_at')->nullable();
            $table->integer('sequence_number')->default(1);
            $table->timestamps();

            $table->index(['booking_id', 'status']);
            $table->index(['vendor_id', 'status']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('booking_requests');
    }
};
