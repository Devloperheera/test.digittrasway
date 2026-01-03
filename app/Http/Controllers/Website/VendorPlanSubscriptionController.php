<?php

namespace App\Http\Controllers\Website;

use App\Models\VendorPlanSubscription;
use App\Models\Vendor;
use App\Models\VendorPlan;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class VendorPlanSubscriptionController extends Controller
{
    /**
     * Display all subscriptions
     */
    public function index(Request $request)
    {
        $query = VendorPlanSubscription::with(['vendor', 'vendorPlan', 'payment']);

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('plan_name', 'LIKE', "%{$search}%")
                  ->orWhere('id', 'LIKE', "%{$search}%")
                  ->orWhereHas('vendor', function($vendorQuery) use ($search) {
                      $vendorQuery->where('name', 'LIKE', "%{$search}%")
                                  ->orWhere('contact_number', 'LIKE', "%{$search}%");
                  });
            });
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by vendor
        if ($request->filled('vendor_id')) {
            $query->where('vendor_id', $request->vendor_id);
        }

        // Filter by duration type
        if ($request->filled('duration_type')) {
            $query->where('duration_type', $request->duration_type);
        }

        // Date range filter
        if ($request->filled('start_date')) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        $subscriptions = $query->latest()->paginate(25)->withQueryString();

        // Get vendors and plans for filters
        $vendors = Vendor::where('is_verified', true)->orderBy('name', 'asc')->get();
        $plans = VendorPlan::where('is_active', true)->orderBy('name', 'asc')->get();

        return view('Website.VendorPlanSubscription.index', compact('subscriptions', 'vendors', 'plans'));
    }

    /**
     * Show subscription details
     */
    public function show($id)
    {
        $subscription = VendorPlanSubscription::with(['vendor', 'vendorPlan', 'payment'])->findOrFail($id);

        return view('Website.VendorPlanSubscription.show', compact('subscription'));
    }

    /**
     * Cancel subscription
     */
    public function cancel($id)
    {
        try {
            $subscription = VendorPlanSubscription::findOrFail($id);

            if ($subscription->cancel()) {
                return redirect()->back()
                    ->with('success', 'Subscription cancelled successfully!');
            }

            return redirect()->back()
                ->with('error', 'Failed to cancel subscription');

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Error: ' . $e->getMessage());
        }
    }

    /**
     * Renew subscription
     */
    public function renew(Request $request, $id)
    {
        $request->validate([
            'days' => 'required|integer|min:1'
        ]);

        try {
            $subscription = VendorPlanSubscription::findOrFail($id);

            if ($subscription->renew($request->days)) {
                return redirect()->back()
                    ->with('success', 'Subscription renewed successfully!');
            }

            return redirect()->back()
                ->with('error', 'Failed to renew subscription');

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Error: ' . $e->getMessage());
        }
    }
}
