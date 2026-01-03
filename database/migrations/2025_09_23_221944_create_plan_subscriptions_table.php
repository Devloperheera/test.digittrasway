<?php
// database/migrations/2025_01_01_000002_create_plan_subscriptions_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('plan_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id'); // vendor_id from token
            $table->unsignedBigInteger('plan_id')->nullable(); // Reference to plans table
            $table->string('plan_name');
            $table->decimal('price', 10, 2);
            $table->enum('duration_type', ['daily', 'weekly', 'monthly', 'yearly']);
            $table->json('selected_features')->nullable(); // Features at time of purchase
            $table->enum('status', ['active', 'expired', 'cancelled'])->default('active');
            $table->datetime('expires_at')->nullable();
            $table->timestamps();

            $table->index('user_id');
            $table->index('plan_id');
            $table->index('duration_type');
            $table->index('status');

            $table->foreign('plan_id')->references('id')->on('plans')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::dropIfExists('plan_subscriptions');
    }
};
