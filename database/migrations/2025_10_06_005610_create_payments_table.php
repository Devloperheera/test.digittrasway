<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();

            // User & Subscription Info
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('plan_id')->constrained('plans')->onDelete('cascade');
            $table->foreignId('plan_subscription_id')->nullable()->constrained('plan_subscriptions')->onDelete('set null');

            // Payment Basic Info
            $table->string('payment_id')->unique()->index(); // Our internal ID
            $table->string('transaction_id')->unique()->nullable(); // Razorpay payment_id
            $table->string('order_id')->unique()->index(); // Razorpay order_id
            $table->string('receipt_number')->unique();

            // Amount Details
            $table->decimal('amount', 10, 2); // Original amount
            $table->decimal('amount_paid', 10, 2)->nullable(); // Actually paid
            $table->decimal('tax_amount', 10, 2)->default(0.00);
            $table->decimal('discount_amount', 10, 2)->default(0.00);
            $table->decimal('total_amount', 10, 2); // Final amount
            $table->string('currency', 3)->default('INR');

            // Payment Gateway Details
            $table->string('payment_gateway')->default('razorpay'); // razorpay, paytm, etc
            $table->string('payment_method')->nullable(); // card, upi, netbanking, wallet
            $table->string('payment_method_type')->nullable(); // visa, mastercard, paytm, phonepe

            // Card/Bank Details (if applicable)
            $table->string('card_id')->nullable();
            $table->string('card_type')->nullable(); // credit, debit
            $table->string('card_network')->nullable(); // Visa, Mastercard, Rupay
            $table->string('card_last4')->nullable();
            $table->string('bank')->nullable();
            $table->string('wallet')->nullable(); // paytm, phonepe, etc
            $table->string('vpa')->nullable(); // UPI ID

            // Status & Timestamps
            $table->enum('payment_status', [
                'pending',
                'processing',
                'authorized',
                'captured',
                'failed',
                'cancelled',
                'refunded',
                'partial_refund'
            ])->default('pending');

            $table->enum('order_status', [
                'created',
                'attempted',
                'paid',
                'failed'
            ])->default('created');

            $table->timestamp('payment_initiated_at')->nullable();
            $table->timestamp('payment_completed_at')->nullable();
            $table->timestamp('payment_failed_at')->nullable();

            // User Contact Info
            $table->string('email')->nullable();
            $table->string('contact', 15)->nullable();
            $table->string('customer_name')->nullable();

            // Error Details
            $table->string('error_code')->nullable();
            $table->string('error_description')->nullable();
            $table->text('error_source')->nullable();
            $table->text('error_step')->nullable();
            $table->text('error_reason')->nullable();

            // Razorpay Signature & Security
            $table->text('razorpay_signature')->nullable();
            $table->boolean('signature_verified')->default(false);

            // Additional Info
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->json('metadata')->nullable(); // Extra custom data

            // Razorpay Full Response
            $table->json('razorpay_order_response')->nullable();
            $table->json('razorpay_payment_response')->nullable();

            // Refund Info (if any)
            $table->string('refund_id')->nullable();
            $table->decimal('refund_amount', 10, 2)->nullable();
            $table->timestamp('refund_date')->nullable();
            $table->string('refund_status')->nullable();
            $table->text('refund_reason')->nullable();

            // Notes & Description
            $table->text('description')->nullable();
            $table->json('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes for performance
            $table->index(['user_id', 'payment_status']);
            $table->index('payment_status');
            $table->index('order_status');
            $table->index('created_at');
        });
    }

    public function down()
    {
        Schema::dropIfExists('payments');
    }
};
