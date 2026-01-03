<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plan_subscriptions', function (Blueprint $table) {
            // Razorpay IDs
            $table->string('razorpay_subscription_id')->nullable()->after('plan_id');
            $table->string('razorpay_customer_id')->nullable()->after('razorpay_subscription_id');
            $table->string('razorpay_plan_id')->nullable()->after('razorpay_customer_id');

            // Subscription Details
            $table->string('subscription_status')->default('created')->after('status')
                ->comment('created, authenticated, active, paused, cancelled, completed, expired, halted');
            $table->integer('total_billing_cycles')->default(0)->after('duration_type');
            $table->integer('completed_billing_cycles')->default(0)->after('total_billing_cycles');
            $table->integer('remaining_billing_cycles')->default(0)->after('completed_billing_cycles');

            // Billing Dates
            $table->timestamp('next_billing_at')->nullable()->after('expires_at');
            $table->timestamp('cancelled_at')->nullable()->after('next_billing_at');
            $table->timestamp('completed_at')->nullable()->after('cancelled_at');
            $table->timestamp('paused_at')->nullable()->after('completed_at');
            $table->timestamp('resumed_at')->nullable()->after('paused_at');

            // Setup Fee & Auto Renew
            $table->decimal('setup_fee', 10, 2)->default(1.00)->after('price_paid');
            $table->boolean('auto_renew')->default(true)->after('subscription_status');
            $table->boolean('is_trial')->default(false)->after('auto_renew');

            // Payment Token (for recurring)
            $table->text('payment_token_data')->nullable()->after('is_trial');

            // Additional Info
            $table->json('subscription_metadata')->nullable()->after('payment_token_data');
            $table->text('cancellation_reason')->nullable()->after('subscription_metadata');

            // Indexes
            $table->index('razorpay_subscription_id');
            $table->index('razorpay_customer_id');
            $table->index('subscription_status');
            $table->index('next_billing_at');
        });
    }

    public function down(): void
    {
        Schema::table('plan_subscriptions', function (Blueprint $table) {
            $table->dropColumn([
                'razorpay_subscription_id',
                'razorpay_customer_id',
                'razorpay_plan_id',
                'subscription_status',
                'total_billing_cycles',
                'completed_billing_cycles',
                'remaining_billing_cycles',
                'next_billing_at',
                'cancelled_at',
                'completed_at',
                'paused_at',
                'resumed_at',
                'setup_fee',
                'auto_renew',
                'is_trial',
                'payment_token_data',
                'subscription_metadata',
                'cancellation_reason'
            ]);
        });
    }
};
