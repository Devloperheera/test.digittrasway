<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        // ✅ FIX: Add 'pending' to status ENUM
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
    }

    public function down()
    {
        DB::statement("ALTER TABLE `plan_subscriptions`
            MODIFY COLUMN `status` ENUM(
                'active',
                'inactive',
                'expired',
                'cancelled'
            ) DEFAULT 'active'");
    }
};
