<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('vendor_plan_subscriptions', function (Blueprint $table) {
            $table->foreignId('vendor_payment_id')->nullable()->after('vendor_plan_id')
                ->constrained('vendor_payments')->onDelete('set null');
            $table->boolean('is_paid')->default(false)->after('status');
        });
    }

    public function down()
    {
        Schema::table('vendor_plan_subscriptions', function (Blueprint $table) {
            $table->dropForeign(['vendor_payment_id']);
            $table->dropColumn(['vendor_payment_id', 'is_paid']);
        });
    }
};
