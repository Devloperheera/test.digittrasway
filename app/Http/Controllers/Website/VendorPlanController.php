<?php

namespace App\Http\Controllers\Website;

use App\Models\VendorPlan;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class VendorPlanController extends Controller
{
    /**
     * Display all vendor plans
     */
    public function index(Request $request)
    {
        $query = VendorPlan::query();

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('description', 'LIKE', "%{$search}%")
                  ->orWhere('price', 'LIKE', "%{$search}%");
            });
        }

        // Filter by status
        if ($request->filled('is_active')) {
            $query->where('is_active', $request->is_active);
        }

        // Filter by popular
        if ($request->filled('is_popular')) {
            $query->where('is_popular', $request->is_popular);
        }

        // Filter by duration type
        if ($request->filled('duration_type')) {
            $query->where('duration_type', $request->duration_type);
        }

        $plans = $query->orderBy('sort_order', 'asc')->paginate(25)->withQueryString();

        return view('Website.VendorPlan.index', compact('plans'));
    }

    /**
     * Show create form
     */
    public function create()
    {
        return view('Website.VendorPlan.create');
    }

    /**
     * Store new plan
     */
    public function store(Request $request)
    {
        // ✅ DEBUG LOGGING
        Log::info('=== VENDOR PLAN CREATION STARTED ===');
        Log::info('Form Data:', $request->all());

        // ✅ UPDATED VALIDATION: All 6 duration types
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'price' => 'required|numeric|min:0',
            'duration_type' => 'required|in:daily,weekly,monthly,quarterly,half_yearly,yearly', // ✅ FIXED
            'duration_days' => 'required|integer|min:1', // ✅ REQUIRED
            'features' => 'nullable|array',
            'features.*' => 'nullable|string|max:500', // ✅ Changed to nullable
            'is_popular' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
            'button_text' => 'required|string|max:100',
            'button_color' => 'required|string|max:20', // ✅ Increased from 7
            'sort_order' => 'required|integer|min:1'
        ]);

        if ($validator->fails()) {
            Log::error('Validation Failed:', $validator->errors()->toArray());
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            // Prepare data
            $data = [
                'name' => $request->name,
                'description' => $request->description,
                'price' => $request->price,
                'duration_type' => $request->duration_type,
                'duration_days' => $request->duration_days,
                'button_text' => $request->button_text,
                'button_color' => $request->button_color,
                'sort_order' => $request->sort_order,
                'is_popular' => $request->has('is_popular') ? 1 : 0,
                'is_active' => $request->has('is_active') ? 1 : 0,
            ];

            // ✅ FILTER EMPTY FEATURES
            if ($request->has('features') && is_array($request->features)) {
                $data['features'] = array_values(array_filter($request->features, function($feature) {
                    return !empty(trim($feature));
                }));
            } else {
                $data['features'] = [];
            }

            Log::info('Data to be saved:', $data);

            $plan = VendorPlan::create($data);

            Log::info('Vendor Plan Created:', [
                'id' => $plan->id,
                'name' => $plan->name
            ]);

            return redirect()->route('vendor-plans.index')
                ->with('success', 'Vendor Plan created successfully!');

        } catch (\Exception $e) {
            Log::error('=== ERROR CREATING VENDOR PLAN ===');
            Log::error('Message: ' . $e->getMessage());
            Log::error('File: ' . $e->getFile());
            Log::error('Line: ' . $e->getLine());

            return redirect()->back()
                ->with('error', 'Failed to create plan: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Show edit form
     */
    public function edit($id)
    {
        $plan = VendorPlan::findOrFail($id);

        return view('Website.VendorPlan.edit', compact('plan'));
    }

    /**
     * Update plan
     */
    public function update(Request $request, $id)
    {
        $plan = VendorPlan::findOrFail($id);

        Log::info('=== VENDOR PLAN UPDATE STARTED ===');
        Log::info('Plan ID:', ['id' => $id]);
        Log::info('Form Data:', $request->all());

        // ✅ UPDATED VALIDATION: All 6 duration types
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'price' => 'required|numeric|min:0',
            'duration_type' => 'required|in:daily,weekly,monthly,quarterly,half_yearly,yearly', // ✅ FIXED
            'duration_days' => 'required|integer|min:1', // ✅ REQUIRED
            'features' => 'nullable|array',
            'features.*' => 'nullable|string|max:500', // ✅ Changed to nullable
            'is_popular' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
            'button_text' => 'required|string|max:100',
            'button_color' => 'required|string|max:20',
            'sort_order' => 'required|integer|min:1'
        ]);

        if ($validator->fails()) {
            Log::error('Validation Failed:', $validator->errors()->toArray());
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            // Prepare data
            $data = [
                'name' => $request->name,
                'description' => $request->description,
                'price' => $request->price,
                'duration_type' => $request->duration_type,
                'duration_days' => $request->duration_days,
                'button_text' => $request->button_text,
                'button_color' => $request->button_color,
                'sort_order' => $request->sort_order,
                'is_popular' => $request->has('is_popular') ? 1 : 0,
                'is_active' => $request->has('is_active') ? 1 : 0,
            ];

            // ✅ FILTER EMPTY FEATURES
            if ($request->has('features') && is_array($request->features)) {
                $data['features'] = array_values(array_filter($request->features, function($feature) {
                    return !empty(trim($feature));
                }));
            } else {
                $data['features'] = [];
            }

            Log::info('Data to be updated:', $data);

            $plan->update($data);

            Log::info('Vendor Plan Updated Successfully');

            return redirect()->route('vendor-plans.index')
                ->with('success', 'Vendor Plan updated successfully!');

        } catch (\Exception $e) {
            Log::error('=== ERROR UPDATING VENDOR PLAN ===');
            Log::error('Message: ' . $e->getMessage());
            Log::error('File: ' . $e->getFile());
            Log::error('Line: ' . $e->getLine());

            return redirect()->back()
                ->with('error', 'Failed to update plan: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Delete plan
     */
    public function destroy($id)
    {
        try {
            $plan = VendorPlan::findOrFail($id);

            // ✅ CHECK IF PLAN HAS ACTIVE SUBSCRIPTIONS
            if ($plan->activeSubscriptions()->exists()) {
                return redirect()->back()
                    ->with('error', 'Cannot delete plan with active subscriptions!');
            }

            $plan->delete();

            Log::info('Vendor Plan Deleted:', ['id' => $id]);

            return redirect()->route('vendor-plans.index')
                ->with('success', 'Vendor Plan deleted successfully!');

        } catch (\Exception $e) {
            Log::error('Error deleting vendor plan:', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);

            return redirect()->back()
                ->with('error', 'Failed to delete plan: ' . $e->getMessage());
        }
    }

    /**
     * Toggle active status
     */
    public function toggleStatus($id)
    {
        try {
            $plan = VendorPlan::findOrFail($id);
            $plan->is_active = !$plan->is_active;
            $plan->save();

            return response()->json([
                'success' => true,
                'message' => 'Status updated successfully',
                'is_active' => $plan->is_active
            ]);

        } catch (\Exception $e) {
            Log::error('Error toggling vendor plan status:', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);

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
            $plan = VendorPlan::findOrFail($id);
            $plan->is_popular = !$plan->is_popular;
            $plan->save();

            return response()->json([
                'success' => true,
                'message' => 'Popular status updated successfully',
                'is_popular' => $plan->is_popular
            ]);

        } catch (\Exception $e) {
            Log::error('Error toggling vendor plan popular status:', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update popular status'
            ], 500);
        }
    }

    /**
     * Get plan details (AJAX)
     */
    public function show($id)
    {
        try {
            $plan = VendorPlan::findOrFail($id);

            return response()->json([
                'success' => true,
                'plan' => $plan->summary
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Plan not found'
            ], 404);
        }
    }

    /**
     * Get active plans for API/AJAX
     */
    public function getActivePlans()
    {
        try {
            $plans = VendorPlan::active()
                ->ordered()
                ->get()
                ->map(function($plan) {
                    return $plan->summary;
                });

            return response()->json([
                'success' => true,
                'plans' => $plans
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch plans'
            ], 500);
        }
    }
}
