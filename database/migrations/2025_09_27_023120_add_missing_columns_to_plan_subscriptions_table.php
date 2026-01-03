<?php
// database/migrations/xxxx_add_missing_columns_to_plan_subscriptions_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('plan_subscriptions', function (Blueprint $table) {
            // Add missing columns that code expects
            if (!Schema::hasColumn('plan_subscriptions', 'price')) {
                $table->decimal('price', 10, 2)->after('plan_name');
            }

            if (!Schema::hasColumn('plan_subscriptions', 'selected_features')) {
                $table->json('selected_features')->nullable()->after('duration_type');
            }

            if (!Schema::hasColumn('plan_subscriptions', 'expires_at')) {
                $table->datetime('expires_at')->nullable()->after('selected_features');
            }
        });
    }

    public function down()
    {
        Schema::table('plan_subscriptions', function (Blueprint $table) {
            $table->dropColumn(['price', 'selected_features', 'expires_at']);
        });
    }
};
