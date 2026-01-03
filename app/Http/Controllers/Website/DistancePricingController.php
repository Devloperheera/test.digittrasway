<?php

namespace App\Http\Controllers\Website;

use App\Models\DistancePricing;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class DistancePricingController extends Controller
{
    /**
     * Display a listing of distance pricings
     */
    public function index()
    {
        $pricings = DistancePricing::orderBy('sort_order', 'asc')->get();

        return view('Website.DistancePricing.index', compact('pricings'));
    }

    /**
     * Show the form for creating a new distance pricing
     */
    public function create()
    {
        return view('Website.DistancePricing.create');
    }

    /**
     * Store a newly created distance pricing
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'price_per_km' => 'required|numeric|min:0',
            'distance_from' => 'required|integer|min:0',
            'distance_to' => 'nullable|integer|min:0|gt:distance_from',
            'minimum_charge' => 'required|numeric|min:0',
            'description' => 'nullable|string|max:500',
            'is_active' => 'boolean',
            'sort_order' => 'required|integer|min:1'
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            $data = $request->except('_token');
            $data['is_active'] = $request->has('is_active') ? 1 : 0;

            DistancePricing::create($data);

            return redirect()->route('distance-pricings.index')
                ->with('success', 'Distance Pricing created successfully!');

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to create Distance Pricing: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Show the form for editing the specified distance pricing
     */
    public function edit($id)
    {
        $pricing = DistancePricing::findOrFail($id);

        return view('Website.DistancePricing.edit', compact('pricing'));
    }

    /**
     * Update the specified distance pricing
     */
    public function update(Request $request, $id)
    {
        $pricing = DistancePricing::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'price_per_km' => 'required|numeric|min:0',
            'distance_from' => 'required|integer|min:0',
            'distance_to' => 'nullable|integer|min:0|gt:distance_from',
            'minimum_charge' => 'required|numeric|min:0',
            'description' => 'nullable|string|max:500',
            'is_active' => 'boolean',
            'sort_order' => 'required|integer|min:1'
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            $data = $request->except('_token', '_method');
            $data['is_active'] = $request->has('is_active') ? 1 : 0;

            $pricing->update($data);

            return redirect()->route('distance-pricings.index')
                ->with('success', 'Distance Pricing updated successfully!');

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to update Distance Pricing: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Remove the specified distance pricing
     */
    public function destroy($id)
    {
        try {
            $pricing = DistancePricing::findOrFail($id);
            $pricing->delete();

            return redirect()->route('distance-pricings.index')
                ->with('success', 'Distance Pricing deleted successfully!');

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to delete Distance Pricing: ' . $e->getMessage());
        }
    }

    /**
     * Toggle active status
     */
    public function toggleStatus($id)
    {
        try {
            $pricing = DistancePricing::findOrFail($id);
            $pricing->is_active = !$pricing->is_active;
            $pricing->save();

            return response()->json([
                'success' => true,
                'message' => 'Status updated successfully',
                'is_active' => $pricing->is_active
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update status'
            ], 500);
        }
    }

    /**
     * Calculate price for a distance
     */
    public function calculatePrice(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'distance' => 'required|numeric|min:0'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid distance'
            ], 400);
        }

        $distance = $request->distance;

        $pricing = DistancePricing::active()
            ->where('distance_from', '<=', $distance)
            ->where(function($query) use ($distance) {
                $query->whereNull('distance_to')
                      ->orWhere('distance_to', '>=', $distance);
            })
            ->orderBy('sort_order')
            ->first();

        if (!$pricing) {
            return response()->json([
                'success' => false,
                'message' => 'No pricing found for this distance'
            ], 404);
        }

        $calculatedPrice = $pricing->calculatePrice($distance);

        return response()->json([
            'success' => true,
            'pricing_name' => $pricing->name,
            'distance' => $distance,
            'price_per_km' => $pricing->price_per_km,
            'minimum_charge' => $pricing->minimum_charge,
            'calculated_price' => $calculatedPrice
        ]);
    }
}
