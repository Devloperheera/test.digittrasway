<?php
// database/migrations/2025_01_01_000004_create_vendor_vehicle_types_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('vendor_vehicle_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');                    // Mini Truck, Pickup, Truck, etc.
            $table->text('description')->nullable();
            $table->json('available_lengths')->nullable(); // [8, 16, 20] ft
            $table->json('available_capacities')->nullable(); // [20, 30] ton
            $table->json('tyre_variants')->nullable();  // [5, 10] tyre
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('vendor_vehicle_types');
    }
};
