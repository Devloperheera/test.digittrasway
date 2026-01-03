<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PlanSubscription;
use App\Models\Payment;
use App\Models\User;
use App\Services\RazorpayService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class SubscriptionController extends Controller
{
    protected $razorpayService;

    public function __construct(RazorpayService $razorpayService)
    {
        $this->razorpayService = $razorpayService;
    }

    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    // ✅ 1. GET AVAILABLE SUBSCRIPTION PLANS
    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

    /**
     * Get all available subscription plans
     */
    public function getPlans(Request $request): JsonResponse
    {
        try {
            $plans = $this->razorpayService->getAvailablePlans();

            return response()->json([
                'success' => true,
                'message' => 'Subscription plans retrieved successfully',
                'data' => [
                    'plans' => $plans,
                    'total_plans' => count($plans),
                    'currency' => 'INR',
                    'setup_fee_info' => 'All plans require ₹1 setup fee for first payment',
                    'auto_renewal' => 'Plans auto-renew unless cancelled',
                    'cancellation' => 'Cancel anytime - no hidden charges'
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Get plans error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve plans: ' . $e->getMessage()
            ], 500);
        }
    }

    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    // ✅ 2. CREATE SUBSCRIPTION
    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

    /**
     * Create new Razorpay subscription
     */
    public function createSubscription(Request $request): JsonResponse
    {
        try {
            // Validate request
            $validator = Validator::make($request->all(), [
                'plan_type' => 'required|string|in:monthly,quarterly,half_yearly,yearly',
                'user_id' => 'sometimes|integer'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 400);
            }

            // Get authenticated user
            $user = $this->getAuthenticatedUser($request);

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found or invalid token'
                ], 401);
            }

            // Check for existing active subscription
            $existingSubscription = PlanSubscription::where('user_id', $user->id)
                ->whereIn('subscription_status', ['authenticated', 'active'])
                ->first();

            if ($existingSubscription) {
                return response()->json([
                    'success' => false,
                    'message' => 'You already have an active subscription',
                    'data' => [
                        'existing_subscription' => $existingSubscription->getSummary()
                    ]
                ], 400);
            }

            DB::beginTransaction();

            try {
                // Create subscription via Razorpay
                $result = $this->razorpayService->createSubscription(
                    $user,
                    $request->plan_type,
                    $request->only(['start_at'])
                );

                if (!$result['success']) {
                    throw new \Exception($result['error']);
                }

                DB::commit();

                Log::info('Subscription created successfully', [
                    'user_id' => $user->id,
                    'subscription_id' => $result['subscription_id'],
                    'plan_type' => $request->plan_type
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Subscription created successfully! Complete the ₹1 payment to activate.',
                    'data' => [
                        'subscription' => $result['subscription']->getSummary(),
                        'razorpay_subscription_id' => $result['subscription_id'],
                        'payment_url' => $result['short_url'],
                        'instructions' => [
                            '1. Click on payment_url to complete ₹1 setup fee payment',
                            '2. Your card will be tokenized for future auto-payments',
                            '3. Subscription activates after successful payment',
                            '4. Auto-renewal happens on due date'
                        ]
                    ]
                ], 201);

            } catch (\Exception $e) {
                DB::rollback();
                throw $e;
            }

        } catch (\Exception $e) {
            Log::error('Create subscription error', [
                'error' => $e->getMessage(),
                'user_id' => $user->id ?? null,
                'plan_type' => $request->plan_type ?? null,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create subscription: ' . $e->getMessage()
            ], 500);
        }
    }

    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    // ✅ 3. GET USER SUBSCRIPTIONS
    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

    /**
     * Get user's subscriptions
     */
    public function getUserSubscriptions(Request $request): JsonResponse
    {
        try {
            $user = $this->getAuthenticatedUser($request);

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found or invalid token'
                ], 401);
            }

            // Get all subscriptions
            $subscriptions = PlanSubscription::where('user_id', $user->id)
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($subscription) {
                    return $subscription->getSummary();
                });

            // Get active subscription
            $activeSubscription = PlanSubscription::where('user_id', $user->id)
                ->razorpayActive()
                ->first();

            // Get subscription stats
            $stats = [
                'total_subscriptions' => $subscriptions->count(),
                'active_subscriptions' => $subscriptions->where('is_active', true)->count(),
                'cancelled_subscriptions' => $subscriptions->where('is_cancelled', true)->count(),
                'total_amount_spent' => $subscriptions->sum('price_paid')
            ];

            return response()->json([
                'success' => true,
                'message' => 'Subscriptions retrieved successfully',
                'data' => [
                    'active_subscription' => $activeSubscription ? $activeSubscription->getSummary() : null,
                    'subscription_history' => $subscriptions,
                    'stats' => $stats,
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'razorpay_customer_id' => $user->razorpay_customer_id
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Get user subscriptions error', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve subscriptions'
            ], 500);
        }
    }

    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    // ✅ 4. GET SUBSCRIPTION BY ID
    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

    /**
     * Get subscription details by ID
     */
    public function getSubscriptionById(Request $request, $id): JsonResponse
    {
        try {
            $user = $this->getAuthenticatedUser($request);

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found or invalid token'
                ], 401);
            }

            $subscription = PlanSubscription::where('id', $id)
                ->where('user_id', $user->id)
                ->first();

            if (!$subscription) {
                return response()->json([
                    'success' => false,
                    'message' => 'Subscription not found'
                ], 404);
            }

            // Get payment history for this subscription
            $paymentHistory = $subscription->getPaymentHistory();

            return response()->json([
                'success' => true,
                'message' => 'Subscription details retrieved successfully',
                'data' => [
                    'subscription' => $subscription->getSummary(),
                    'payment_history' => $paymentHistory,
                    'can_cancel' => $subscription->canCancel(),
                    'can_pause' => $subscription->canPause(),
                    'can_resume' => $subscription->canResume()
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Get subscription by ID error', [
                'subscription_id' => $id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve subscription details'
            ], 500);
        }
    }

    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    // ✅ 5. CANCEL SUBSCRIPTION
    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

    /**
     * Cancel subscription
     */
    public function cancelSubscription(Request $request, $id): JsonResponse
    {
        try {
            $user = $this->getAuthenticatedUser($request);

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found or invalid token'
                ], 401);
            }

            $validator = Validator::make($request->all(), [
                'cancel_at_cycle_end' => 'sometimes|boolean',
                'reason' => 'sometimes|string|max:500'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 400);
            }

            $subscription = PlanSubscription::where('id', $id)
                ->where('user_id', $user->id)
                ->first();

            if (!$subscription) {
                return response()->json([
                    'success' => false,
                    'message' => 'Subscription not found'
                ], 404);
            }

            if (!$subscription->canCancel()) {
                return response()->json([
                    'success' => false,
                    'message' => 'This subscription cannot be cancelled',
                    'current_status' => $subscription->subscription_status
                ], 400);
            }

            DB::beginTransaction();

            try {
                // Cancel via Razorpay
                $result = $this->razorpayService->cancelSubscription(
                    $subscription,
                    $request->input('cancel_at_cycle_end', false)
                );

                if (!$result['success']) {
                    throw new \Exception($result['error']);
                }

                DB::commit();

                Log::info('Subscription cancelled', [
                    'subscription_id' => $subscription->id,
                    'user_id' => $user->id,
                    'reason' => $request->reason
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Subscription cancelled successfully',
                    'data' => [
                        'subscription' => $subscription->fresh()->getSummary(),
                        'cancelled_at' => $subscription->cancelled_at,
                        'cancellation_reason' => $subscription->cancellation_reason
                    ]
                ]);

            } catch (\Exception $e) {
                DB::rollback();
                throw $e;
            }

        } catch (\Exception $e) {
            Log::error('Cancel subscription error', [
                'subscription_id' => $id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to cancel subscription: ' . $e->getMessage()
            ], 500);
        }
    }

    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    // ✅ 6. PAUSE SUBSCRIPTION
    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

    /**
     * Pause subscription
     */
    public function pauseSubscription(Request $request, $id): JsonResponse
    {
        try {
            $user = $this->getAuthenticatedUser($request);

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found or invalid token'
                ], 401);
            }

            $subscription = PlanSubscription::where('id', $id)
                ->where('user_id', $user->id)
                ->first();

            if (!$subscription) {
                return response()->json([
                    'success' => false,
                    'message' => 'Subscription not found'
                ], 404);
            }

            if (!$subscription->canPause()) {
                return response()->json([
                    'success' => false,
                    'message' => 'This subscription cannot be paused',
                    'current_status' => $subscription->subscription_status
                ], 400);
            }

            DB::beginTransaction();

            try {
                $result = $this->razorpayService->pauseSubscription($subscription);

                if (!$result['success']) {
                    throw new \Exception($result['error']);
                }

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Subscription paused successfully',
                    'data' => [
                        'subscription' => $subscription->fresh()->getSummary()
                    ]
                ]);

            } catch (\Exception $e) {
                DB::rollback();
                throw $e;
            }

        } catch (\Exception $e) {
            Log::error('Pause subscription error', [
                'subscription_id' => $id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to pause subscription: ' . $e->getMessage()
            ], 500);
        }
    }

    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    // ✅ 7. RESUME SUBSCRIPTION
    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

    /**
     * Resume subscription
     */
    public function resumeSubscription(Request $request, $id): JsonResponse
    {
        try {
            $user = $this->getAuthenticatedUser($request);

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found or invalid token'
                ], 401);
            }

            $subscription = PlanSubscription::where('id', $id)
                ->where('user_id', $user->id)
                ->first();

            if (!$subscription) {
                return response()->json([
                    'success' => false,
                    'message' => 'Subscription not found'
                ], 404);
            }

            if (!$subscription->canResume()) {
                return response()->json([
                    'success' => false,
                    'message' => 'This subscription cannot be resumed',
                    'current_status' => $subscription->subscription_status
                ], 400);
            }

            DB::beginTransaction();

            try {
                $result = $this->razorpayService->resumeSubscription($subscription);

                if (!$result['success']) {
                    throw new \Exception($result['error']);
                }

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Subscription resumed successfully',
                    'data' => [
                        'subscription' => $subscription->fresh()->getSummary()
                    ]
                ]);

            } catch (\Exception $e) {
                DB::rollback();
                throw $e;
            }

        } catch (\Exception $e) {
            Log::error('Resume subscription error', [
                'subscription_id' => $id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to resume subscription: ' . $e->getMessage()
            ], 500);
        }
    }

    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    // ✅ 8. GET PAYMENT HISTORY
    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

    /**
     * Get user's payment history
     */
    public function getPaymentHistory(Request $request): JsonResponse
    {
        try {
            $user = $this->getAuthenticatedUser($request);

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found or invalid token'
                ], 401);
            }

            $payments = Payment::where('user_id', $user->id)
                ->subscriptionPayments()
                ->with(['planSubscription'])
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($payment) {
                    return $payment->getSummary();
                });

            // Calculate stats
            $stats = [
                'total_payments' => $payments->count(),
                'successful_payments' => $payments->where('is_successful', true)->count(),
                'failed_payments' => $payments->where('payment_status', 'failed')->count(),
                'total_amount_paid' => $payments->where('is_successful', true)->sum('amount'),
                'setup_fee_payments' => $payments->where('payment_type', 'setup_fee')->count(),
                'recurring_payments' => $payments->where('payment_type', 'recurring')->count()
            ];

            return response()->json([
                'success' => true,
                'message' => 'Payment history retrieved successfully',
                'data' => [
                    'payments' => $payments,
                    'stats' => $stats
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

    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    // ✅ HELPER METHOD - GET AUTHENTICATED USER
    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

    /**
     * Get authenticated user from token
     */
    protected function getAuthenticatedUser(Request $request): ?User
    {
        try {
            $authHeader = $request->header('Authorization');

            if (!$authHeader || !str_contains($authHeader, 'Bearer ')) {
                return null;
            }

            $token = str_replace('Bearer ', '', $authHeader);
            $decodedToken = base64_decode($token);
            $tokenParts = explode(':', $decodedToken);

            if (count($tokenParts) < 3) {
                return null;
            }

            $userId = $tokenParts[0];

            return User::find($userId);

        } catch (\Exception $e) {
            Log::error('Get authenticated user error', [
                'error' => $e->getMessage()
            ]);

            return null;
        }
    }
}
