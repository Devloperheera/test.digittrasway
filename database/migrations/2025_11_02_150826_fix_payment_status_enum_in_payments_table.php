<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            // Change ENUM values
            $table->enum('payment_status', [
                'created',
                'pending',
                'authorized',
                'captured',
                'paid',
                'completed',
                'failed',
                'refunded',
                'processing'
            ])->default('created')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            // Revert to original values
            $table->enum('payment_status', [
                'created',
                'pending',
                'failed'
            ])->default('created')->change();
        });
    }
};
