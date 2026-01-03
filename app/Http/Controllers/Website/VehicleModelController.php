<?php

namespace App\Http\Controllers\Website;

use App\Models\VehicleModel;
use App\Models\VehicleCategory;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class VehicleModelController extends Controller
{
    /**
     * Display all vehicle models
     */
    public function index(Request $request)
    {
        $query = VehicleModel::with('category');

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('model_name', 'LIKE', "%{$search}%")
                  ->orWhere('vehicle_type_desc', 'LIKE', "%{$search}%")
                  ->orWhere('carry_capacity_tons', 'LIKE', "%{$search}%")
                  ->orWhereHas('category', function($catQuery) use ($search) {
                      $catQuery->where('category_name', 'LIKE', "%{$search}%");
                  });
            });
        }

        // Filter by category
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        // Filter by status
        if ($request->filled('is_active')) {
            $query->where('is_active', $request->is_active);
        }

        $models = $query->orderBy('display_order', 'asc')->paginate(25)->withQueryString();
        $categories = VehicleCategory::where('is_active', true)
                                    ->orderBy('display_order', 'asc')
                                    ->get();

        return view('Website.VehicleModel.index', compact('models', 'categories'));
    }

    /**
     * Show create form
     */
    public function create()
    {
        $categories = VehicleCategory::where('is_active', true)
                                    ->orderBy('display_order', 'asc')
                                    ->get();
        
        return view('Website.VehicleModel.create', compact('categories'));
    }

    /**
     * Store new model
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'category_id' => 'required|exists:vehicle_categories,id',
            'model_name' => 'required|string|max:255',
            'vehicle_type_desc' => 'nullable|string|max:500',
            'carry_capacity_tons' => 'required|numeric|min:0',
            'is_active' => 'boolean',
            'display_order' => 'required|integer|min:1'
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            $data = $request->except('_token');
            $data['is_active'] = $request->has('is_active') ? 1 : 0;

            VehicleModel::create($data);

            return redirect()->route('vehicle-models.index')
                ->with('success', 'Vehicle Model created successfully!');

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to create model: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Show edit form
     */
    public function edit($id)
    {
        $model = VehicleModel::findOrFail($id);
        $categories = VehicleCategory::where('is_active', true)
                                    ->orderBy('display_order', 'asc')
                                    ->get();
        
        return view('Website.VehicleModel.edit', compact('model', 'categories'));
    }

    /**
     * Update model
     */
    public function update(Request $request, $id)
    {
        $model = VehicleModel::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'category_id' => 'required|exists:vehicle_categories,id',
            'model_name' => 'required|string|max:255',
            'vehicle_type_desc' => 'nullable|string|max:500',
            'carry_capacity_tons' => 'required|numeric|min:0',
            'is_active' => 'boolean',
            'display_order' => 'required|integer|min:1'
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            $data = $request->except('_token', '_method');
            $data['is_active'] = $request->has('is_active') ? 1 : 0;

            $model->update($data);

            return redirect()->route('vehicle-models.index')
                ->with('success', 'Vehicle Model updated successfully!');

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to update model: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Delete model
     */
    public function destroy($id)
    {
        try {
            $model = VehicleModel::findOrFail($id);
            $model->delete();

            return redirect()->route('vehicle-models.index')
                ->with('success', 'Vehicle Model deleted successfully!');

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to delete model: ' . $e->getMessage());
        }
    }

    /**
     * Toggle status
     */
    public function toggleStatus($id)
    {
        try {
            $model = VehicleModel::findOrFail($id);
            $model->is_active = !$model->is_active;
            $model->save();

            return response()->json([
                'success' => true,
                'message' => 'Status updated successfully',
                'is_active' => $model->is_active
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update status'
            ], 500);
        }
    }
}
