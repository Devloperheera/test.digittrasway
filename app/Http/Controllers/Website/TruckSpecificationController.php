<?php

namespace App\Http\Controllers\Website;

use App\Models\TruckSpecification;
use App\Models\TruckType;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class TruckSpecificationController extends Controller
{
    /**
     * Display all truck specifications
     */
    public function index(Request $request)
    {
        $query = TruckSpecification::with('truckType');

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('tyre_count', 'LIKE', "%{$search}%")
                  ->orWhere('max_weight', 'LIKE', "%{$search}%")
                  ->orWhereHas('truckType', function($typeQuery) use ($search) {
                      $typeQuery->where('name', 'LIKE', "%{$search}%");
                  });
            });
        }

        // Filter by truck type
        if ($request->filled('truck_type_id')) {
            $query->where('truck_type_id', $request->truck_type_id);
        }

        // Filter by active status
        if ($request->filled('is_active')) {
            $query->where('is_active', $request->is_active);
        }

        $specifications = $query->latest()->paginate(25)->withQueryString();
        $truckTypes = TruckType::active()->ordered()->get();

        return view('Website.TruckSpecification.index', compact('specifications', 'truckTypes'));
    }

    /**
     * Show create form
     */
    public function create()
    {
        $truckTypes = TruckType::active()->ordered()->get();

        return view('Website.TruckSpecification.create', compact('truckTypes'));
    }

    /**
     * Store new specification
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'truck_type_id' => 'required|exists:truck_types,id',
            'length' => 'required|numeric|min:0',
            'length_unit' => 'required|in:ft,m',
            'tyre_count' => 'required|integer|min:2',
            'height' => 'required|numeric|min:0',
            'height_unit' => 'required|in:ft,m',
            'max_weight' => 'required|numeric|min:0',
            'is_active' => 'boolean'
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            $data = $request->except('_token');
            $data['is_active'] = $request->has('is_active') ? 1 : 0;

            TruckSpecification::create($data);

            return redirect()->route('truck-specifications.index')
                ->with('success', 'Truck Specification created successfully!');

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to create specification: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Show edit form
     */
    public function edit($id)
    {
        $specification = TruckSpecification::findOrFail($id);
        $truckTypes = TruckType::active()->ordered()->get();

        return view('Website.TruckSpecification.edit', compact('specification', 'truckTypes'));
    }

    /**
     * Update specification
     */
    public function update(Request $request, $id)
    {
        $specification = TruckSpecification::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'truck_type_id' => 'required|exists:truck_types,id',
            'length' => 'required|numeric|min:0',
            'length_unit' => 'required|in:ft,m',
            'tyre_count' => 'required|integer|min:2',
            'height' => 'required|numeric|min:0',
            'height_unit' => 'required|in:ft,m',
            'max_weight' => 'required|numeric|min:0',
            'is_active' => 'boolean'
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            $data = $request->except('_token', '_method');
            $data['is_active'] = $request->has('is_active') ? 1 : 0;

            $specification->update($data);

            return redirect()->route('truck-specifications.index')
                ->with('success', 'Truck Specification updated successfully!');

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to update specification: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Delete specification
     */
    public function destroy($id)
    {
        try {
            $specification = TruckSpecification::findOrFail($id);
            $specification->delete();

            return redirect()->route('truck-specifications.index')
                ->with('success', 'Truck Specification deleted successfully!');

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to delete specification: ' . $e->getMessage());
        }
    }

    /**
     * Toggle active status
     */
    public function toggleStatus($id)
    {
        try {
            $specification = TruckSpecification::findOrFail($id);
            $specification->is_active = !$specification->is_active;
            $specification->save();

            return response()->json([
                'success' => true,
                'message' => 'Status updated successfully',
                'is_active' => $specification->is_active
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update status'
            ], 500);
        }
    }
}
