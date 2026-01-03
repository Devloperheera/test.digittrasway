<?php
// database/migrations/2025_01_01_000001_create_pricing_rules_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('pricing_rules', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->decimal('price_per_km', 10, 2);
            $table->decimal('distance_from', 8, 2)->default(0);
            $table->decimal('distance_to', 8, 2)->nullable();
            $table->decimal('minimum_charge', 10, 2)->default(0);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('pricing_rules');
    }
};
