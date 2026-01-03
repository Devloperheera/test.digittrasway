<?php
// database/migrations/2025_01_01_000004_create_truck_specifications_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('truck_specifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('truck_type_id')->constrained('truck_types')->onDelete('cascade'); // ✅ Explicit table name
            $table->decimal('length', 8, 2);
            $table->string('length_unit', 10)->default('ft');
            $table->integer('tyre_count');
            $table->decimal('height', 8, 2)->nullable();
            $table->string('height_unit', 10)->default('ft');
            $table->decimal('max_weight', 10, 2)->default(15.0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('truck_specifications');
    }
};
