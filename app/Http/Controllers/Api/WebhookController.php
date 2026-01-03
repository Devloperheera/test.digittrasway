<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PlanSubscription;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class WebhookController extends Controller
{
    /**
     * Handle Razorpay Webhook
     */
    public function handleRazorpayWebhook(Request $request)
    {
        try {
            Log::info('📨 Razorpay Webhook Received', [
                'event' => $request->input('event'),
                'timestamp' => now()->toDateTimeString(),
                'ip' => $request->ip()
            ]);

            // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
            // ✅ VERIFY WEBHOOK SIGNATURE
            // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
            $webhookSecret = config('services.razorpay.webhook_secret');
            $signature = $request->header('X-Razorpay-Signature');
            
            if ($webhookSecret && $signature) {
                if (!$this->verifyWebhookSignature($request->getContent(), $signature, $webhookSecret)) {
                    Log::warning('⚠️ Invalid webhook signature');
                    return response()->json(['error' => 'Invalid signature'], 401);
                }
                Log::info('✅ Webhook signature verified');
            } else {
                Log::warning('⚠️ Webhook signature verification skipped (no secret configured)');
            }

            // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
            // ✅ GET EVENT & PAYLOAD
            // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
            $event = $request->input('event');
            $payload = $request->input('payload');

            Log::info('📋 Processing webhook event', [
                'event' => $event,
                'payload_keys' => array_keys($payload ?? [])
            ]);

            // Route to handler
            switch ($event) {
                case 'payment.authorized':
                    $this->handlePaymentAuthorized($payload);
                    break;

                case 'payment.captured':
                    $this->handlePaymentCaptured($payload);
                    break;

                case 'payment.failed':
                    $this->handlePaymentFailed($payload);
                    break;

                case 'subscription.authenticated':
                    $this->handleSubscriptionAuthenticated($payload);
                    break;

                case 'subscription.charged':
                    $this->handleSubscriptionCharged($payload);
                    break;

                case 'subscription.completed':
                    $this->handleSubscriptionCompleted($payload);
                    break;

                case 'subscription.cancelled':
                    $this->handleSubscriptionCancelled($payload);
                    break;

                case 'subscription.halted':
                    $this->handleSubscriptionHalted($payload);
                    break;

                case 'subscription.resumed':
                    $this->handleSubscriptionResumed($payload);
                    break;

                default:
                    Log::info('ℹ️ Unknown webhook event', ['event' => $event]);
            }

            return response()->json(['status' => 'ok'], 200);

        } catch (\Exception $e) {
            Log::error('❌ Webhook processing error', [
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => 'Internal server error'], 500);
        }
    }

    /**
     * Handle Payment Authorized
     */
    private function handlePaymentAuthorized($payload)
    {
        $paymentEntity = $payload['payment']['entity'] ?? [];
        $paymentId = $paymentEntity['id'] ?? null;
        $subscriptionId = $paymentEntity['subscription_id'] ?? null;

        Log::info('✅ Payment Authorized', [
            'payment_id' => $paymentId,
            'subscription_id' => $subscriptionId
        ]);

        if (!$subscriptionId) {
            Log::warning('⚠️ No subscription_id in payment.authorized');
            return;
        }

        try {
            $payment = Payment::where('razorpay_subscription_id', $subscriptionId)
                ->where('payment_status', 'created')
                ->first();

            if ($payment) {
                $payment->update([
                    'payment_id' => $paymentId,
                    'payment_status' => 'authorized',
                    'payment_metadata' => array_merge(
                        $payment->payment_metadata ?? [],
                        ['authorized_at' => now()->toDateTimeString()]
                    )
                ]);
                Log::info('✅ Payment authorized recorded');
            }
        } catch (\Exception $e) {
            Log::error('❌ Error in handlePaymentAuthorized', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Handle Payment Captured
     */
    private function handlePaymentCaptured($payload)
    {
        $paymentEntity = $payload['payment']['entity'] ?? [];
        $paymentId = $paymentEntity['id'] ?? null;
        $amount = ($paymentEntity['amount'] ?? 0) / 100;

        Log::info('✅ Payment Captured', [
            'payment_id' => $paymentId,
            'amount' => $amount
        ]);

        try {
            $payment = Payment::where('payment_id', $paymentId)->first();

            if ($payment) {
                $payment->update([
                    'payment_status' => 'captured',
                    'amount_paid' => $amount,
                    'payment_completed_at' => now()
                ]);
                Log::info('✅ Payment marked as captured');
            }
        } catch (\Exception $e) {
            Log::error('❌ Error in handlePaymentCaptured', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Handle Payment Failed
     */
    private function handlePaymentFailed($payload)
    {
        $paymentEntity = $payload['payment']['entity'] ?? [];
        $subscriptionId = $paymentEntity['subscription_id'] ?? null;
        $errorDescription = $paymentEntity['error_description'] ?? 'Unknown error';

        Log::error('❌ Payment Failed', [
            'subscription_id' => $subscriptionId,
            'error' => $errorDescription
        ]);

        if (!$subscriptionId) {
            return;
        }

        try {
            DB::beginTransaction();

            PlanSubscription::where('razorpay_subscription_id', $subscriptionId)
                ->update([
                    'subscription_status' => 'failed',
                    'status' => 'failed'
                ]);

            Payment::where('razorpay_subscription_id', $subscriptionId)
                ->where('payment_status', 'created')
                ->update([
                    'payment_status' => 'failed',
                    'error_description' => $errorDescription
                ]);

            DB::commit();
            Log::info('✅ Subscription marked as failed');
        } catch (\Exception $e) {
            DB::rollback();
            Log::error('❌ Error in handlePaymentFailed', ['error' => $e->getMessage()]);
        }
    }

    /**
     * ✅ MAIN EVENT: Handle Subscription Authenticated (PAYMENT SUCCESS!)
     */
    private function handleSubscriptionAuthenticated($payload)
    {
        $subscriptionEntity = $payload['subscription']['entity'] ?? [];
        $razorpaySubscriptionId = $subscriptionEntity['id'] ?? null;

        Log::info('🎉 Subscription Authenticated - ACTIVATING!', [
            'subscription_id' => $razorpaySubscriptionId,
            'status' => $subscriptionEntity['status'] ?? 'unknown'
        ]);

        if (!$razorpaySubscriptionId) {
            Log::error('❌ No subscription ID in payload');
            return;
        }

        DB::beginTransaction();

        try {
            // ✅ FIND SUBSCRIPTION
            $subscription = PlanSubscription::where('razorpay_subscription_id', $razorpaySubscriptionId)
                ->first();

            if (!$subscription) {
                Log::warning('⚠️ Subscription not found', [
                    'razorpay_subscription_id' => $razorpaySubscriptionId
                ]);
                DB::rollback();
                return;
            }

            // ✅ UPDATE SUBSCRIPTION TO ACTIVE
            $subscription->update([
                'subscription_status' => 'authenticated',
                'status' => 'active',  // ✅ MAIN STATUS
                'subscription_metadata' => array_merge(
                    $subscription->subscription_metadata ?? [],
                    [
                        'authenticated_at' => now()->toDateTimeString(),
                        'razorpay_data' => $subscriptionEntity
                    ]
                )
            ]);

            Log::info('✅ Subscription activated', [
                'subscription_id' => $subscription->id,
                'status' => 'active'
            ]);

            // ✅ UPDATE PAYMENT
            $payment = Payment::where('razorpay_subscription_id', $razorpaySubscriptionId)
                ->where('billing_cycle_number', 0)
                ->first();

            if ($payment) {
                $payment->update([
                    'payment_status' => 'captured',
                    'order_status' => 'authenticated',
                    'payment_completed_at' => now(),
                    'signature_verified' => true
                ]);
                Log::info('✅ Payment marked as captured');
            }

            DB::commit();

            Log::info('🎉 SUBSCRIPTION ACTIVATED SUCCESSFULLY!', [
                'subscription_id' => $subscription->id,
                'user_id' => $subscription->user_id,
                'status' => 'active'
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('❌ Error in handleSubscriptionAuthenticated', [
                'error' => $e->getMessage(),
                'line' => $e->getLine()
            ]);
        }
    }

    /**
     * Handle Subscription Charged (Recurring payment)
     */
    private function handleSubscriptionCharged($payload)
    {
        $subscriptionEntity = $payload['subscription']['entity'] ?? [];
        $invoiceEntity = $payload['invoice']['entity'] ?? [];
        $razorpaySubscriptionId = $subscriptionEntity['id'] ?? null;
        $amount = ($invoiceEntity['amount'] ?? 0) / 100;
        $paidCount = $subscriptionEntity['paid_count'] ?? 0;

        Log::info('💳 Subscription Charged', [
            'subscription_id' => $razorpaySubscriptionId,
            'amount' => $amount,
            'paid_count' => $paidCount
        ]);

        try {
            $subscription = PlanSubscription::where('razorpay_subscription_id', $razorpaySubscriptionId)
                ->first();

            if ($subscription) {
                // Create recurring payment record
                Payment::create([
                    'user_id' => $subscription->user_id,
                    'plan_id' => $subscription->plan_id,
                    'plan_subscription_id' => $subscription->id,
                    'razorpay_subscription_id' => $razorpaySubscriptionId,
                    'razorpay_customer_id' => $subscription->razorpay_customer_id,
                    'razorpay_invoice_id' => $invoiceEntity['id'] ?? null,
                    'payment_type' => 'recurring',
                    'is_subscription_payment' => true,
                    'billing_cycle_number' => $paidCount,
                    'amount' => $amount,
                    'total_amount' => $amount,
                    'currency' => 'INR',
                    'payment_gateway' => 'razorpay',
                    'payment_status' => 'captured',
                    'order_status' => 'charged',
                    'payment_completed_at' => now(),
                    'email' => $subscription->user->email ?? null,
                    'contact' => $subscription->user->contact_number ?? null,
                    'customer_name' => $subscription->user->name ?? null
                ]);

                // Update subscription
                $subscription->update([
                    'completed_billing_cycles' => $paidCount,
                    'remaining_billing_cycles' => max(0, $subscription->total_billing_cycles - $paidCount)
                ]);

                Log::info('✅ Recurring payment recorded');
            }
        } catch (\Exception $e) {
            Log::error('❌ Error in handleSubscriptionCharged', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Handle Subscription Completed
     */
    private function handleSubscriptionCompleted($payload)
    {
        $subscriptionEntity = $payload['subscription']['entity'] ?? [];
        $razorpaySubscriptionId = $subscriptionEntity['id'] ?? null;

        Log::info('✅ Subscription Completed', ['subscription_id' => $razorpaySubscriptionId]);

        try {
            PlanSubscription::where('razorpay_subscription_id', $razorpaySubscriptionId)
                ->update([
                    'subscription_status' => 'completed',
                    'status' => 'completed',
                    'completed_at' => now()
                ]);
        } catch (\Exception $e) {
            Log::error('❌ Error in handleSubscriptionCompleted', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Handle Subscription Cancelled
     */
    private function handleSubscriptionCancelled($payload)
    {
        $subscriptionEntity = $payload['subscription']['entity'] ?? [];
        $razorpaySubscriptionId = $subscriptionEntity['id'] ?? null;

        Log::info('❌ Subscription Cancelled', ['subscription_id' => $razorpaySubscriptionId]);

        try {
            PlanSubscription::where('razorpay_subscription_id', $razorpaySubscriptionId)
                ->update([
                    'subscription_status' => 'cancelled',
                    'status' => 'cancelled',
                    'cancelled_at' => now(),
                    'auto_renew' => false
                ]);
        } catch (\Exception $e) {
            Log::error('❌ Error in handleSubscriptionCancelled', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Handle Subscription Halted
     */
    private function handleSubscriptionHalted($payload)
    {
        $subscriptionEntity = $payload['subscription']['entity'] ?? [];
        $razorpaySubscriptionId = $subscriptionEntity['id'] ?? null;

        Log::info('⏸️ Subscription Halted', ['subscription_id' => $razorpaySubscriptionId]);

        try {
            PlanSubscription::where('razorpay_subscription_id', $razorpaySubscriptionId)
                ->update([
                    'subscription_status' => 'halted',
                    'status' => 'paused',
                    'paused_at' => now()
                ]);
        } catch (\Exception $e) {
            Log::error('❌ Error in handleSubscriptionHalted', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Handle Subscription Resumed
     */
    private function handleSubscriptionResumed($payload)
    {
        $subscriptionEntity = $payload['subscription']['entity'] ?? [];
        $razorpaySubscriptionId = $subscriptionEntity['id'] ?? null;

        Log::info('▶️ Subscription Resumed', ['subscription_id' => $razorpaySubscriptionId]);

        try {
            PlanSubscription::where('razorpay_subscription_id', $razorpaySubscriptionId)
                ->update([
                    'subscription_status' => 'authenticated',
                    'status' => 'active',
                    'resumed_at' => now()
                ]);
        } catch (\Exception $e) {
            Log::error('❌ Error in handleSubscriptionResumed', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Verify Webhook Signature
     */
    private function verifyWebhookSignature($requestBody, $signature, $webhookSecret)
    {
        try {
            $expectedSignature = hash_hmac('sha256', $requestBody, $webhookSecret);
            return hash_equals($expectedSignature, $signature);
        } catch (\Exception $e) {
            Log::error('❌ Signature verification error', ['error' => $e->getMessage()]);
            return false;
        }
    }
}
