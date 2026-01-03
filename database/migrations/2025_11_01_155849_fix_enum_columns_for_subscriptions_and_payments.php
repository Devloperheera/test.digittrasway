<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
        // ✅ FIX plan_subscriptions TABLE
        // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
        DB::statement("ALTER TABLE `plan_subscriptions`
            MODIFY COLUMN `status` ENUM(
                'pending',
                'active',
                'inactive',
                'expired',
                'cancelled',
                'paused',
                'completed',
                'failed'
            ) DEFAULT 'pending'");

        DB::statement("ALTER TABLE `plan_subscriptions`
            MODIFY COLUMN `subscription_status` ENUM(
                'pending',
                'created',
                'authenticated',
                'active',
                'halted',
                'cancelled',
                'completed',
                'expired',
                'failed'
            ) DEFAULT 'pending'");

        // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
        // ✅ FIX payments TABLE
        // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
        DB::statement("ALTER TABLE `payments`
            MODIFY COLUMN `payment_status` ENUM(
                'created',
                'authorized',
                'captured',
                'failed',
                'refunded',
                'pending',
                'processing'
            ) DEFAULT 'created'");

        DB::statement("ALTER TABLE `payments`
            MODIFY COLUMN `order_status` ENUM(
                'created',
                'authenticated',
                'charged',
                'pending',
                'attempted',
                'paid',
                'failed',
                'cancelled'
            ) DEFAULT 'created'");
    }

    public function down()
    {
        // Rollback changes if needed
    }
};
