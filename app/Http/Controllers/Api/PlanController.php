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
use Carbon\Carbon;

class PlanController extends Controller
{
    private $razorpayKey;
    private $razorpaySecret;

    public function __construct()
    {
        $this->razorpayKey = config('services.razorpay.key');
        $this->razorpaySecret = config('services.razorpay.secret');

        Log::info('PlanController initialized', [
            'key_present' => !empty($this->razorpayKey),
            'secret_present' => !empty($this->razorpaySecret)
        ]);
    }

    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    // ✅ 1. GET ALL PLANS - WITH COMPLETE PRICING INFO
    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    public function getPlans(): JsonResponse
    {
        try {
            $plans = Plan::active()
                ->ordered()
                ->get()
                ->map(function ($plan) {
                    return [
                        // Basic Info
                        'id' => $plan->id,
                        'name' => $plan->name,
                        'description' => $plan->description,

                        // ✅ Pricing Details (UPDATED)
                        'price' => $plan->price,                              // ₹249 (recurring)
                        'setup_fee' => $plan->setup_fee,                      // ₹1 (one-time)
                        'first_payment' => $plan->setup_fee,                  // ₹1 (first charge)
                        'recurring_payment' => $plan->price,                  // ₹249 (auto-renewal)

                        // Formatted Prices
                        'formatted_price' => $plan->formatted_price,          // ₹249.00
                        'formatted_setup_fee' => $plan->formatted_setup_fee,  // ₹1.00
                        'price_per_month' => $plan->price_per_month,          // ₹83.00 (for quarterly)

                        // Duration
                        'duration_type' => $plan->duration_type,
                        'duration_days' => $plan->duration_days ?? $this->getDurationDays($plan->duration_type),
                        'duration_text' => $plan->duration_text ?? ucfirst($plan->duration_type),
                        'duration_in_months' => $plan->duration_in_months,    // 3 (for quarterly)

                        // Features
                        'features' => $plan->features_list,                   // Use model accessor
                        'features_count' => count($plan->features_list),

                        // Popularity & Status
                        'is_popular' => $plan->is_popular,
                        'is_active' => $plan->is_active,

                        // UI Elements
                        'button_text' => $plan->button_text ?? 'Subscribe Now',
                        'button_color' => $plan->button_color ?? '#4caf50',
                        'contact_info' => $plan->contact_info,
                        'sort_order' => $plan->sort_order,

                        // ✅ Subscription Details
                        'auto_renewal' => true,
                        'has_setup_fee' => $plan->hasSetupFee(),
                        'is_free' => $plan->isFree(),

                        // Stats
                        'subscribers_count' => $plan->subscribers_count,
                        'total_subscribers' => $plan->total_subscribers,
                        'has_subscribers' => $plan->hasSubscribers(),

                        // ✅ Payment Flow Info
                        'payment_flow' => [
                            'step_1' => [
                                'description' => 'Initial Setup Payment',
                                'amount' => $plan->setup_fee,
                                'formatted_amount' => $plan->formatted_setup_fee,
                                'type' => 'one_time'
                            ],
                            'step_2' => [
                                'description' => 'Auto-Renewal Payment',
                                'amount' => $plan->price,
                                'formatted_amount' => $plan->formatted_price,
                                'type' => 'recurring',
                                'frequency' => $plan->duration_type,
                                'interval_days' => $plan->duration_days
                            ]
                        ],

                        // ✅ Complete Pricing Breakdown
                        'pricing_details' => $plan->pricing_details,

                        // Timestamps
                        'created_at' => $plan->created_at,
                        'updated_at' => $plan->updated_at
                    ];
                });

            // ✅ Additional Information
            $additionalInfo = [
                'subscription_info' => [
                    'setup_fee_description' => '₹1 setup fee for card tokenization and subscription activation',
                    'auto_renewal_description' => 'Automatic renewal on billing date - no manual action needed',
                    'billing_cycle' => 'Charges occur based on plan duration (monthly/quarterly/yearly)',
                    'first_payment' => 'Only setup fee charged initially',
                    'subsequent_payments' => 'Plan price charged automatically on renewal date'
                ],
                'cancellation_policy' => [
                    'anytime_cancellation' => true,
                    'no_hidden_charges' => true,
                    'refund_policy' => 'Pro-rata refunds available on annual plans',
                    'cancellation_notice' => 'Cancel before renewal date to avoid next charge'
                ],
                'pricing_info' => [
                    'inclusive_of_taxes' => true,
                    'gst_applicable' => '18% GST included in pricing',
                    'currency' => 'INR',
                    'pricing_model' => 'Subscription-based with auto-renewal'
                ],
                'support_info' => [
                    'availability' => '24/7 customer support',
                    'channels' => ['Email', 'Phone', 'Chat'],
                    'response_time' => 'Within 2 hours',
                    'support_email' => 'support@digittransway.com',
                    'support_phone' => '+91 70334 43759'
                ],
                'payment_methods' => [
                    'cards' => ['Visa', 'Mastercard', 'RuPay', 'American Express'],
                    'upi' => true,
                    'netbanking' => true,
                    'wallets' => ['Paytm', 'PhonePe', 'Google Pay']
                ]
            ];

            // ✅ Summary Statistics
            $statistics = [
                'total_plans' => $plans->count(),
                'active_plans' => $plans->where('is_active', true)->count(),
                'popular_plans' => $plans->where('is_popular', true)->count(),
                'total_subscribers' => PlanSubscription::whereIn('subscription_status', ['authenticated', 'active'])->count(),
                'price_range' => [
                    'min' => $plans->min('price'),
                    'max' => $plans->max('price'),
                    'min_formatted' => '₹' . number_format($plans->min('price'), 2),
                    'max_formatted' => '₹' . number_format($plans->max('price'), 2)
                ],
                'setup_fee_range' => [
                    'min' => $plans->min('setup_fee'),
                    'max' => $plans->max('setup_fee'),
                    'standard' => 1.00,
                    'formatted' => '₹1.00'
                ]
            ];

            return response()->json([
                'success' => true,
                'message' => 'Plans retrieved successfully',
                'data' => [
                    'plans' => $plans,
                    'statistics' => $statistics,
                    'important_information' => $additionalInfo
                ],
                'meta' => [
                    'timestamp' => now()->toIso8601String(),
                    'api_version' => '2.0',
                    'total_records' => $plans->count()
                ]
            ], 200);
        } catch (\Exception $e) {
            Log::error('Get Plans Error', [
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve plans: ' . $e->getMessage(),
                'error_code' => 'PLAN_FETCH_ERROR'
            ], 500);
        }
    }


