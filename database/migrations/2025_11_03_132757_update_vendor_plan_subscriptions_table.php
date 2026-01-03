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
        Schema::table('vendor_plan_subscriptions', function (Blueprint $table) {
            // ✅ Add missing columns if they don't exist
            if (!Schema::hasColumn('vendor_plan_subscriptions', 'razorpay_subscription_id')) {
                $table->string('razorpay_subscription_id')->nullable()->after('vendor_plan_id');
            }

            if (!Schema::hasColumn('vendor_plan_subscriptions', 'subscription_status')) {
                $table->enum('subscription_status', ['pending', 'authenticated', 'active', 'paused', 'cancelled', 'failed'])
                    ->default('pending')
                    ->after('status');
            }

            if (!Schema::hasColumn('vendor_plan_subscriptions', 'total_billing_cycles')) {
                $table->integer('total_billing_cycles')->default(12)->after('duration_type');
            }

            if (!Schema::hasColumn('vendor_plan_subscriptions', 'completed_billing_cycles')) {
                $table->integer('completed_billing_cycles')->default(0)->after('total_billing_cycles');
            }

            if (!Schema::hasColumn('vendor_plan_subscriptions', 'next_billing_at')) {
                $table->timestamp('next_billing_at')->nullable()->after('expires_at');
            }

            if (!Schema::hasColumn('vendor_plan_subscriptions', 'cancelled_at')) {
                $table->timestamp('cancelled_at')->nullable()->after('next_billing_at');
            }

            if (!Schema::hasColumn('vendor_plan_subscriptions', 'setup_fee')) {
                $table->decimal('setup_fee', 10, 2)->default(0)->after('price_paid');
            }

            if (!Schema::hasColumn('vendor_plan_subscriptions', 'razorpay_plan_id')) {
                $table->string('razorpay_plan_id')->nullable()->after('razorpay_subscription_id');
            }

            if (!Schema::hasColumn('vendor_plan_subscriptions', 'razorpay_customer_id')) {
                $table->string('razorpay_customer_id')->nullable()->after('razorpay_plan_id');
            }

            if (!Schema::hasColumn('vendor_plan_subscriptions', 'auto_renew')) {
                $table->boolean('auto_renew')->default(true)->after('is_paid');
            }

            if (!Schema::hasColumn('vendor_plan_subscriptions', 'subscription_metadata')) {
                $table->longText('subscription_metadata')->nullable()->after('plan_features');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vendor_plan_subscriptions', function (Blueprint $table) {
            $table->dropColumn([
                'razorpay_subscription_id',
                'subscription_status',
                'total_billing_cycles',
                'completed_billing_cycles',
                'next_billing_at',
                'cancelled_at',
                'setup_fee',
                'razorpay_plan_id',
                'razorpay_customer_id',
                'auto_renew',
                'subscription_metadata'
            ]);
        });
    }
};
