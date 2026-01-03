<?php
// database/migrations/2025_01_01_000001_create_plans_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2);
            $table->enum('duration_type', ['daily', 'weekly', 'monthly', 'yearly']);
            $table->json('features')->nullable(); // Plan features as JSON
            $table->boolean('is_popular')->default(false);
            $table->boolean('is_active')->default(true);
            $table->string('button_text')->default('Choose Plan');
            $table->string('button_color')->default('#4CAF50');
            $table->string('contact_info')->nullable(); // For custom plans
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('plans');
    }
};
