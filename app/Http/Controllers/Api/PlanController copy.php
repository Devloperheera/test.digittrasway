<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\PlanSubscription;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Razorpay\Api\Api;
use Razorpay\Api\Errors\SignatureVerificationError;
use Carbon\Carbon;

class PlanController extends Controller
{
    private $razorpayKey;
    private $razorpaySecret;
    private $razorpayApi;

    public function __construct()
    {
        $this->razorpayKey = env('RAZORPAY_KEY_ID');
        $this->razorpaySecret = env('RAZORPAY_KEY_SECRET');

        try {
            $this->razorpayApi = new Api($this->razorpayKey, $this->razorpaySecret);
        } catch (\Exception $e) {
            Log::error('Razorpay API initialization failed', [
                'error' => $e->getMessage()
            ]);
        }
    }

    // ✅ 1. GET ALL PLANS
    public function getPlans(): JsonResponse
    {
        try {
            $plans = Plan::active()
                        ->ordered()
                        ->get()
                        ->map(function ($plan) {
                            return [
                                'id' => $plan->id,
                                'name' => $plan->name,
                                'description' => $plan->description,
                                'price' => $plan->price,
                                'duration_type' => $plan->duration_type,
                                'duration_days' => $plan->duration_days ?? $this->getDurationDays($plan->duration_type),
                                'duration_text' => $plan->duration_text ?? ucfirst($plan->duration_type),
                                'features' => $plan->features,
                                'is_popular' => $plan->is_popular,
                                'button_text' => $plan->button_text,
                                'button_color' => $plan->button_color,
                                'contact_info' => $plan->contact_info ?? null,
                                'subscribers_count' => PlanSubscription::where('plan_id', $plan->id)
                                    ->where('status', 'active')
                                    ->count(),
                                'sort_order' => $plan->sort_order,
                                'created_at' => $plan->created_at
                            ];
                        });

            $additionalInfo = [
                'subscription_auto_renews' => 'Subscription auto-renews based on duration',
                'cancellation_policy' => 'Cancel anytime from your account',
                'pricing_info' => 'All prices include applicable taxes',
                'support_info' => '24/7 customer support available'
            ];

            return response()->json([
                'success' => true,
                'message' => 'Plans retrieved successfully',
                'data' => [
                    'plans' => $plans,
                    'total_plans' => $plans->count(),
                    'important_information' => $additionalInfo
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Get Plans Error', [
                'error' => $e->getMessage(),
                'line' => $e->getLine()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve plans: ' . $e->getMessage()
            ], 500);
        }
    }

    // ✅ 2. SUBSCRIBE TO PLAN (Create Razorpay Order)
    public function subscribePlan(Request $request): JsonResponse
    {
        try {
            if (!$this->razorpayApi) {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment gateway not configured properly'
                ], 500);
            }

            Log::info('Subscribe plan request received', [
                'has_auth_header' => !empty($request->header('Authorization')),
                'plan_id' => $request->plan_id ?? null
            ]);

            // ✅ Token validation
            $authHeader = $request->header('Authorization');
            if (!$authHeader || !str_contains($authHeader, 'Bearer ')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Authorization token required'
                ], 401);
            }

            $token = str_replace('Bearer ', '', $authHeader);
            $decodedToken = base64_decode($token);
            $tokenParts = explode(':', $decodedToken);

            if (count($tokenParts) < 3) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid token format'
                ], 401);
            }

            $userId = $tokenParts[0];
            $timestamp = $tokenParts[1];

            // Check token expiry
            if ((time() - $timestamp) > 3600) {
                return response()->json([
                    'success' => false,
                    'message' => 'Token expired. Please login again.'
                ], 401);
            }

            // Find user
            $user = User::find($userId);

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found'
                ], 404);
            }

            // Validate request
            $validator = Validator::make($request->all(), [
                'plan_id' => 'required|integer|exists:plans,id'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 400);
            }

            // Get plan details
            $plan = Plan::findOrFail($request->plan_id);

            if (!$plan->is_active) {
                return response()->json([
                    'success' => false,
                    'message' => 'Selected plan is not available'
                ], 400);
            }

            // Check for existing active subscription
            $existingSubscription = PlanSubscription::where('user_id', $userId)
                ->where('status', 'active')
                ->where(function($query) {
                    $query->whereNull('expires_at')
                          ->orWhere('expires_at', '>', now());
                })
                ->first();

            if ($existingSubscription) {
                return response()->json([
                    'success' => false,
                    'message' => 'You already have an active subscription'
                ], 409);
            }

            DB::beginTransaction();

            try {
                // Generate unique IDs
                $paymentId = Payment::generatePaymentId();
                $receiptNumber = Payment::generateReceiptNumber();

                // Convert to paise (Razorpay uses smallest currency unit)
                $amountInPaise = intval($plan->price * 100);

                Log::info('Creating Razorpay order', [
                    'amount' => $plan->price,
                    'amount_in_paise' => $amountInPaise,
                    'receipt' => $receiptNumber
                ]);

                // ✅ CREATE RAZORPAY ORDER
                $razorpayOrder = $this->razorpayApi->order->create([
                    'receipt' => $receiptNumber,
                    'amount' => $amountInPaise,
                    'currency' => 'INR',
                    'payment_capture' => 1, // Auto capture
                    'notes' => [
                        'user_id' => $userId,
                        'user_name' => $user->name,
                        'user_email' => $user->email,
                        'plan_id' => $plan->id,
                        'plan_name' => $plan->name,
                        'payment_id' => $paymentId
                    ]
                ]);

                Log::info('Razorpay order created successfully', [
                    'order_id' => $razorpayOrder['id'],
                    'amount' => $razorpayOrder['amount'],
                    'currency' => $razorpayOrder['currency']
                ]);

                // ✅ CREATE PAYMENT RECORD
                $payment = Payment::create([
                    'user_id' => $userId,
                    'plan_id' => $plan->id,
                    'payment_id' => $paymentId,
                    'order_id' => $razorpayOrder['id'],
                    'receipt_number' => $receiptNumber,
                    'amount' => $plan->price,
                    'total_amount' => $plan->price,
                    'currency' => 'INR',
                    'payment_gateway' => 'razorpay',
                    'payment_status' => 'pending',
                    'order_status' => 'created',
                    'email' => $user->email,
                    'contact' => $user->contact_number,
                    'customer_name' => $user->name,
                    'payment_initiated_at' => now(),
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'razorpay_order_response' => $razorpayOrder,
                    'description' => "Payment for {$plan->name}",
                    'notes' => [
                        'plan_name' => $plan->name,
                        'duration' => $plan->duration_type
                    ]
                ]);

                DB::commit();

                Log::info('Payment record created', [
                    'payment_id' => $payment->payment_id,
                    'order_id' => $razorpayOrder['id']
                ]);

                // ✅ RETURN RAZORPAY CHECKOUT DATA
                return response()->json([
                    'success' => true,
                    'message' => 'Order created successfully. Proceed to payment.',
                    'action' => 'open_razorpay_checkout',
                    'data' => [
                        'razorpay_key' => $this->razorpayKey,
                        'razorpay_order_id' => $razorpayOrder['id'],
                        'amount' => $plan->price,
                        'amount_in_paise' => $amountInPaise,
                        'currency' => 'INR',
                        'payment_id' => $payment->payment_id,
                        'receipt_number' => $receiptNumber,
                        'order_created_at' => now()->format('Y-m-d H:i:s'),
                        'plan' => [
                            'id' => $plan->id,
                            'name' => $plan->name,
                            'description' => $plan->description,
                            'price' => $plan->price,
                            'duration_type' => $plan->duration_type
                        ],
                        'prefill' => [
                            'name' => $user->name,
                            'email' => $user->email,
                            'contact' => $user->contact_number
                        ],
                        'theme' => [
                            'color' => '#02b5ff',
                            'backdrop_color' => '#000000'
                        ],
                        'modal' => [
                            'ondismiss' => 'Payment cancelled by user'
                        ]
                    ]
                ]);

            } catch (\Exception $e) {
                DB::rollback();
                throw $e;
            }

        } catch (\Exception $e) {
            Log::error('Subscribe plan error', [
                'user_id' => $userId ?? null,
                'plan_id' => $request->plan_id ?? null,
                'error' => $e->getMessage(),
                'line' => $e->getLine()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create order: ' . $e->getMessage()
            ], 500);
        }
    }

    // ✅ 3. VERIFY PAYMENT & CREATE SUBSCRIPTION
    public function verifyPayment(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'razorpay_order_id' => 'required|string',
                'razorpay_payment_id' => 'required|string',
                'razorpay_signature' => 'required|string'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 400);
            }

            $orderId = $request->razorpay_order_id;
            $paymentId = $request->razorpay_payment_id;
            $signature = $request->razorpay_signature;

            // Find payment record
            $payment = Payment::where('order_id', $orderId)->first();

            if (!$payment) {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment record not found'
                ], 404);
            }

            DB::beginTransaction();

            try {
                // ✅ VERIFY SIGNATURE
                $generatedSignature = hash_hmac('sha256', $orderId . '|' . $paymentId, $this->razorpaySecret);

                if ($generatedSignature !== $signature) {
                    $payment->update([
                        'payment_status' => 'failed',
                        'order_status' => 'failed',
                        'error_code' => 'SIGNATURE_MISMATCH',
                        'error_description' => 'Payment signature verification failed',
                        'payment_failed_at' => now()
                    ]);

                    DB::commit();

                    Log::warning('Payment signature mismatch', [
                        'order_id' => $orderId,
                        'payment_id' => $paymentId
                    ]);

                    return response()->json([
                        'success' => false,
                        'message' => 'Payment verification failed. Invalid signature.'
                    ], 400);
                }

                // ✅ FETCH PAYMENT DETAILS FROM RAZORPAY
                $razorpayPayment = $this->razorpayApi->payment->fetch($paymentId);

                Log::info('Razorpay payment details', [
                    'payment_id' => $paymentId,
                    'status' => $razorpayPayment['status'],
                    'amount' => $razorpayPayment['amount']
                ]);

                // Update payment record
                $payment->update([
                    'transaction_id' => $paymentId,
                    'razorpay_signature' => $signature,
                    'signature_verified' => true,
                    'payment_status' => 'captured',
                    'order_status' => 'paid',
                    'payment_method' => $razorpayPayment['method'] ?? null,
                    'payment_method_type' => $razorpayPayment['card']['type'] ?? $razorpayPayment['wallet'] ?? $razorpayPayment['vpa'] ?? null,
                    'card_network' => $razorpayPayment['card']['network'] ?? null,
                    'card_last4' => $razorpayPayment['card']['last4'] ?? null,
                    'bank' => $razorpayPayment['bank'] ?? null,
                    'wallet' => $razorpayPayment['wallet'] ?? null,
                    'vpa' => $razorpayPayment['vpa'] ?? null,
                    'amount_paid' => $razorpayPayment['amount'] / 100,
                    'payment_completed_at' => now(),
                    'razorpay_payment_response' => $razorpayPayment
                ]);

                // ✅ CREATE SUBSCRIPTION
                $plan = Plan::find($payment->plan_id);
                $startsAt = now();
                $durationDays = $plan->duration_days ?? $this->getDurationDays($plan->duration_type);
                $expiresAt = $plan->duration_type !== 'lifetime' ? $startsAt->copy()->addDays($durationDays) : null;

                $subscription = PlanSubscription::create([
                    'user_id' => $payment->user_id,
                    'plan_id' => $plan->id,
                    'plan_name' => $plan->name,
                    'price_paid' => $plan->price,
                    'duration_type' => $plan->duration_type,
                    'selected_features' => $plan->features,
                    'starts_at' => $startsAt,
                    'expires_at' => $expiresAt,
                    'status' => 'active'
                ]);

                // Link subscription to payment
                $payment->update([
                    'plan_subscription_id' => $subscription->id
                ]);

                DB::commit();

                Log::info('Payment verified and subscription created', [
                    'payment_id' => $payment->payment_id,
                    'subscription_id' => $subscription->id,
                    'user_id' => $payment->user_id
                ]);

                $user = User::find($payment->user_id);

                return response()->json([
                    'success' => true,
                    'message' => 'Payment successful! Your subscription is now active.',
                    'data' => [
                        'payment' => [
                            'payment_id' => $payment->payment_id,
                            'transaction_id' => $payment->transaction_id,
                            'order_id' => $payment->order_id,
                            'amount_paid' => $payment->amount_paid,
                            'payment_method' => $payment->payment_method,
                            'payment_status' => $payment->payment_status,
                            'paid_at' => $payment->payment_completed_at->format('Y-m-d H:i:s')
                        ],
                        'subscription' => [
                            'id' => $subscription->id,
                            'plan_name' => $subscription->plan_name,
                            'price_paid' => $subscription->price_paid,
                            'duration_type' => $subscription->duration_type,
                            'starts_at' => $subscription->starts_at->format('Y-m-d H:i:s'),
                            'expires_at' => $subscription->expires_at ? $subscription->expires_at->format('Y-m-d H:i:s') : null,
                            'status' => $subscription->status,
                            'days_remaining' => $subscription->expires_at ? max(0, now()->diffInDays($subscription->expires_at)) : 'Unlimited'
                        ],
                        'user' => [
                            'id' => $user->id,
                            'name' => $user->name,
                            'email' => $user->email
                        ]
                    ]
                ]);

            } catch (\Exception $e) {
                DB::rollback();
                throw $e;
            }

        } catch (\Exception $e) {
            Log::error('Verify payment error', [
                'error' => $e->getMessage(),
                'line' => $e->getLine()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Payment verification failed: ' . $e->getMessage()
            ], 500);
        }
    }

    // ✅ 4. PAYMENT FAILED HANDLER
    public function paymentFailed(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'razorpay_order_id' => 'required|string',
                'error_code' => 'nullable|string',
                'error_description' => 'nullable|string'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 400);
            }

            $payment = Payment::where('order_id', $request->razorpay_order_id)->first();

            if ($payment) {
                $payment->update([
                    'payment_status' => 'failed',
                    'order_status' => 'failed',
                    'error_code' => $request->error_code,
                    'error_description' => $request->error_description,
                    'payment_failed_at' => now()
                ]);

                Log::info('Payment failed', [
                    'payment_id' => $payment->payment_id,
                    'order_id' => $payment->order_id,
                    'error_code' => $request->error_code
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Payment failed. Please try again.',
                'error_code' => $request->error_code,
                'error_description' => $request->error_description
            ]);

        } catch (\Exception $e) {
            Log::error('Payment failed handler error', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to record payment failure'
            ], 500);
        }
    }

    // ✅ 5. GET PAYMENT HISTORY
    public function getPaymentHistory(Request $request): JsonResponse
    {
        try {
            $authHeader = $request->header('Authorization');
            if (!$authHeader || !str_contains($authHeader, 'Bearer ')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Authorization token required'
                ], 401);
            }

            $token = str_replace('Bearer ', '', $authHeader);
            $decodedToken = base64_decode($token);
            $tokenParts = explode(':', $decodedToken);

            if (count($tokenParts) < 3) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid token format'
                ], 401);
            }

            $userId = $tokenParts[0];
            $user = User::find($userId);

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found'
                ], 404);
            }

            $payments = Payment::where('user_id', $userId)
                ->with(['plan', 'planSubscription'])
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($payment) {
                    return [
                        'payment_id' => $payment->payment_id,
                        'transaction_id' => $payment->transaction_id,
                        'order_id' => $payment->order_id,
                        'receipt_number' => $payment->receipt_number,
                        'amount' => $payment->total_amount,
                        'currency' => $payment->currency,
                        'payment_method' => $payment->payment_method_display,
                        'payment_status' => $payment->payment_status,
                        'order_status' => $payment->order_status,
                        'plan_name' => $payment->plan->name ?? 'N/A',
                        'payment_date' => $payment->payment_completed_at ? $payment->payment_completed_at->format('Y-m-d H:i:s') : null,
                        'created_at' => $payment->created_at->format('Y-m-d H:i:s')
                    ];
                });

            $stats = [
                'total_payments' => $payments->count(),
                'successful_payments' => $payments->where('payment_status', 'captured')->count(),
                'failed_payments' => $payments->where('payment_status', 'failed')->count(),
                'total_amount_paid' => $payments->where('payment_status', 'captured')->sum('amount')
            ];

            return response()->json([
                'success' => true,
                'message' => 'Payment history retrieved successfully',
                'data' => [
                    'payments' => $payments,
                    'stats' => $stats,
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Get payment history error', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve payment history'
            ], 500);
        }
    }

    // Continue with other methods (getUserSubscriptions, cancelSubscription, etc.)...

    // ✅ 6. GET USER SUBSCRIPTIONS
    public function getUserSubscriptions(Request $request): JsonResponse
    {
        // [Same as your original code - no changes needed]
        try {
            $authHeader = $request->header('Authorization');
            if (!$authHeader || !str_contains($authHeader, 'Bearer ')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Authorization token required'
                ], 401);
            }

            $token = str_replace('Bearer ', '', $authHeader);
            $decodedToken = base64_decode($token);
            $tokenParts = explode(':', $decodedToken);

            if (count($tokenParts) < 3) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid token format'
                ], 401);
            }

            $userId = $tokenParts[0];
            $user = User::find($userId);

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found'
                ], 404);
            }

            $subscriptions = PlanSubscription::where('user_id', $userId)
                ->with('plan')
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($subscription) {
                    $isActive = $subscription->status === 'active' &&
                               ($subscription->expires_at === null || $subscription->expires_at > now());

                    $daysRemaining = 'Unlimited';
                    if ($subscription->expires_at) {
                        $daysRemaining = max(0, now()->diffInDays($subscription->expires_at));
                    }

                    return [
                        'id' => $subscription->id,
                        'plan_id' => $subscription->plan_id,
                        'plan_name' => $subscription->plan_name,
                        'price_paid' => $subscription->price_paid,
                        'duration_type' => $subscription->duration_type,
                        'status' => $subscription->status,
                        'is_active' => $isActive,
                        'days_remaining' => $daysRemaining,
                        'starts_at' => $subscription->starts_at ? $subscription->starts_at->format('Y-m-d H:i:s') : null,
                        'expires_at' => $subscription->expires_at ? $subscription->expires_at->format('Y-m-d H:i:s') : null,
                        'subscribed_at' => $subscription->created_at->format('Y-m-d H:i:s')
                    ];
                });

            return response()->json([
                'success' => true,
                'message' => 'User subscriptions retrieved successfully',
                'data' => [
                    'subscriptions' => $subscriptions,
                    'active_subscription' => $subscriptions->where('is_active', true)->first()
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Get subscriptions error', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve subscriptions'
            ], 500);
        }
    }

    // ✅ 7. CANCEL SUBSCRIPTION
    public function cancelSubscription(Request $request): JsonResponse
    {
        // [Keep your original cancelSubscription code]
        try {
            $authHeader = $request->header('Authorization');
            if (!$authHeader || !str_contains($authHeader, 'Bearer ')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Authorization token required'
                ], 401);
            }

            $token = str_replace('Bearer ', '', $authHeader);
            $decodedToken = base64_decode($token);
            $tokenParts = explode(':', $decodedToken);

            if (count($tokenParts) < 3) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid token format'
                ], 401);
            }

            $userId = $tokenParts[0];
            $user = User::find($userId);

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found'
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'subscription_id' => 'required|integer',
                'reason' => 'nullable|string|max:500'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 400);
            }

            $subscription = PlanSubscription::where('id', $request->subscription_id)
                ->where('user_id', $userId)
                ->first();

            if (!$subscription) {
                return response()->json([
                    'success' => false,
                    'message' => 'Subscription not found'
                ], 404);
            }

            if ($subscription->status === 'cancelled') {
                return response()->json([
                    'success' => false,
                    'message' => 'Subscription is already cancelled'
                ], 400);
            }

            $subscription->update(['status' => 'cancelled']);

            Log::info('Subscription cancelled', [
                'subscription_id' => $subscription->id,
                'reason' => $request->reason
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Subscription cancelled successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Cancel subscription error', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to cancel subscription'
            ], 500);
        }
    }

    // ✅ HELPER METHOD
    private function getDurationDays($durationType): int
    {
        return match($durationType) {
            'daily' => 1,
            'weekly' => 7,
            'monthly' => 30,
            'quarterly' => 90,
            'yearly' => 365,
            'lifetime' => 0,
            default => 30
        };
    }
}
