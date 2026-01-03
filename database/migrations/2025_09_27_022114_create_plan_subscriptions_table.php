<?php
// database/migrations/xxxx_create_plan_subscriptions_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('plan_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('vendors')->onDelete('cascade');
            $table->foreignId('plan_id')->constrained('plans')->onDelete('cascade');
            $table->string('plan_name');
            $table->decimal('price_paid', 10, 2);
            $table->enum('duration_type', ['daily', 'weekly', 'monthly', 'yearly']);
            $table->datetime('starts_at');
            $table->datetime('expires_at');
            $table->enum('status', ['active', 'expired', 'cancelled'])->default('active');
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['plan_id', 'status']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('plan_subscriptions');
    }
};
