<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('vehicle_models', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained('vehicle_categories')->onDelete('cascade');
            $table->string('model_name'); // '3 WHEELER 500 KG', 'PICK UP 800 KG', etc.
            $table->string('vehicle_type_desc'); // '3 Wheeler', 'PICK-UP', 'LCV', etc.
            $table->decimal('body_length', 8, 2)->nullable(); // 5, 8, 9.6, etc.
            $table->decimal('body_width', 8, 2)->nullable(); // 4, 6, 6.6, etc.
            $table->decimal('body_height', 8, 2)->nullable(); // 4, 6, 7, etc.
            $table->integer('carry_capacity_kgs')->nullable(); // 500, 800, 1000, etc.
            $table->decimal('carry_capacity_tons', 8, 2)->nullable(); // 0.5, 0.8, 1, etc.
            $table->boolean('is_active')->default(true);
            $table->integer('display_order')->default(0);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('vehicle_models');
    }
};
