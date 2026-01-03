<?php

namespace App\Services;

use App\Models\PlanSubscription;
use App\Models\Payment;
use App\Models\User;
use Razorpay\Api\Api;
use Illuminate\Support\Facades\Log;
use Exception;

class RazorpayService
{
    protected $api;
    protected $keyId;
    protected $keySecret;

    public function __construct()
    {
        $this->keyId = config('services.razorpay.key');
        $this->keySecret = config('services.razorpay.secret');
        $this->api = new Api($this->keyId, $this->keySecret);
    }

    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    // ✅ CUSTOMER MANAGEMENT
    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

    /**
     * Create or retrieve Razorpay customer
     */
    public function createOrGetCustomer(User $user): array
    {
        try {
            // Check if customer already exists
            if ($user->razorpay_customer_id) {
                try {
                    $customer = $this->api->customer->fetch($user->razorpay_customer_id);
                    return [
                        'success' => true,
                        'customer' => $customer,
                        'customer_id' => $customer->id
                    ];
                } catch (Exception $e) {
                    // Customer not found, create new
                    Log::warning('Razorpay customer not found, creating new', [
                        'user_id' => $user->id,
                        'old_customer_id' => $user->razorpay_customer_id
                    ]);
                }
            }

            // Create new customer
            $customer = $this->api->customer->create([
                'name' => $user->name,
                'email' => $user->email,
                'contact' => $user->contact_number ?? $user->phone,
                'fail_existing' => 0,
                'notes' => [
                    'user_id' => $user->id,
                    'created_at' => now()->toDateTimeString()
                ]
            ]);

            // Update user with customer ID
            $user->update(['razorpay_customer_id' => $customer->id]);

            Log::info('Razorpay customer created', [
                'user_id' => $user->id,
                'customer_id' => $customer->id
            ]);

            return [
                'success' => true,
                'customer' => $customer,
                'customer_id' => $customer->id
            ];

        } catch (Exception $e) {
            Log::error('Create Razorpay customer failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    // ✅ SUBSCRIPTION CREATION
    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

    /**
     * Create Razorpay subscription
     */
    public function createSubscription(User $user, string $planType, array $additionalData = []): array
    {
        try {
            // Get plan ID from env based on type
            $planId = $this->getPlanId($planType);

            if (!$planId) {
                throw new Exception("Invalid plan type: {$planType}");
            }

            // Create or get customer
            $customerResult = $this->createOrGetCustomer($user);

            if (!$customerResult['success']) {
                throw new Exception('Failed to create customer: ' . $customerResult['error']);
            }

            $customerId = $customerResult['customer_id'];

            // Prepare subscription data
            $subscriptionData = [
                'plan_id' => $planId,
                'customer_id' => $customerId,
                'quantity' => 1,
                'total_count' => 0, // Unlimited billing cycles
                'customer_notify' => 1,
                'notes' => [
                    'user_id' => $user->id,
                    'plan_type' => $planType,
                    'created_at' => now()->toDateTimeString()
                ]
            ];

            // Add optional start date (future date if needed)
            if (isset($additionalData['start_at'])) {
                $subscriptionData['start_at'] = $additionalData['start_at'];
            }

            // Create subscription
            $razorpaySubscription = $this->api->subscription->create($subscriptionData);

            Log::info('Razorpay subscription created', [
                'user_id' => $user->id,
                'subscription_id' => $razorpaySubscription->id,
                'plan_type' => $planType,
                'customer_id' => $customerId
            ]);

            // Get plan details
            $planDetails = $this->getPlanDetails($planType);

            // Create local subscription record
            $subscription = PlanSubscription::create([
                'user_id' => $user->id,
                'plan_id' => $planDetails['id'],
                'plan_name' => $planDetails['name'],
                'price_paid' => $planDetails['price'],
                'setup_fee' => 1.00,
                'duration_type' => $planDetails['duration'],
                'razorpay_subscription_id' => $razorpaySubscription->id,
                'razorpay_customer_id' => $customerId,
                'razorpay_plan_id' => $planId,
                'subscription_status' => 'created',
                'status' => 'pending',
                'starts_at' => now(),
                'expires_at' => null, // Will be set after first payment
                'next_billing_at' => null,
                'total_billing_cycles' => 0,
                'completed_billing_cycles' => 0,
                'auto_renew' => true,
                'is_trial' => false,
                'subscription_metadata' => [
                    'razorpay_data' => $razorpaySubscription->toArray()
                ]
            ]);

            return [
                'success' => true,
                'subscription' => $subscription,
                'razorpay_subscription' => $razorpaySubscription,
                'subscription_id' => $razorpaySubscription->id,
                'short_url' => $razorpaySubscription->short_url ?? null
            ];

        } catch (Exception $e) {
            Log::error('Create subscription failed', [
                'user_id' => $user->id,
                'plan_type' => $planType,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    // ✅ SUBSCRIPTION MANAGEMENT
    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

    /**
     * Cancel subscription
     */
    public function cancelSubscription(PlanSubscription $subscription, bool $cancelAtCycleEnd = false): array
    {
        try {
            if (!$subscription->razorpay_subscription_id) {
                throw new Exception('Razorpay subscription ID not found');
            }

            $razorpaySubscription = $this->api->subscription->fetch($subscription->razorpay_subscription_id);
            $razorpaySubscription->cancel(['cancel_at_cycle_end' => $cancelAtCycleEnd ? 1 : 0]);

            // Update local subscription
            $subscription->cancel('User requested cancellation');

            Log::info('Subscription cancelled', [
                'subscription_id' => $subscription->id,
                'razorpay_subscription_id' => $subscription->razorpay_subscription_id,
                'cancel_at_cycle_end' => $cancelAtCycleEnd
            ]);

            return [
                'success' => true,
                'message' => 'Subscription cancelled successfully',
                'subscription' => $subscription
            ];

        } catch (Exception $e) {
            Log::error('Cancel subscription failed', [
                'subscription_id' => $subscription->id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Pause subscription
     */
    public function pauseSubscription(PlanSubscription $subscription): array
    {
        try {
            if (!$subscription->razorpay_subscription_id) {
                throw new Exception('Razorpay subscription ID not found');
            }

            $razorpaySubscription = $this->api->subscription->fetch($subscription->razorpay_subscription_id);
            $razorpaySubscription->pause(['pause_at' => 'now']);

            // Update local subscription
            $subscription->pause();

            Log::info('Subscription paused', [
                'subscription_id' => $subscription->id,
                'razorpay_subscription_id' => $subscription->razorpay_subscription_id
            ]);

            return [
                'success' => true,
                'message' => 'Subscription paused successfully',
                'subscription' => $subscription
            ];

        } catch (Exception $e) {
            Log::error('Pause subscription failed', [
                'subscription_id' => $subscription->id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Resume subscription
     */
    public function resumeSubscription(PlanSubscription $subscription): array
    {
        try {
            if (!$subscription->razorpay_subscription_id) {
                throw new Exception('Razorpay subscription ID not found');
            }

            $razorpaySubscription = $this->api->subscription->fetch($subscription->razorpay_subscription_id);
            $razorpaySubscription->resume(['resume_at' => 'now']);

            // Update local subscription
            $subscription->resume();

            Log::info('Subscription resumed', [
                'subscription_id' => $subscription->id,
                'razorpay_subscription_id' => $subscription->razorpay_subscription_id
            ]);

            return [
                'success' => true,
                'message' => 'Subscription resumed successfully',
                'subscription' => $subscription
            ];

        } catch (Exception $e) {
            Log::error('Resume subscription failed', [
                'subscription_id' => $subscription->id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    // ✅ PAYMENT VERIFICATION
    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

    /**
     * Verify payment signature
     */
    public function verifyPaymentSignature(array $attributes): bool
    {
        try {
            $this->api->utility->verifyPaymentSignature($attributes);
            return true;
        } catch (Exception $e) {
            Log::error('Payment signature verification failed', [
                'error' => $e->getMessage(),
                'attributes' => $attributes
            ]);
            return false;
        }
    }

    /**
     * Verify webhook signature
     */
    public function verifyWebhookSignature(string $payload, string $signature): bool
    {
        try {
            $webhookSecret = config('services.razorpay.webhook_secret');

            $expectedSignature = hash_hmac('sha256', $payload, $webhookSecret);

            return hash_equals($expectedSignature, $signature);
        } catch (Exception $e) {
            Log::error('Webhook signature verification failed', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    // ✅ HELPER METHODS
    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

    /**
     * Get plan ID from type
     */
    protected function getPlanId(string $planType): ?string
    {
        $plans = [
            'monthly' => config('services.razorpay.plan_monthly'),
            'quarterly' => config('services.razorpay.plan_quarterly'),
            'half_yearly' => config('services.razorpay.plan_halfyearly'),
            'yearly' => config('services.razorpay.plan_yearly'),
        ];

        return $plans[$planType] ?? null;
    }

    /**
     * Get plan details
     */
    protected function getPlanDetails(string $planType): array
    {
        $plans = [
            'monthly' => [
                'id' => 1,
                'name' => 'Monthly Plan',
                'price' => 249.00,
                'duration' => 'monthly'
            ],
            'quarterly' => [
                'id' => 2,
                'name' => 'Quarterly Plan',
                'price' => 699.00,
                'duration' => 'quarterly'
            ],
            'half_yearly' => [
                'id' => 3,
                'name' => 'Half-Yearly Plan',
                'price' => 1199.00,
                'duration' => 'half_yearly'
            ],
            'yearly' => [
                'id' => 4,
                'name' => 'Yearly Plan',
                'price' => 1999.00,
                'duration' => 'yearly'
            ],
        ];

        return $plans[$planType] ?? [];
    }

    /**
     * Get available plans
     */
    public function getAvailablePlans(): array
    {
        return [
            [
                'id' => 1,
                'type' => 'monthly',
                'name' => 'Monthly Plan',
                'price' => 249.00,
                'setup_fee' => 1.00,
                'duration' => '1 month',
                'billing_cycle' => 'Every month',
                'features' => [
                    'Unlimited load posting',
                    'GPS tracking',
                    'Part load support',
                    'Job postings',
                    'Basic support'
                ]
            ],
            [
                'id' => 2,
                'type' => 'quarterly',
                'name' => 'Quarterly Plan',
                'price' => 699.00,
                'setup_fee' => 1.00,
                'duration' => '3 months',
                'billing_cycle' => 'Every 3 months',
                'savings' => '₹48 (6.4%)',
                'features' => [
                    'All Monthly Plan features',
                    'Priority support',
                    'Advanced analytics',
                    'Best value'
                ]
            ],
            [
                'id' => 3,
                'type' => 'half_yearly',
                'name' => 'Half-Yearly Plan',
                'price' => 1199.00,
                'setup_fee' => 1.00,
                'duration' => '6 months',
                'billing_cycle' => 'Every 6 months',
                'savings' => '₹295 (20%)',
                'features' => [
                    'All Quarterly Plan features',
                    '24/7 support',
                    'Premium features',
                    'Great savings'
                ]
            ],
            [
                'id' => 4,
                'type' => 'yearly',
                'name' => 'Yearly Plan',
                'price' => 1999.00,
                'setup_fee' => 1.00,
                'duration' => '12 months',
                'billing_cycle' => 'Every year',
                'savings' => '₹989 (33%)',
                'popular' => true,
                'features' => [
                    'All Half-Yearly Plan features',
                    'Dedicated account manager',
                    'API access',
                    'Maximum savings',
                    'Best for serious businesses'
                ]
            ]
        ];
    }
}
