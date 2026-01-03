<?php

namespace App\Http\Controllers\Website;

use App\Models\TruckType;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class TruckTypeController extends Controller
{
    /**
     * Display a listing of truck types
     */
    public function index()
    {
        $truckTypes = TruckType::orderBy('sort_order', 'asc')->get();

        return view('Website.TruckType.index', compact('truckTypes'));
    }

    /**
     * Show the form for creating a new truck type
     */
    public function create()
    {
        return view('Website.TruckType.create');
    }

    /**
     * Store a newly created truck type
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
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

            TruckType::create($data);

            return redirect()->route('truck-types.index')
                ->with('success', 'Truck Type created successfully!');

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to create Truck Type: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Show the form for editing the specified truck type
     */
    public function edit($id)
    {
        $truckType = TruckType::findOrFail($id);

        return view('Website.TruckType.edit', compact('truckType'));
    }

    /**
     * Update the specified truck type
     */
    public function update(Request $request, $id)
    {
        $truckType = TruckType::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
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

            $truckType->update($data);

            return redirect()->route('truck-types.index')
                ->with('success', 'Truck Type updated successfully!');

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to update Truck Type: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Remove the specified truck type
     */
    public function destroy($id)
    {
        try {
            $truckType = TruckType::findOrFail($id);
            $truckType->delete();

            return redirect()->route('truck-types.index')
                ->with('success', 'Truck Type deleted successfully!');

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to delete Truck Type: ' . $e->getMessage());
        }
    }

    /**
     * Toggle active status
     */
    public function toggleStatus($id)
    {
        try {
            $truckType = TruckType::findOrFail($id);
            $truckType->is_active = !$truckType->is_active;
            $truckType->save();

            return response()->json([
                'success' => true,
                'message' => 'Status updated successfully',
                'is_active' => $truckType->is_active
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update status'
            ], 500);
        }
    }
}
