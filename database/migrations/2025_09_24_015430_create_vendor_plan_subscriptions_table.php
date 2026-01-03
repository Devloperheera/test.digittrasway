<?php
// database/migrations/2025_01_01_000003_create_vendor_plan_subscriptions_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('vendor_plan_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained('vendors')->onDelete('cascade');
            $table->foreignId('vendor_plan_id')->constrained('vendor_plans')->onDelete('cascade');
            $table->string('plan_name');
            $table->decimal('price_paid', 10, 2);
            $table->enum('duration_type', ['daily', 'weekly', 'monthly', 'yearly']);
            $table->datetime('starts_at');
            $table->datetime('expires_at');
            $table->enum('status', ['active', 'expired', 'cancelled'])->default('active');
            $table->json('plan_features')->nullable();
            $table->timestamps();

            $table->index(['vendor_id', 'status']);
            $table->index('expires_at');
        });
    }

    public function down()
    {
        Schema::dropIfExists('vendor_plan_subscriptions');
    }
};
