<?php
// app/Http/Controllers/Api/PlanSubscriptionController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PlanSubscription;
use App\Models\Vendor;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class PlanSubscriptionController extends Controller
{
    // ✅ GET ALL AVAILABLE PLANS (Predefined)
    public function getAvailablePlans(): JsonResponse
    {
        try {
            $plans = [
                [
                    'id' => 1,
                    'plan_name' => 'Basic Plan',
                    'price' => 99.00,
                    'duration_type' => 'monthly',
                    'description' => 'Perfect for small vendors',
                    'features' => ['Basic support', '10 products', 'Standard delivery']
                ],
                [
                    'id' => 2,
                    'plan_name' => 'Pro Plan',
                    'price' => 199.00,
                    'duration_type' => 'monthly',
                    'description' => 'Best for growing businesses',
                    'features' => ['Priority support', '50 products', 'Fast delivery', 'Analytics']
                ],
                [
                    'id' => 3,
                    'plan_name' => 'Premium Plan',
                    'price' => 499.00,
                    'duration_type' => 'monthly',
                    'description' => 'Complete solution for enterprises',
                    'features' => ['24/7 support', 'Unlimited products', 'Express delivery', 'Advanced analytics', 'API access']
                ],
                [
                    'id' => 4,
                    'plan_name' => 'Weekly Basic',
                    'price' => 29.00,
                    'duration_type' => 'weekly',
                    'description' => 'Try our basic plan for a week',
                    'features' => ['Basic support', '5 products', 'Standard delivery']
                ],
                [
                    'id' => 5,
                    'plan_name' => 'Annual Pro',
                    'price' => 1999.00,
                    'duration_type' => 'yearly',
                    'description' => 'Annual Pro plan with discount',
                    'features' => ['Priority support', 'Unlimited products', 'Fast delivery', 'Analytics', '2 months free']
                ],
                [
                    'id' => 6,
                    'plan_name' => 'Daily Trial',
                    'price' => 9.00,
                    'duration_type' => 'daily',
                    'description' => '1-day trial plan',
                    'features' => ['Basic support', '3 products', 'Standard delivery']
                ]
            ];

            return response()->json([
                'success' => true,
                'message' => 'Available plans retrieved successfully',
                'data' => [
                    'plans' => $plans,
                    'total_plans' => count($plans)
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Get Available Plans Error', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve plans: ' . $e->getMessage()
            ], 500);
        }
    }

    // ✅ SUBSCRIBE TO A PLAN
    public function subscribePlan(Request $request): JsonResponse
    {
        try {
            // Token validation
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

            $userId = $tokenParts[0]; // This is vendor_id from token
            $vendor = Vendor::find($userId);

            if (!$vendor) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found'
                ], 404);
            }

            // Validate request
            $validator = Validator::make($request->all(), [
                'plan_name' => 'required|string|max:255',
                'price' => 'required|numeric|min:0',
                'duration_type' => 'required|string|in:daily,weekly,monthly,yearly'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 400);
            }

            // Create plan subscription
            $subscription = PlanSubscription::create([
                'user_id' => $userId,
                'plan_name' => $request->plan_name,
                'price' => $request->price,
                'duration_type' => $request->duration_type
            ]);

            Log::info('Plan subscription created', [
                'user_id' => $userId,
                'plan_name' => $request->plan_name,
                'price' => $request->price,
                'duration_type' => $request->duration_type,
                'subscription_id' => $subscription->id
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Plan subscribed successfully',
                'data' => [
                    'subscription' => [
                        'id' => $subscription->id,
                        'user_id' => $subscription->user_id,
                        'plan_name' => $subscription->plan_name,
                        'price' => $subscription->price,
                        'duration_type' => $subscription->duration_type,
                        'duration_text' => $subscription->duration_text,
                        'subscribed_at' => $subscription->created_at,
                        'updated_at' => $subscription->updated_at
                    ],
                    'user' => [
                        'id' => $vendor->id,
                        'name' => $vendor->name,
                        'contact_number' => $vendor->contact_number,
                        'email' => $vendor->email
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Plan subscription error', [
                'user_id' => $userId ?? null,
                'error' => $e->getMessage(),
                'line' => $e->getLine()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to subscribe plan: ' . $e->getMessage()
            ], 500);
        }
    }

    // ✅ GET USER'S SUBSCRIPTION HISTORY
    public function getUserSubscriptions(Request $request): JsonResponse
    {
        try {
            // Token validation
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
            $vendor = Vendor::find($userId);

            if (!$vendor) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found'
                ], 404);
            }

            // Get user subscriptions
            $subscriptions = PlanSubscription::byUser($userId)
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($subscription) {
                    return [
                        'id' => $subscription->id,
                        'plan_name' => $subscription->plan_name,
                        'price' => $subscription->price,
                        'duration_type' => $subscription->duration_type,
                        'duration_text' => $subscription->duration_text,
                        'subscribed_at' => $subscription->created_at,
                        'updated_at' => $subscription->updated_at
                    ];
                });

            // Get latest subscription
            $latestSubscription = $subscriptions->first();

            // Get subscription stats
            $totalSpent = $subscriptions->sum('price');
            $subscriptionCounts = $subscriptions->groupBy('duration_type')->map(function ($group) {
                return $group->count();
            });

            return response()->json([
                'success' => true,
                'message' => 'User subscriptions retrieved successfully',
                'data' => [
                    'latest_subscription' => $latestSubscription,
                    'subscription_history' => $subscriptions,
                    'subscription_stats' => [
                        'total_subscriptions' => $subscriptions->count(),
                        'total_amount_spent' => $totalSpent,
                        'subscription_counts' => $subscriptionCounts
                    ],
                    'user_info' => [
                        'id' => $vendor->id,
                        'name' => $vendor->name,
                        'contact_number' => $vendor->contact_number
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Get user subscriptions error', [
                'user_id' => $userId ?? null,
                'error' => $e->getMessage(),
                'line' => $e->getLine()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve subscriptions: ' . $e->getMessage()
            ], 500);
        }
    }

    // ✅ GET SUBSCRIPTION BY ID
    public function getSubscriptionById(Request $request, $id): JsonResponse
    {
        try {
            // Token validation
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

            // Find subscription for this user
            $subscription = PlanSubscription::where('id', $id)
                ->where('user_id', $userId)
                ->first();

            if (!$subscription) {
                return response()->json([
                    'success' => false,
                    'message' => 'Subscription not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Subscription retrieved successfully',
                'data' => [
                    'subscription' => [
                        'id' => $subscription->id,
                        'user_id' => $subscription->user_id,
                        'plan_name' => $subscription->plan_name,
                        'price' => $subscription->price,
                        'duration_type' => $subscription->duration_type,
                        'duration_text' => $subscription->duration_text,
                        'subscribed_at' => $subscription->created_at,
                        'updated_at' => $subscription->updated_at
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Get subscription by ID error', [
                'subscription_id' => $id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve subscription: ' . $e->getMessage()
            ], 500);
        }
    }

    // ✅ DELETE SUBSCRIPTION
    public function deleteSubscription(Request $request, $id): JsonResponse
    {
        try {
            // Token validation
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

            // Find subscription for this user
            $subscription = PlanSubscription::where('id', $id)
                ->where('user_id', $userId)
                ->first();

            if (!$subscription) {
                return response()->json([
                    'success' => false,
                    'message' => 'Subscription not found'
                ], 404);
            }

            // Store subscription data for response
            $subscriptionData = [
                'id' => $subscription->id,
                'plan_name' => $subscription->plan_name,
                'price' => $subscription->price,
                'duration_type' => $subscription->duration_type
            ];

            // Delete subscription
            $subscription->delete();

            Log::info('Plan subscription deleted', [
                'user_id' => $userId,
                'subscription_id' => $id,
                'plan_name' => $subscriptionData['plan_name']
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Subscription deleted successfully',
                'data' => [
                    'deleted_subscription' => $subscriptionData
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Delete subscription error', [
                'subscription_id' => $id,
                'user_id' => $userId ?? null,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete subscription: ' . $e->getMessage()
            ], 500);
        }
    }

    // ✅ GET SUBSCRIPTION STATISTICS
    public function getSubscriptionStats(): JsonResponse
    {
        try {
            $totalSubscriptions = PlanSubscription::count();
            $totalRevenue = PlanSubscription::sum('price');

            $planStats = PlanSubscription::selectRaw('plan_name, COUNT(*) as count, SUM(price) as revenue')
                ->groupBy('plan_name')
                ->get();

            $durationStats = PlanSubscription::selectRaw('duration_type, COUNT(*) as count, SUM(price) as revenue')
                ->groupBy('duration_type')
                ->get();

            $recentSubscriptions = PlanSubscription::with('vendor')
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get()
                ->map(function ($subscription) {
                    return [
                        'id' => $subscription->id,
                        'plan_name' => $subscription->plan_name,
                        'price' => $subscription->price,
                        'duration_type' => $subscription->duration_type,
                        'user_name' => $subscription->vendor->name ?? 'Unknown',
                        'subscribed_at' => $subscription->created_at
                    ];
                });

            return response()->json([
                'success' => true,
                'message' => 'Subscription statistics retrieved successfully',
                'data' => [
                    'overview' => [
                        'total_subscriptions' => $totalSubscriptions,
                        'total_revenue' => $totalRevenue,
                        'average_subscription_value' => $totalSubscriptions > 0 ? round($totalRevenue / $totalSubscriptions, 2) : 0
                    ],
                    'plan_statistics' => $planStats,
                    'duration_statistics' => $durationStats,
                    'recent_subscriptions' => $recentSubscriptions
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Get subscription stats error', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve statistics: ' . $e->getMessage()
            ], 500);
        }
    }
}
