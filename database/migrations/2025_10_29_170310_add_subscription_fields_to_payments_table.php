<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            // Razorpay Subscription IDs
            $table->string('razorpay_subscription_id')->nullable()->after('plan_subscription_id');
            $table->string('razorpay_customer_id')->nullable()->after('razorpay_subscription_id');
            $table->string('razorpay_invoice_id')->nullable()->after('razorpay_customer_id');

            // Payment Type
            $table->enum('payment_type', ['setup_fee', 'recurring', 'one_time', 'addon', 'refund'])
                ->default('one_time')->after('razorpay_invoice_id')
                ->comment('setup_fee = ₹1 initial, recurring = monthly/quarterly/etc');

            // Subscription-specific fields
            $table->boolean('is_subscription_payment')->default(false)->after('payment_type');
            $table->timestamp('billing_period_start')->nullable()->after('is_subscription_payment');
            $table->timestamp('billing_period_end')->nullable()->after('billing_period_start');
            $table->integer('billing_cycle_number')->nullable()->after('billing_period_end')
                ->comment('1st payment, 2nd payment, etc');

            // Indexes
            $table->index('razorpay_subscription_id');
            $table->index('razorpay_customer_id');
            $table->index('payment_type');
            $table->index('is_subscription_payment');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn([
                'razorpay_subscription_id',
                'razorpay_customer_id',
                'razorpay_invoice_id',
                'payment_type',
                'is_subscription_payment',
                'billing_period_start',
                'billing_period_end',
                'billing_cycle_number'
            ]);
        });
    }
};
