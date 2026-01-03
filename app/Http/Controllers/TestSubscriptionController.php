<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Models\PlanSubscription;
use App\Models\User;
use Illuminate\Http\Request;

class TestSubscriptionController extends Controller
{
    public function showTestPage()
    {
        // Simple version - no auth
        $plans = Plan::where('is_active', 1)->get();
        $user = User::first(); // Use first user for testing
        $activeSubscription = null;

        if ($user) {
            $activeSubscription = PlanSubscription::where('user_id', $user->id)
                ->where('subscription_status', 'active')
                ->first();
        }

        return view('test-subscription', [
            'plans' => $plans,
            'activeSubscription' => $activeSubscription,
            'user' => $user
        ]);
    }
}
