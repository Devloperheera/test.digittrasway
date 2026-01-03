<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PlanSubscription;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class RazorpayWebhookController extends Controller
{
    protected $webhookSecret;

    public function __construct()
    {
        $this->webhookSecret = config('services.razorpay.webhook_secret');
    }

    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    // ✅ MAIN WEBHOOK HANDLER
    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

    /**
     * Handle Razorpay webhooks
     */
    public function handleWebhook(Request $request): JsonResponse
    {
        try {
            // Get webhook payload
            $payload = $request->getContent();
            $signature = $request->header('X-Razorpay-Signature');

            Log::info('🔔 Webhook received', [
                'event' => $request->input('event'),
                'payload_length' => strlen($payload),
                'has_signature' => !empty($signature)
            ]);

            // Verify webhook signature
            if (!$this->verifyWebhookSignature($payload, $signature)) {
                Log::error('❌ Webhook signature verification failed');

                return response()->json([
                    'success' => false,
                    'message' => 'Invalid signature'
                ], 401);
            }

            // Parse webhook data
            $data = json_decode($payload, true);
            $event = $data['event'] ?? null;
            $entity = $data['payload']['subscription']['entity'] ?? $data['payload']['payment']['entity'] ?? null;

            if (!$event || !$entity) {
                Log::error('❌ Invalid webhook data', ['data' => $data]);

                return response()->json([
                    'success' => false,
                    'message' => 'Invalid webhook data'
                ], 400);
            }

            Log::info('✅ Webhook signature verified', [
                'event' => $event,
                'entity_id' => $entity['id'] ?? 'N/A'
            ]);

            // Route to appropriate handler
            $result = match(true) {
                str_starts_with($event, 'subscription.') => $this->handleSubscriptionEvent($event, $entity, $data),
                str_starts_with($event, 'payment.') => $this->handlePaymentEvent($event, $entity, $data),
                str_starts_with($event, 'invoice.') => $this->handleInvoiceEvent($event, $entity, $data),
                default => ['success' => false, 'message' => 'Unhandled event type']
            };

            Log::info('Webhook processed', [
                'event' => $event,
                'success' => $result['success'] ?? false
            ]);

            return response()->json($result);

        } catch (\Exception $e) {
            Log::error('❌ Webhook handler exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Webhook processing failed'
            ], 500);
        }
    }

    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    // ✅ VERIFY WEBHOOK SIGNATURE
    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

    /**
     * Verify webhook signature
     */
    protected function verifyWebhookSignature(string $payload, ?string $signature): bool
    {
        try {
            if (!$signature || !$this->webhookSecret) {
                return false;
            }

            $expectedSignature = hash_hmac('sha256', $payload, $this->webhookSecret);

            return hash_equals($expectedSignature, $signature);
        } catch (\Exception $e) {
            Log::error('Webhook signature verification failed', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    // ✅ SUBSCRIPTION EVENT HANDLERS
    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

    /**
     * Handle subscription events
     */
    protected function handleSubscriptionEvent(string $event, array $entity, array $fullData): array
    {
        $subscriptionId = $entity['id'];
        $subscription = PlanSubscription::where('razorpay_subscription_id', $subscriptionId)->first();

        if (!$subscription) {
            Log::warning('⚠️ Subscription not found in database', [
                'razorpay_subscription_id' => $subscriptionId
            ]);

            return ['success' => false, 'message' => 'Subscription not found'];
        }

        return match($event) {
            'subscription.activated' => $this->handleSubscriptionActivated($subscription, $entity),
            'subscription.charged' => $this->handleSubscriptionCharged($subscription, $entity, $fullData),
            'subscription.cancelled' => $this->handleSubscriptionCancelled($subscription, $entity),
            'subscription.paused' => $this->handleSubscriptionPaused($subscription, $entity),
            'subscription.resumed' => $this->handleSubscriptionResumed($subscription, $entity),
            'subscription.completed' => $this->handleSubscriptionCompleted($subscription, $entity),
            'subscription.pending' => $this->handleSubscriptionPending($subscription, $entity),
            'subscription.halted' => $this->handleSubscriptionHalted($subscription, $entity),
            'subscription.authenticated' => $this->handleSubscriptionAuthenticated($subscription, $entity),
            default => ['success' => false, 'message' => 'Unhandled subscription event']
        };
    }

    /**
     * Handle subscription.authenticated
     */
    protected function handleSubscriptionAuthenticated(PlanSubscription $subscription, array $entity): array
    {
        try {
            DB::beginTransaction();

            $subscription->update([
                'subscription_status' => 'authenticated',
                'status' => 'active',
                'subscription_metadata' => array_merge(
                    $subscription->subscription_metadata ?? [],
                    ['authenticated_at' => now(), 'razorpay_data' => $entity]
                )
            ]);

            DB::commit();

            Log::info('✅ Subscription authenticated', [
                'subscription_id' => $subscription->id,
                'user_id' => $subscription->user_id
            ]);

            return ['success' => true, 'message' => 'Subscription authenticated'];

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('❌ Subscription authentication failed', ['error' => $e->getMessage()]);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Handle subscription.activated
     */
    protected function handleSubscriptionActivated(PlanSubscription $subscription, array $entity): array
    {
        try {
            DB::beginTransaction();

            $startAt = isset($entity['start_at']) ? Carbon::createFromTimestamp($entity['start_at']) : now();
            $endAt = isset($entity['end_at']) ? Carbon::createFromTimestamp($entity['end_at']) : null;
            $nextBillingAt = isset($entity['charge_at']) ? Carbon::createFromTimestamp($entity['charge_at']) : null;

            $subscription->update([
                'subscription_status' => 'active',
                'status' => 'active',
                'starts_at' => $startAt,
                'expires_at' => $endAt,
                'next_billing_at' => $nextBillingAt,
                'subscription_metadata' => array_merge(
                    $subscription->subscription_metadata ?? [],
                    ['activated_at' => now(), 'razorpay_data' => $entity]
                )
            ]);

            DB::commit();

            Log::info('✅ Subscription activated', [
                'subscription_id' => $subscription->id,
                'user_id' => $subscription->user_id
            ]);

            return ['success' => true, 'message' => 'Subscription activated'];

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('❌ Subscription activation failed', ['error' => $e->getMessage()]);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Handle subscription.charged (recurring payment)
     */
    protected function handleSubscriptionCharged(PlanSubscription $subscription, array $entity, array $fullData): array
    {
        try {
            DB::beginTransaction();

            // Get payment entity from webhook
            $paymentEntity = $fullData['payload']['payment']['entity'] ?? null;

            if (!$paymentEntity) {
                throw new \Exception('Payment entity not found in webhook data');
            }

            // Check if payment already exists
            $existingPayment = Payment::where('payment_id', $paymentEntity['id'])->first();
            if ($existingPayment) {
                Log::info('Payment already exists', ['payment_id' => $paymentEntity['id']]);
                DB::commit();
                return ['success' => true, 'message' => 'Payment already recorded'];
            }

            // Determine payment type
            $cycleNumber = $subscription->completed_billing_cycles;
            $isSetupFee = $cycleNumber === 0 && $paymentEntity['amount'] == 100; // ₹1 = 100 paise

            // Create payment record
            $payment = Payment::create([
                'user_id' => $subscription->user_id,
                'plan_id' => $subscription->plan_id,
                'plan_subscription_id' => $subscription->id,
                'razorpay_subscription_id' => $subscription->razorpay_subscription_id,
                'razorpay_customer_id' => $subscription->razorpay_customer_id,
                'razorpay_invoice_id' => $entity['invoice_id'] ?? null,
                'payment_id' => $paymentEntity['id'],
                'order_id' => $paymentEntity['order_id'] ?? null,
                'receipt_number' => 'REC_' . time() . '_' . rand(1000, 9999),
                'amount' => $paymentEntity['amount'] / 100,
                'amount_paid' => $paymentEntity['amount'] / 100,
                'total_amount' => $paymentEntity['amount'] / 100,
                'currency' => $paymentEntity['currency'] ?? 'INR',
                'payment_gateway' => 'razorpay',
                'payment_method' => $paymentEntity['method'] ?? null,
                'payment_status' => 'captured',
                'payment_type' => $isSetupFee ? 'setup_fee' : 'recurring',
                'is_subscription_payment' => true,
                'billing_cycle_number' => $isSetupFee ? 0 : $cycleNumber + 1,
                'payment_initiated_at' => now(),
                'payment_completed_at' => now(),
                'email' => $paymentEntity['email'] ?? $subscription->user->email ?? 'noreply@digittransway.com',
                'contact' => $paymentEntity['contact'] ?? $subscription->user->contact_number ?? '+910000000000',
                'customer_name' => $subscription->user->name ?? 'User',
                'razorpay_payment_response' => $paymentEntity
            ]);

            // Update subscription billing cycles (only for non-setup fee payments)
            if (!$isSetupFee) {
                $subscription->increment('completed_billing_cycles');

                // Update subscription status to active if not already
                if ($subscription->subscription_status !== 'active') {
                    $subscription->update(['subscription_status' => 'active', 'status' => 'active']);
                }
            }

            // Update next billing date
            if (isset($entity['charge_at'])) {
                $subscription->update([
                    'next_billing_at' => Carbon::createFromTimestamp($entity['charge_at'])
                ]);
            }

            DB::commit();

            Log::info('✅ Subscription charged', [
                'subscription_id' => $subscription->id,
                'payment_id' => $payment->id,
                'payment_type' => $payment->payment_type,
                'amount' => $payment->amount,
                'cycle_number' => $payment->billing_cycle_number
            ]);

            return ['success' => true, 'message' => 'Payment recorded'];

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('❌ Subscription charge handling failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Handle subscription.cancelled
     */
    protected function handleSubscriptionCancelled(PlanSubscription $subscription, array $entity): array
    {
        try {
            $subscription->update([
                'subscription_status' => 'cancelled',
                'status' => 'cancelled',
                'cancelled_at' => now(),
                'auto_renew' => false
            ]);

            Log::info('✅ Subscription cancelled', [
                'subscription_id' => $subscription->id
            ]);

            return ['success' => true, 'message' => 'Subscription cancelled'];

        } catch (\Exception $e) {
            Log::error('❌ Subscription cancellation failed', ['error' => $e->getMessage()]);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Handle subscription.paused
     */
    protected function handleSubscriptionPaused(PlanSubscription $subscription, array $entity): array
    {
        try {
            $subscription->update([
                'subscription_status' => 'paused',
                'paused_at' => now()
            ]);

            Log::info('✅ Subscription paused', [
                'subscription_id' => $subscription->id
            ]);

            return ['success' => true, 'message' => 'Subscription paused'];

        } catch (\Exception $e) {
            Log::error('❌ Subscription pause failed', ['error' => $e->getMessage()]);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Handle subscription.resumed
     */
    protected function handleSubscriptionResumed(PlanSubscription $subscription, array $entity): array
    {
        try {
            $subscription->update([
                'subscription_status' => 'active',
                'resumed_at' => now(),
                'paused_at' => null
            ]);

            Log::info('✅ Subscription resumed', [
                'subscription_id' => $subscription->id
            ]);

            return ['success' => true, 'message' => 'Subscription resumed'];

        } catch (\Exception $e) {
            Log::error('❌ Subscription resume failed', ['error' => $e->getMessage()]);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Handle subscription.completed
     */
    protected function handleSubscriptionCompleted(PlanSubscription $subscription, array $entity): array
    {
        try {
            $subscription->update([
                'subscription_status' => 'completed',
                'status' => 'completed',
                'completed_at' => now()
            ]);

            Log::info('✅ Subscription completed', [
                'subscription_id' => $subscription->id
            ]);

            return ['success' => true, 'message' => 'Subscription completed'];

        } catch (\Exception $e) {
            Log::error('❌ Subscription completion failed', ['error' => $e->getMessage()]);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Handle subscription.pending
     */
    protected function handleSubscriptionPending(PlanSubscription $subscription, array $entity): array
    {
        try {
            $subscription->update([
                'subscription_status' => 'pending',
                'status' => 'pending'
            ]);

            Log::info('✅ Subscription pending', [
                'subscription_id' => $subscription->id
            ]);

            return ['success' => true, 'message' => 'Subscription pending'];

        } catch (\Exception $e) {
            Log::error('❌ Subscription pending update failed', ['error' => $e->getMessage()]);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Handle subscription.halted
     */
    protected function handleSubscriptionHalted(PlanSubscription $subscription, array $entity): array
    {
        try {
            $subscription->update([
                'subscription_status' => 'halted',
                'status' => 'halted'
            ]);

            Log::info('✅ Subscription halted', [
                'subscription_id' => $subscription->id
            ]);

            return ['success' => true, 'message' => 'Subscription halted'];

        } catch (\Exception $e) {
            Log::error('❌ Subscription halt failed', ['error' => $e->getMessage()]);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    // ✅ PAYMENT EVENT HANDLERS
    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

    /**
     * Handle payment events
     */
    protected function handlePaymentEvent(string $event, array $entity, array $fullData): array
    {
        return match($event) {
            'payment.authorized' => $this->handlePaymentAuthorized($entity),
            'payment.captured' => $this->handlePaymentCaptured($entity),
            'payment.failed' => $this->handlePaymentFailed($entity),
            default => ['success' => false, 'message' => 'Unhandled payment event']
        };
    }

    /**
     * Handle payment.authorized
     */
    protected function handlePaymentAuthorized(array $entity): array
    {
        try {
            $payment = Payment::where('payment_id', $entity['id'])->first();

            if ($payment) {
                $payment->update([
                    'payment_status' => 'authorized'
                ]);
            }

            Log::info('✅ Payment authorized', [
                'payment_id' => $entity['id']
            ]);

            return ['success' => true, 'message' => 'Payment authorized'];

        } catch (\Exception $e) {
            Log::error('❌ Payment authorization handling failed', ['error' => $e->getMessage()]);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Handle payment.captured
     */
    protected function handlePaymentCaptured(array $entity): array
    {
        try {
            $payment = Payment::where('payment_id', $entity['id'])->first();

            if ($payment) {
                $payment->update([
                    'payment_status' => 'captured',
                    'payment_completed_at' => now()
                ]);
            }

            Log::info('✅ Payment captured', [
                'payment_id' => $entity['id']
            ]);

            return ['success' => true, 'message' => 'Payment captured'];

        } catch (\Exception $e) {
            Log::error('❌ Payment capture handling failed', ['error' => $e->getMessage()]);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Handle payment.failed
     */
    protected function handlePaymentFailed(array $entity): array
    {
        try {
            $payment = Payment::where('payment_id', $entity['id'])->first();

            if ($payment) {
                $payment->update([
                    'payment_status' => 'failed',
                    'payment_failed_at' => now(),
                    'error_code' => $entity['error_code'] ?? null,
                    'error_description' => $entity['error_description'] ?? null
                ]);
            }

            Log::info('✅ Payment failed', [
                'payment_id' => $entity['id'],
                'error_code' => $entity['error_code'] ?? 'N/A'
            ]);

            return ['success' => true, 'message' => 'Payment failure recorded'];

        } catch (\Exception $e) {
            Log::error('❌ Payment failure handling failed', ['error' => $e->getMessage()]);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    // ✅ INVOICE EVENT HANDLERS
    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

    /**
     * Handle invoice events
     */
    protected function handleInvoiceEvent(string $event, array $entity, array $fullData): array
    {
        return match($event) {
            'invoice.paid' => $this->handleInvoicePaid($entity),
            default => ['success' => false, 'message' => 'Unhandled invoice event']
        };
    }

    /**
     * Handle invoice.paid
     */
    protected function handleInvoicePaid(array $entity): array
    {
        try {
            Log::info('✅ Invoice paid', [
                'invoice_id' => $entity['id'],
                'subscription_id' => $entity['subscription_id'] ?? 'N/A'
            ]);

            return ['success' => true, 'message' => 'Invoice paid'];

        } catch (\Exception $e) {
            Log::error('❌ Invoice paid handling failed', ['error' => $e->getMessage()]);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}