    /**
     * ✅ SUBSCRIBE TO PLAN - CORRECTED
     * Monthly: ₹1 first payment + auto-renew
     * Others (Quarterly/Half-Yearly/Yearly): Full amount + auto-renew
     */
    public function subscribePlan(Request $request): JsonResponse
    {
        try {
            Log::info('═══════════════════════════════════════════════════════════════');
            Log::info('🚀 SUBSCRIBE PLAN STARTED');
            Log::info('═══════════════════════════════════════════════════════════════', [
                'timestamp' => now()->toDateTimeString()
            ]);

            // ✅ 1. TOKEN VALIDATION
            $authHeader = $request->header('Authorization');
            if (!$authHeader || !str_contains($authHeader, 'Bearer ')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Authorization token required'
                ], 401);
            }

            $token = str_replace('Bearer ', '', $authHeader);
            $tokenParts = explode(':', base64_decode($token));
            $userId = $tokenParts[0] ?? null;

            if (!$userId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid token format'
                ], 401);
            }

            $user = User::find($userId);
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found'
                ], 404);
            }

            Log::info('✅ User validated', ['user_id' => $userId, 'name' => $user->name]);

            // ✅ 2. VALIDATE PLAN
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

            $plan = Plan::find($request->plan_id);
            if (!$plan || !$plan->is_active) {
                return response()->json([
                    'success' => false,
                    'message' => 'Plan not available'
                ], 404);
            }

            Log::info('✅ Plan validated', [
                'plan_id' => $plan->id,
                'plan_name' => $plan->name,
                'price' => $plan->price,
                'duration_type' => $plan->duration_type
            ]);

            // ✅ 3. CHECK EXISTING SUBSCRIPTION
            $existingValidSubscription = PlanSubscription::where('user_id', $userId)
                ->whereIn('subscription_status', ['authenticated', 'active', 'halted'])
                ->whereNull('deleted_at')
                ->first();

            if ($existingValidSubscription) {
                return response()->json([
                    'success' => false,
                    'message' => 'You already have an active subscription'
                ], 400);
            }

            DB::beginTransaction();

            try {
                // ✅ 4. DETERMINE PAYMENT AMOUNTS
                $planType = $plan->duration_type;
                $planPrice = floatval($plan->price);

                // ✅ MONTHLY = ₹1, OTHERS = FULL AMOUNT
                $isMonthlyPlan = ($planType === 'monthly');
                $firstPaymentAmount = $isMonthlyPlan ? 1.00 : $planPrice;

                Log::info('💰 Payment structure', [
                    'plan_type' => $planType,
                    'is_monthly' => $isMonthlyPlan,
                    'first_payment' => $firstPaymentAmount,
                    'recurring_amount' => $planPrice
                ]);

                // ✅ 5. GET RAZORPAY PLAN ID
                $razorpayPlanId = config("services.razorpay.plan_{$planType}");

                if (!$razorpayPlanId) {
                    throw new \Exception("Razorpay plan not configured for: {$planType}");
                }

                // ✅ 6. FORMAT CUSTOMER DATA
                $contactNumber = $user->contact_number;
                if ($contactNumber && !preg_match('/^\+91/', $contactNumber)) {
                    $cleanNumber = preg_replace('/[^0-9]/', '', $contactNumber);
                    if (strlen($cleanNumber) == 10) {
                        $contactNumber = '+91' . $cleanNumber;
                    }
                }

                $customerEmail = $user->email && filter_var($user->email, FILTER_VALIDATE_EMAIL)
                    ? $user->email
                    : 'noreply@digittransway.com';

                // ✅ 7. CREATE/GET RAZORPAY CUSTOMER
                $customerId = $user->razorpay_customer_id;

                if (!$customerId) {
                    $customerData = [
                        'name' => $user->name,
                        'email' => $customerEmail,
                        'contact' => $contactNumber,
                        'fail_existing' => 0
                    ];

                    $ch = curl_init('https://api.razorpay.com/v1/customers');
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_POST, true);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($customerData));
                    curl_setopt($ch, CURLOPT_USERPWD, $this->razorpayKey . ':' . $this->razorpaySecret);
                    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
                    curl_setopt($ch, CURLOPT_TIMEOUT, 30);

                    $customerResponse = curl_exec($ch);
                    $customerHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                    curl_close($ch);

                    if ($customerHttpCode === 200 || $customerHttpCode === 201) {
                        $customerInfo = json_decode($customerResponse, true);
                        $customerId = $customerInfo['id'];
                        $user->update(['razorpay_customer_id' => $customerId]);
                        Log::info('✅ Customer created', ['customer_id' => $customerId]);
                    } else {
                        // Try fetching existing customer
                        Log::warning('⚠️ Customer creation failed, attempting fetch');
                        // ... (existing fetch logic) ...
                    }
                } else {
                    Log::info('✅ Using existing customer', ['customer_id' => $customerId]);
                }

                // ✅ 8. CALCULATE BILLING DATES
                $firstBillingDate = match ($planType) {
                    'monthly' => now()->addMonth(),
                    'quarterly' => now()->addMonths(3),
                    'halfyearly' => now()->addMonths(6),
                    'yearly' => now()->addYear(),
                    default => now()->addMonth()
                };

                $totalBillingCycles = match ($planType) {
                    'monthly' => 12,
                    'quarterly' => 4,
                    'halfyearly' => 2,
                    'yearly' => 1,
                    default => 12
                };

                Log::info('✅ Billing calculated', [
                    'first_billing_date' => $firstBillingDate->toDateString(),
                    'total_cycles' => $totalBillingCycles
                ]);

                // ✅ 9. CREATE RAZORPAY SUBSCRIPTION WITH ADDON
                $subscriptionData = [
                    'plan_id' => $razorpayPlanId,
                    'customer_id' => $customerId,
                    'quantity' => 1,
                    'total_count' => $totalBillingCycles,
                    'customer_notify' => 1,
                    'start_at' => $firstBillingDate->timestamp,  // ✅ ALL plans defer to next cycle
                    'notes' => [
                        'user_id' => $userId,
                        'plan_type' => $planType,
                        'plan_name' => $plan->name,
                        'first_payment' => $firstPaymentAmount,
                        'recurring_amount' => $planPrice,
                        'first_billing_date' => $firstBillingDate->toDateString()
                    ]
                ];

                // ✅ CRITICAL: ADD ADDON FOR ALL PLANS
                // Monthly: ₹1 addon
                // Others: Full amount addon (to avoid ₹5 refundable charge)
                $addonAmountPaise = intval($firstPaymentAmount * 100);

                $subscriptionData['addons'] = [
                    [
                        'item' => [
                            'name' => $isMonthlyPlan
                                ? 'Card Verification Fee'
                                : ucfirst($planType) . ' Plan - First Payment',
                            'amount' => $addonAmountPaise,
                            'currency' => 'INR',
                            'description' => $isMonthlyPlan
                                ? 'One-time card verification'
                                : 'First payment for ' . $planType . ' plan'
                        ]
                    ]
                ];

                Log::info('📋 Subscription data with addon', [
                    'addon_amount_rupees' => $firstPaymentAmount,
                    'addon_amount_paise' => $addonAmountPaise,
                    'is_monthly' => $isMonthlyPlan
                ]);

                $ch = curl_init('https://api.razorpay.com/v1/subscriptions');
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($subscriptionData));
                curl_setopt($ch, CURLOPT_USERPWD, $this->razorpayKey . ':' . $this->razorpaySecret);
                curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
                curl_setopt($ch, CURLOPT_TIMEOUT, 30);

                $subscriptionResponse = curl_exec($ch);
                $subscriptionHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);

                if ($subscriptionHttpCode !== 200 && $subscriptionHttpCode !== 201) {
                    $errorData = json_decode($subscriptionResponse, true);
                    Log::error('❌ Subscription creation failed', ['error' => $errorData]);
                    throw new \Exception('Razorpay API Error: ' . ($errorData['error']['description'] ?? 'Unknown error'));
                }

                $razorpaySubscription = json_decode($subscriptionResponse, true);

                Log::info('✅ Subscription created', [
                    'subscription_id' => $razorpaySubscription['id'],
                    'status' => $razorpaySubscription['status']
                ]);

                // ✅ 10. CREATE LOCAL SUBSCRIPTION
                $subscription = PlanSubscription::create([
                    'user_id' => $userId,
                    'plan_id' => $plan->id,
                    'plan_name' => $plan->name,
                    'price_paid' => $planPrice,
                    'setup_fee' => $firstPaymentAmount,
                    'duration_type' => $planType,
                    'selected_features' => $plan->features ?? [],
                    'razorpay_subscription_id' => $razorpaySubscription['id'],
                    'razorpay_customer_id' => $customerId,
                    'razorpay_plan_id' => $razorpayPlanId,
                    'subscription_status' => 'pending',
                    'status' => 'pending',
                    'starts_at' => now(),
                    'expires_at' => $firstBillingDate,
                    'total_billing_cycles' => $totalBillingCycles,
                    'completed_billing_cycles' => 0,
                    'auto_renew' => true,
                    'is_trial' => false,
                    'subscription_metadata' => [
                        'razorpay_data' => $razorpaySubscription,
                        'pricing' => [
                            'first_payment' => $firstPaymentAmount,
                            'recurring_price' => $planPrice,
                            'first_billing_date' => $firstBillingDate->toDateString()
                        ]
                    ]
                ]);

                // ✅ 11. CREATE PAYMENT RECORD
                $payment = Payment::create([
                    'user_id' => $userId,
                    'plan_id' => $plan->id,
                    'plan_subscription_id' => $subscription->id,
                    'razorpay_subscription_id' => $razorpaySubscription['id'],
                    'razorpay_customer_id' => $customerId,
                    'payment_id' => 'PENDING_' . time(),
                    'order_id' => $razorpaySubscription['id'],
                    'receipt_number' => Payment::generateReceiptNumber(),
                    'amount' => $firstPaymentAmount,
                    'total_amount' => $firstPaymentAmount,
                    'currency' => 'INR',
                    'payment_gateway' => 'razorpay',
                    'payment_status' => 'created',
                    'payment_type' => 'subscription',  // ✅ FIX: Use valid enum value
                    'is_subscription_payment' => true,
                    'billing_cycle_number' => 0,
                    'email' => $customerEmail,
                    'contact' => $contactNumber,
                    'customer_name' => $user->name,
                    'payment_initiated_at' => now(),
                    'payment_metadata' => [
                        'first_payment' => $firstPaymentAmount,
                        'recurring_amount' => $planPrice,
                        'next_billing_date' => $firstBillingDate->toDateString(),
                        'payment_cycle' => $isMonthlyPlan ? 'setup' : 'first_cycle'  // ✅ Store detail in metadata
                    ]
                ]);

                Log::info('✅ Payment record created', [
                    'payment_id' => $payment->id,
                    'amount' => $firstPaymentAmount,
                    'type' => 'subscription'
                ]);


                DB::commit();

                Log::info('🎉 SUBSCRIPTION CREATED SUCCESSFULLY');
                Log::info('═══════════════════════════════════════════════════════════════');

                return response()->json([
                    'success' => true,
                    'message' => $isMonthlyPlan
                        ? 'Pay ₹1 now. Auto-renews at ₹' . number_format($planPrice, 2) . ' monthly'
                        : 'Pay ₹' . number_format($firstPaymentAmount, 2) . ' now. Auto-renews ' . $planType,
                    'data' => [
                        'razorpay_key' => $this->razorpayKey,
                        'razorpay_subscription_id' => $razorpaySubscription['id'],
                        'subscription_id' => $subscription->id,
                        'payment_id' => $payment->payment_id,
                        'subscription_status' => 'pending',
                        'payment_status' => 'created',

                        'is_monthly_plan' => $isMonthlyPlan,
                        'first_payment_amount' => $firstPaymentAmount,
                        'first_payment_formatted' => '₹' . number_format($firstPaymentAmount, 2),
                        'recurring_payment_amount' => $planPrice,
                        'recurring_payment_formatted' => '₹' . number_format($planPrice, 2),

                        'next_billing_date' => $firstBillingDate->toDateString(),
                        'next_billing_amount' => $planPrice,
                        'currency' => 'INR',

                        'customer' => [
                            'name' => $user->name,
                            'email' => $customerEmail,
                            'contact' => $contactNumber,
                            'razorpay_customer_id' => $customerId
                        ],

                        'plan' => [
                            'id' => $plan->id,
                            'name' => $plan->name,
                            'recurring_price' => $planPrice,
                            'first_payment' => $firstPaymentAmount,
                            'duration_type' => $planType,
                            'features' => $plan->features ?? []
                        ],

                        'payment_flow' => [
                            'step_1' => [
                                'title' => $isMonthlyPlan ? 'Card Verification' : ucfirst($planType) . ' Payment',
                                'amount' => $firstPaymentAmount,
                                'formatted' => '₹' . number_format($firstPaymentAmount, 2),
                                'status' => 'awaiting_payment'
                            ],
                            'step_2' => [
                                'title' => 'Auto-Renewal',
                                'amount' => $planPrice,
                                'formatted' => '₹' . number_format($planPrice, 2),
                                'date' => $firstBillingDate->format('d M Y'),
                                'status' => 'scheduled'
                            ]
                        ]
                    ]
                ], 201);
            } catch (\Exception $e) {
                DB::rollback();
                throw $e;
            }
        } catch (\Exception $e) {
            if (DB::transactionLevel() > 0) {
                DB::rollback();
            }

            Log::error('═══════════════════════════════════════════════════════════════');
            Log::error('❌ SUBSCRIBE PLAN FAILED', [
                'error' => $e->getMessage(),
                'line' => $e->getLine()
            ]);
            Log::error('═══════════════════════════════════════════════════════════════');

            return response()->json([
                'success' => false,
                'message' => 'Failed to create subscription',
                'error' => $e->getMessage()
            ], 500);
        }
    }





    /**
     * ✅ VERIFY PAYMENT & ACTIVATE SUBSCRIPTION
     */
    public function verifyPayment(Request $request): JsonResponse
    {
        try {
            Log::info('🔐 ============ PAYMENT VERIFICATION STARTED ============');
            Log::info('📥 Request Data Received', [
                'method' => $request->method(),
                'url' => $request->url(),
                'all_inputs' => $request->all()
            ]);

            // ✅ Extract variables
            $paymentId = $request->input('razorpay_payment_id');
            $subscriptionId = $request->input('razorpay_subscription_id');
            $signature = $request->input('razorpay_signature');
            $subId = $request->input('subscription_id');

            Log::info('📦 Extracted Variables', [
                'razorpay_payment_id' => $paymentId,
                'razorpay_subscription_id' => $subscriptionId,
                'razorpay_signature' => $signature,
                'subscription_id' => $subId
            ]);

            // ✅ Validation
            $validator = Validator::make([
                'razorpay_payment_id' => $paymentId,
                'razorpay_subscription_id' => $subscriptionId,
                'razorpay_signature' => $signature,
                'subscription_id' => $subId
            ], [
                'razorpay_payment_id' => 'required|string',
                'razorpay_subscription_id' => 'required|string',
                'razorpay_signature' => 'required|string',
                'subscription_id' => 'required|integer'
            ]);

            if ($validator->fails()) {
                Log::warning('❌ Validation Failed', $validator->errors()->toArray());
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 400);
            }

            Log::info('✅ Validation Passed');

            // ✅ Authorization Check
            $authHeader = $request->header('Authorization');
            Log::info('🔑 Authorization Header', [
                'header_present' => !empty($authHeader),
                'header_value' => $authHeader ? 'Bearer token present' : 'NO HEADER'
            ]);

            if (!$authHeader || !str_contains($authHeader, 'Bearer ')) {
                Log::warning('❌ Authorization Failed - Invalid Bearer Token');
                return response()->json(['success' => false, 'message' => 'Authorization required'], 401);
            }

            $token = str_replace('Bearer ', '', $authHeader);
            $tokenParts = explode(':', base64_decode($token));
            $userId = $tokenParts[0] ?? null;

            Log::info('👤 User Extraction from Token', [
                'token_length' => strlen($token),
                'decoded_parts' => count($tokenParts),
                'user_id' => $userId
            ]);

            $user = User::find($userId);
            if (!$user) {
                Log::warning('❌ User Not Found', ['user_id' => $userId]);
                return response()->json(['success' => false, 'message' => 'User not found'], 404);
            }

            Log::info('✅ User Found', [
                'user_id' => $user->id,
                'user_email' => $user->email
            ]);

            // ✅ Find Subscription
            Log::info('🔍 Finding Subscription', ['subscription_id' => $subId, 'user_id' => $userId]);

            $subscription = PlanSubscription::find($subId);
            if (!$subscription) {
                Log::warning('❌ Subscription Not Found', ['subscription_id' => $subId]);
                return response()->json(['success' => false, 'message' => 'Subscription not found'], 404);
            }

            if ($subscription->user_id != $userId) {
                Log::warning('❌ Subscription User Mismatch', [
                    'expected_user_id' => $userId,
                    'subscription_user_id' => $subscription->user_id
                ]);
                return response()->json(['success' => false, 'message' => 'Subscription not found'], 404);
            }

            Log::info('✅ Subscription Found', [
                'subscription_id' => $subscription->id,
                'subscription_status' => $subscription->subscription_status,
                'razorpay_subscription_id' => $subscription->razorpay_subscription_id
            ]);

            // ✅ Verify Subscription ID Match
            Log::info('🔐 Verifying Subscription ID Match', [
                'expected_razorpay_sub_id' => $subscription->razorpay_subscription_id,
                'received_razorpay_sub_id' => $subscriptionId,
                'match' => $subscription->razorpay_subscription_id === $subscriptionId ? 'YES ✅' : 'NO ❌'
            ]);

            if ($subscription->razorpay_subscription_id !== $subscriptionId) {
                Log::warning('❌ Razorpay Subscription ID Mismatch', [
                    'expected' => $subscription->razorpay_subscription_id,
                    'received' => $subscriptionId
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Subscription mismatch'
                ], 400);
            }

            // ✅ Find Payment Record
            Log::info('🔍 Finding Payment Record', [
                'razorpay_subscription_id' => $subscriptionId,
                'looking_for_status' => 'created'
            ]);

            $payment = Payment::where('razorpay_subscription_id', $subscriptionId)
                ->where('payment_status', 'created')
                ->first();

            if (!$payment) {
                Log::warning('❌ Payment Record Not Found', [
                    'razorpay_subscription_id' => $subscriptionId,
                    'status_filter' => 'created'
                ]);

                // Debug: Show all payments for this subscription
                $allPayments = Payment::where('razorpay_subscription_id', $subscriptionId)
                    ->select('id', 'payment_status', 'payment_id', 'created_at')
                    ->get();

                Log::info('💡 All payments found for subscription', [
                    'count' => $allPayments->count(),
                    'payments' => $allPayments->toArray()
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Payment record not found',
                    'debug' => [
                        'razorpay_subscription_id' => $subscriptionId,
                        'all_payments_count' => $allPayments->count()
                    ]
                ], 404);
            }

            Log::info('✅ Payment Record Found', [
                'payment_id' => $payment->id,
                'payment_status' => $payment->payment_status,
                'amount' => $payment->amount
            ]);

            // ✅ Start Transaction
            Log::info('💳 Starting Database Transaction');
            DB::beginTransaction();

            try {
                // ✅ VERIFY RAZORPAY SIGNATURE
                Log::info('🔐 Generating Expected Signature', [
                    'subscription_id' => $subscriptionId,
                    'payment_id' => $paymentId
                ]);

                $signaturePayload = $paymentId . '|' . $subscriptionId;
                $expectedSignature = hash_hmac('sha256', $signaturePayload, $this->razorpaySecret);

                Log::info('🔐 Signature Verification', [
                    'payload' => $signaturePayload,
                    'expected_signature' => $expectedSignature,
                    'received_signature' => $signature,
                    'match' => $expectedSignature === $signature ? '✅ YES' : '❌ NO',
                    'razorpay_secret_masked' => substr($this->razorpaySecret, 0, 10) . '***'
                ]);

                if ($expectedSignature !== $signature) {
                    Log::error('❌ SIGNATURE MISMATCH - PAYMENT VERIFICATION FAILED', [
                        'expected' => $expectedSignature,
                        'received' => $signature,
                        'match' => false
                    ]);

                    $payment->update([
                        'payment_status' => 'failed',
                        'error_code' => 'SIGNATURE_MISMATCH',
                        'error_description' => 'Razorpay signature verification failed'
                    ]);

                    DB::commit();

                    return response()->json([
                        'success' => false,
                        'message' => 'Payment verification failed',
                        'debug' => [
                            'reason' => 'Signature mismatch',
                            'expected_signature' => $expectedSignature,
                            'received_signature' => $signature
                        ]
                    ], 400);
                }

                Log::info('✅ SIGNATURE VERIFIED SUCCESSFULLY');

                // ✅ UPDATE SUBSCRIPTION
                Log::info('📝 Updating Subscription Status', [
                    'subscription_id' => $subscription->id,
                    'from_status' => $subscription->status,
                    'to_status' => 'active'
                ]);

                $subscription->update([
                    'subscription_status' => 'authenticated',
                    'status' => 'active'
                ]);

                Log::info('✅ Subscription Updated', [
                    'subscription_id' => $subscription->id,
                    'new_status' => 'active'
                ]);

                // ✅ UPDATE PAYMENT
                Log::info('📝 Updating Payment Status', [
                    'payment_id' => $payment->id,
                    'from_status' => $payment->payment_status,
                    'to_status' => 'completed'
                ]);

                $payment->update([
                    'payment_status' => 'completed',
                    'payment_id' => $paymentId,
                    'razorpay_payment_id' => $paymentId,
                    'razorpay_signature' => $signature,
                    'signature_verified' => true,
                    'payment_completed_at' => now()
                ]);

                Log::info('✅ Payment Updated', [
                    'payment_id' => $payment->id,
                    'razorpay_payment_id' => $paymentId,
                    'new_status' => 'completed',
                    'verified_at' => now()
                ]);

                // ✅ Commit Transaction
                DB::commit();
                Log::info('✅ Database Transaction Committed Successfully');

                Log::info('🎉 ============ PAYMENT VERIFICATION COMPLETE ============', [
                    'subscription_id' => $subscription->id,
                    'payment_id' => $paymentId,
                    'status' => 'active',
                    'verified_at' => now()
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Payment verified! Subscription activated.',
                    'data' => [
                        'subscription_id' => $subscription->id,
                        'status' => 'active',
                        'plan_name' => $subscription->plan_name,
                        'expires_at' => $subscription->expires_at
                    ]
                ]);
            } catch (\Exception $e) {
                Log::error('❌ Exception During Transaction', [
                    'error_message' => $e->getMessage(),
                    'error_code' => $e->getCode(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ]);

                DB::rollback();
                Log::warning('⏮️ Database Transaction Rolled Back');
                throw $e;
            }
        } catch (\Exception $e) {
            if (DB::transactionLevel() > 0) {
                DB::rollback();
                Log::warning('⏮️ Database Transaction Rolled Back (Outer Catch)');
            }

            Log::error('❌ ============ PAYMENT VERIFICATION FAILED ============', [
                'error_message' => $e->getMessage(),
                'error_code' => $e->getCode(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Verification failed: ' . $e->getMessage()
            ], 500);
        }
    }


    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    // ✅ 4. PAYMENT FAILED HANDLER
    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
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

    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    // ✅ PAYMENT CALLBACK (After Razorpay Payment)
    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

    public function paymentCallback(Request $request): JsonResponse
    {
        try {
            Log::info('💰 Payment callback received', [
                'payment_id' => $request->input('razorpay_payment_id'),
                'subscription_id' => $request->input('razorpay_subscription_id'),
                'all_params' => $request->all()
            ]);

            // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
            // ✅ STEP 1: VALIDATE REQUEST
            // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
            $validator = Validator::make($request->all(), [
                'razorpay_payment_id' => 'required|string',
                'razorpay_subscription_id' => 'required|string',
                'razorpay_signature' => 'required|string'
            ]);

            if ($validator->fails()) {
                Log::warning('❌ Callback validation failed', ['errors' => $validator->errors()]);
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid callback parameters',
                    'errors' => $validator->errors()
                ], 422);
            }

            // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
            // ✅ STEP 2: VERIFY RAZORPAY SIGNATURE
            // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
            $razorpayPaymentId = $request->input('razorpay_payment_id');
            $razorpaySubscriptionId = $request->input('razorpay_subscription_id');
            $razorpaySignature = $request->input('razorpay_signature');

            $verifyData = $razorpaySubscriptionId . '|' . $razorpayPaymentId;
            $expectedSignature = hash_hmac('sha256', $verifyData, $this->razorpaySecret);

            if ($expectedSignature !== $razorpaySignature) {
                Log::warning('❌ Invalid payment signature', [
                    'payment_id' => $razorpayPaymentId,
                    'expected' => $expectedSignature,
                    'actual' => $razorpaySignature
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Invalid payment signature',
                    'code' => 'INVALID_SIGNATURE'
                ], 403);
            }

            Log::info('✅ Payment signature verified', ['payment_id' => $razorpayPaymentId]);

            // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
            // ✅ STEP 3: FIND SUBSCRIPTION
            // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
            $subscription = PlanSubscription::where(
                'razorpay_subscription_id',
                $razorpaySubscriptionId
            )->first();

            if (!$subscription) {
                Log::error('❌ Subscription not found', [
                    'razorpay_subscription_id' => $razorpaySubscriptionId
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Subscription not found',
                    'code' => 'SUBSCRIPTION_NOT_FOUND'
                ], 404);
            }

            Log::info('✅ Subscription found', [
                'subscription_id' => $subscription->id,
                'user_id' => $subscription->user_id
            ]);

            DB::beginTransaction();

            try {
                // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
                // ✅ STEP 4: UPDATE SUBSCRIPTION STATUS TO ACTIVE
                // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
                $subscription->update([
                    'subscription_status' => 'active',
                    'status' => 'active'
                ]);

                Log::info('✅ Subscription activated', [
                    'subscription_id' => $subscription->id,
                    'status' => 'active'
                ]);

                // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
                // ✅ STEP 5: UPDATE PAYMENT TABLE
                // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
                $payment = Payment::where('plan_subscription_id', $subscription->id)
                    ->where('payment_status', 'created')
                    ->first();

                if ($payment) {
                    $payment->update([
                        'payment_status' => 'completed',
                        'razorpay_payment_id' => $razorpayPaymentId,
                        'payment_completed_at' => now(),
                        'signature_verified' => true
                    ]);

                    Log::info('✅ Payment marked as completed', [
                        'payment_id' => $payment->id,
                        'razorpay_payment_id' => $razorpayPaymentId
                    ]);
                } else {
                    Log::warning('⚠️ Payment record not found for subscription', [
                        'subscription_id' => $subscription->id
                    ]);
                }

                // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
                // ✅ STEP 6: UPDATE BILLING CYCLES
                // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
                $subscription->update([
                    'completed_billing_cycles' => 1,
                    'remaining_billing_cycles' => max(0, $subscription->total_billing_cycles - 1)
                ]);

                Log::info('✅ Billing cycles updated', [
                    'completed' => 1,
                    'remaining' => max(0, $subscription->total_billing_cycles - 1)
                ]);

                DB::commit();

                Log::info('🎉 Payment callback processed successfully', [
                    'subscription_id' => $subscription->id,
                    'payment_id' => $razorpayPaymentId
                ]);

                // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
                // ✅ RETURN SUCCESS
                // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
                return response()->json([
                    'success' => true,
                    'message' => 'Payment processed successfully! Subscription activated.',
                    'data' => [
                        'subscription_id' => $subscription->id,
                        'status' => 'active',
                        'razorpay_payment_id' => $razorpayPaymentId,
                        'plan_name' => $subscription->plan_name,
                        'user_id' => $subscription->user_id,
                        'activated_at' => now()->toDateTimeString(),
                        'expires_at' => $subscription->expires_at?->toDateTimeString()
                    ]
                ], 200);
            } catch (\Exception $e) {
                DB::rollback();
                throw $e;
            }
        } catch (\Exception $e) {
            if (DB::transactionLevel() > 0) {
                DB::rollback();
            }

            Log::error('❌ Payment callback processing failed', [
                'error' => $e->getMessage(),
                'line' => $e->getLine()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to process payment callback: ' . $e->getMessage(),
                'code' => 'CALLBACK_ERROR'
            ], 500);
        }
    }


    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    // ✅ 6. GET USER SUBSCRIPTIONS
    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    public function getUserSubscriptions(Request $request): JsonResponse
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
            $tokenParts = explode(':', base64_decode($token));
            $userId = $tokenParts[0] ?? null;

            if (!$userId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid token'
                ], 401);
            }

            $user = User::find($userId);
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found'
                ], 404);
            }

            $subscriptions = PlanSubscription::where('user_id', $userId)
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($subscription) {
                    return [
                        'id' => $subscription->id,
                        'plan_id' => $subscription->plan_id,
                        'plan_name' => $subscription->plan_name,
                        'price_paid' => $subscription->price_paid,
                        'setup_fee' => $subscription->setup_fee,
                        'duration_type' => $subscription->duration_type,
                        'subscription_status' => $subscription->subscription_status,
                        'status' => $subscription->status,
                        'is_active' => $subscription->isActive(),
                        'auto_renew' => $subscription->auto_renew,
                        'razorpay_subscription_id' => $subscription->razorpay_subscription_id,
                        'completed_billing_cycles' => $subscription->completed_billing_cycles,
                        'starts_at' => $subscription->starts_at ? $subscription->starts_at->format('Y-m-d H:i:s') : null,
                        'expires_at' => $subscription->expires_at ? $subscription->expires_at->format('Y-m-d H:i:s') : null,
                        'next_billing_at' => $subscription->next_billing_at ? $subscription->next_billing_at->format('Y-m-d H:i:s') : null,
                        'created_at' => $subscription->created_at->format('Y-m-d H:i:s')
                    ];
                });

            $activeSubscription = $subscriptions->where('is_active', true)->first();

            return response()->json([
                'success' => true,
                'message' => 'Subscriptions retrieved successfully',
                'data' => [
                    'subscriptions' => $subscriptions,
                    'active_subscription' => $activeSubscription,
                    'total_subscriptions' => $subscriptions->count()
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Get subscriptions error', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve subscriptions'
            ], 500);
        }
    }

    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    // ✅ 7. GET PAYMENT HISTORY
    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
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
            $tokenParts = explode(':', base64_decode($token));
            $userId = $tokenParts[0] ?? null;

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
                        'amount' => $payment->total_amount,
                        'payment_type' => $payment->payment_type,
                        'payment_status' => $payment->payment_status,
                        'billing_cycle_number' => $payment->billing_cycle_number,
                        'plan_name' => $payment->plan->name ?? 'N/A',
                        'payment_date' => $payment->payment_completed_at ? $payment->payment_completed_at->format('Y-m-d H:i:s') : null,
                        'created_at' => $payment->created_at->format('Y-m-d H:i:s')
                    ];
                });

            $stats = [
                'total_payments' => $payments->count(),
                'successful_payments' => $payments->where('payment_status', 'captured')->count(),
                'total_amount_paid' => $payments->where('payment_status', 'captured')->sum('amount')
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

    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    // ✅ 8. CANCEL SUBSCRIPTION
    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    public function cancelSubscription(Request $request): JsonResponse
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
            $tokenParts = explode(':', base64_decode($token));
            $userId = $tokenParts[0] ?? null;

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

            if ($subscription->isCancelled()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Subscription is already cancelled'
                ], 400);
            }

            DB::beginTransaction();

            try {
                // Cancel on Razorpay if subscription ID exists
                if ($subscription->razorpay_subscription_id) {
                    $ch = curl_init('https://api.razorpay.com/v1/subscriptions/' . $subscription->razorpay_subscription_id . '/cancel');
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_POST, true);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['cancel_at_cycle_end' => 0]));
                    curl_setopt($ch, CURLOPT_USERPWD, $this->razorpayKey . ':' . $this->razorpaySecret);
                    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

                    $response = curl_exec($ch);
                    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                    curl_close($ch);

                    Log::info('Razorpay subscription cancellation', [
                        'http_code' => $httpCode,
                        'subscription_id' => $subscription->razorpay_subscription_id
                    ]);
                }

                // Update local subscription
                $subscription->cancel($request->reason);

                DB::commit();

                Log::info('Subscription cancelled', [
                    'subscription_id' => $subscription->id,
                    'user_id' => $userId,
                    'reason' => $request->reason
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Subscription cancelled successfully',
                    'data' => [
                        'subscription_id' => $subscription->id,
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
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to cancel subscription: ' . $e->getMessage()
            ], 500);
        }
    }

    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    // ✅ 9. CONTACT SALES (IF NEEDED)
    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    public function contactSales(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'email' => 'required|email',
                'phone' => 'required|string',
                'message' => 'required|string'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 400);
            }

            // Log contact request
            Log::info('Contact sales request', [
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone
            ]);

            // Here you can send email or save to database

            return response()->json([
                'success' => true,
                'message' => 'Thank you for contacting us! Our team will reach out to you soon.'
            ]);
        } catch (\Exception $e) {
            Log::error('Contact sales error', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to submit contact request'
            ], 500);
        }
    }

    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    // ✅ HELPER METHOD
    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    private function getDurationDays($durationType): int
    {
        return match ($durationType) {
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
