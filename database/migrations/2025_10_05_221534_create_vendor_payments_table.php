<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('vendor_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained('vendors')->onDelete('cascade');
            $table->foreignId('vendor_plan_id')->constrained('vendor_plans')->onDelete('cascade');
            $table->foreignId('vendor_plan_subscription_id')->nullable()->constrained('vendor_plan_subscriptions')->onDelete('set null');

            // Payment Details
            $table->string('razorpay_order_id')->unique();
            $table->string('razorpay_payment_id')->nullable()->unique();
            $table->string('razorpay_signature')->nullable();

            // Amount Details
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('INR');
            $table->decimal('amount_paid', 10, 2)->nullable();

            // Payment Status
            $table->enum('payment_status', ['pending', 'paid', 'failed', 'refunded'])->default('pending');
            $table->enum('order_status', ['created', 'attempted', 'paid'])->default('created');

            // Additional Details
            $table->string('payment_method')->nullable(); // card, netbanking, upi, wallet
            $table->string('card_id')->nullable();
            $table->string('bank')->nullable();
            $table->string('wallet')->nullable();
            $table->string('vpa')->nullable(); // UPI VPA
            $table->string('email')->nullable();
            $table->string('contact')->nullable();

            // Metadata
            $table->json('razorpay_response')->nullable();
            $table->text('error_code')->nullable();
            $table->text('error_description')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->string('receipt_number')->unique();

            $table->timestamps();
            $table->index(['vendor_id', 'payment_status']);
            $table->index('razorpay_order_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('vendor_payments');
    }
};
