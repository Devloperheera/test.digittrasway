<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('user_types', function (Blueprint $table) {
            $table->id();
            $table->string('type_key')->unique(); // 'fleet_owner' or 'professional_driver'
            $table->string('title'); // 'Fleet Owner' or 'Professional Driver'
            $table->string('subtitle')->nullable(); // 'BUSINESS & OPERATIONS' or 'ON THE ROAD'
            $table->text('description')->nullable();
            $table->string('icon')->nullable(); // Emoji or image path
            $table->json('features')->nullable(); // Array of features
            $table->boolean('is_active')->default(true);
            $table->integer('display_order')->default(0);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('user_types');
    }
};
