<?php

namespace App\Http\Controllers\Website;

use App\Models\User;
use App\Models\Plan;
use App\Models\PlanSubscription;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class SubscriptionController extends Controller
{
    /**
     * Display all subscriptions
     */
    public function index()
    {
        $subscriptions = PlanSubscription::with(['user', 'plan'])
                            ->latest()
                            ->paginate(25);

        return view('Website.Subscription.index', compact('subscriptions'));
    }

    /**
     * Show subscription details
     */
    public function show($id)
    {
        $subscription = PlanSubscription::with(['user', 'plan'])->findOrFail($id);

        return view('Website.Subscription.show', compact('subscription'));
    }

    /**
     * Show form to create new subscription for user
     */
    public function create($userId)
    {
        $user = User::findOrFail($userId);
        $plans = Plan::active()->ordered()->get();

        return view('Website.Subscription.create', compact('user', 'plans'));
    }

    /**
     * Store new subscription
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'plan_id' => 'required|exists:plans,id',
            'price_paid' => 'required|numeric|min:0',
            'duration_type' => 'required|in:monthly,yearly,lifetime',
            'starts_at' => 'required|date'
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            $plan = Plan::findOrFail($request->plan_id);

            // Calculate expiry date
            $startsAt = Carbon::parse($request->starts_at);
            $expiresAt = match($request->duration_type) {
                'monthly' => $startsAt->copy()->addMonth(),
                'yearly' => $startsAt->copy()->addYear(),
                'lifetime' => $startsAt->copy()->addYears(100),
                default => $startsAt->copy()->addMonth()
            };

            PlanSubscription::create([
                'user_id' => $request->user_id,
                'plan_id' => $request->plan_id,
                'plan_name' => $plan->name,
                'price_paid' => $request->price_paid,
                'duration_type' => $request->duration_type,
                'selected_features' => $plan->features ?? [],
                'starts_at' => $startsAt,
                'expires_at' => $expiresAt,
                'status' => 'active'
            ]);

            return redirect()->route('users.show', $request->user_id)
                ->with('success', 'Subscription created successfully!');

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to create subscription: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Cancel subscription
     */
    public function cancel($id)
    {
        try {
            $subscription = PlanSubscription::findOrFail($id);
            $subscription->update(['status' => 'cancelled']);

            return redirect()->back()
                ->with('success', 'Subscription cancelled successfully!');

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to cancel subscription: ' . $e->getMessage());
        }
    }
}
