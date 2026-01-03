<?php

namespace App\Http\Controllers\Website;

use App\Models\Plan;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class PlanController extends Controller
{
    /**
     * Display a listing of plans
     */
    public function index()
    {
        $plans = Plan::orderBy('sort_order', 'asc')->get();

        return view('Website.Plan.index', compact('plans'));
    }

    /**
     * Show the form for creating a new plan
     */
    public function create()
    {
        return view('Website.Plan.create');
    }

    /**
     * Store a newly created plan
     */
    public function store(Request $request)
    {
        // ✅ CORRECTED VALIDATION RULES WITH ALL 6 DURATION TYPES
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'price' => 'required|numeric|min:0',
            'duration_type' => 'required|in:daily,weekly,monthly,quarterly,half_yearly,yearly', // ✅ FIXED
            'duration_days' => 'required|integer|min:1', // ✅ ADDED
            'features' => 'nullable|array',
            'features.*' => 'nullable|string|max:500', // ✅ Changed to nullable
            'is_popular' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
            'button_text' => 'required|string|max:100',
            'button_color' => 'required|string|max:20',
            'contact_info' => 'nullable|email|max:255',
            'sort_order' => 'required|integer|min:1'
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            $data = $request->except('_token');

            // ✅ FILTER EMPTY FEATURES
            if ($request->has('features')) {
                $data['features'] = array_values(array_filter($request->features, function($feature) {
                    return !empty(trim($feature));
                }));
            } else {
                $data['features'] = [];
            }

            // Set boolean values
            $data['is_popular'] = $request->has('is_popular') ? 1 : 0;
            $data['is_active'] = $request->has('is_active') ? 1 : 0;

            Plan::create($data);

            return redirect()->route('plans.index')
                ->with('success', 'Plan created successfully!');

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to create Plan: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Show the form for editing the specified plan
     */
    public function edit($id)
    {
        $plan = Plan::findOrFail($id);

        return view('Website.Plan.edit', compact('plan'));
    }

    /**
     * Update the specified plan
     */
    public function update(Request $request, $id)
    {
        $plan = Plan::findOrFail($id);

        // ✅ CORRECTED VALIDATION RULES WITH ALL 6 DURATION TYPES
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'price' => 'required|numeric|min:0',
            'duration_type' => 'required|in:daily,weekly,monthly,quarterly,half_yearly,yearly', // ✅ FIXED
            'duration_days' => 'required|integer|min:1', // ✅ ADDED
            'features' => 'nullable|array',
            'features.*' => 'nullable|string|max:500', // ✅ Changed to nullable
            'is_popular' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
            'button_text' => 'required|string|max:100',
            'button_color' => 'required|string|max:20',
            'contact_info' => 'nullable|email|max:255',
            'sort_order' => 'required|integer|min:1'
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            $data = $request->except('_token', '_method');

            // ✅ FILTER EMPTY FEATURES
            if ($request->has('features')) {
                $data['features'] = array_values(array_filter($request->features, function($feature) {
                    return !empty(trim($feature));
                }));
            } else {
                $data['features'] = [];
            }

            // Set boolean values
            $data['is_popular'] = $request->has('is_popular') ? 1 : 0;
            $data['is_active'] = $request->has('is_active') ? 1 : 0;

            $plan->update($data);

            return redirect()->route('plans.index')
                ->with('success', 'Plan updated successfully!');

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to update Plan: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Remove the specified plan
     */
    public function destroy($id)
    {
        try {
            $plan = Plan::findOrFail($id);
            
            // ✅ CHECK IF PLAN HAS ACTIVE SUBSCRIPTIONS
            if ($plan->activeSubscriptions()->exists()) {
                return redirect()->back()
                    ->with('error', 'Cannot delete plan with active subscriptions!');
            }

            $plan->delete();

            return redirect()->route('plans.index')
                ->with('success', 'Plan deleted successfully!');

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to delete Plan: ' . $e->getMessage());
        }
    }

    /**
     * Toggle active status
     */
    public function toggleStatus($id)
    {
        try {
            $plan = Plan::findOrFail($id);
            $plan->is_active = !$plan->is_active;
            $plan->save();

            return response()->json([
                'success' => true,
                'message' => 'Status updated successfully',
                'is_active' => $plan->is_active
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update status'
            ], 500);
        }
    }

    /**
     * Toggle popular status
     */
    public function togglePopular($id)
    {
        try {
            $plan = Plan::findOrFail($id);
            $plan->is_popular = !$plan->is_popular;
            $plan->save();

            return response()->json([
                'success' => true,
                'message' => 'Popular status updated successfully',
                'is_popular' => $plan->is_popular
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update popular status'
            ], 500);
        }
    }
}
