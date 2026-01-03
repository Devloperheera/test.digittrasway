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
        Schema::table('vendor_payments', function (Blueprint $table) {
            // ✅ Add missing columns if they don't exist
            if (!Schema::hasColumn('vendor_payments', 'razorpay_subscription_id')) {
                $table->string('razorpay_subscription_id')->nullable()->after('razorpay_order_id');
            }

            if (!Schema::hasColumn('vendor_payments', 'vendor_plan_subscription_id')) {
                $table->unsignedBigInteger('vendor_plan_subscription_id')->nullable()->after('vendor_plan_id');
                $table->foreign('vendor_plan_subscription_id')
                    ->references('id')
                    ->on('vendor_plan_subscriptions')
                    ->onDelete('cascade');
            }

            if (!Schema::hasColumn('vendor_payments', 'payment_completed_at')) {
                $table->timestamp('payment_completed_at')->nullable()->after('paid_at');
            }

            if (!Schema::hasColumn('vendor_payments', 'signature_verified')) {
                $table->boolean('signature_verified')->default(false)->after('payment_completed_at');
            }

            if (!Schema::hasColumn('vendor_payments', 'payment_failed_at')) {
                $table->timestamp('payment_failed_at')->nullable()->after('signature_verified');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vendor_payments', function (Blueprint $table) {
            $table->dropForeign(['vendor_plan_subscription_id']);
            $table->dropColumn([
                'razorpay_subscription_id',
                'vendor_plan_subscription_id',
                'payment_completed_at',
                'signature_verified',
                'payment_failed_at'
            ]);
        });
    }
};
