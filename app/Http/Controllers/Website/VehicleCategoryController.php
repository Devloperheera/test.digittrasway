<?php

namespace App\Http\Controllers\Website;

use App\Models\VehicleCategory;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class VehicleCategoryController extends Controller
{
    /**
     * Display all vehicle categories
     */
    public function index(Request $request)
    {
        $query = VehicleCategory::query();

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('category_name', 'LIKE', "%{$search}%")
                  ->orWhere('category_key', 'LIKE', "%{$search}%")
                  ->orWhere('description', 'LIKE', "%{$search}%");
            });
        }

        // Filter by status
        if ($request->filled('is_active')) {
            $query->where('is_active', $request->is_active);
        }

        $categories = $query->orderBy('display_order', 'asc')->paginate(25)->withQueryString();

        return view('Website.VehicleCategory.index', compact('categories'));
    }

    /**
     * Show create form
     */
    public function create()
    {
        return view('Website.VehicleCategory.create');
    }

    /**
     * Store new category
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'category_key' => 'required|string|max:255|unique:vehicle_categories,category_key',
            'category_name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'icon' => 'nullable|string|max:100',
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

            VehicleCategory::create($data);

            return redirect()->route('vehicle-categories.index')
                ->with('success', 'Vehicle Category created successfully!');

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to create category: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Show edit form
     */
    public function edit($id)
    {
        $category = VehicleCategory::findOrFail($id);

        return view('Website.VehicleCategory.edit', compact('category'));
    }

    /**
     * Update category
     */
    public function update(Request $request, $id)
    {
        $category = VehicleCategory::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'category_key' => 'required|string|max:255|unique:vehicle_categories,category_key,' . $id,
            'category_name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'icon' => 'nullable|string|max:100',
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

            $category->update($data);

            return redirect()->route('vehicle-categories.index')
                ->with('success', 'Vehicle Category updated successfully!');

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to update category: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Delete category
     */
    public function destroy($id)
    {
        try {
            $category = VehicleCategory::findOrFail($id);
            $category->delete();

            return redirect()->route('vehicle-categories.index')
                ->with('success', 'Vehicle Category deleted successfully!');

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to delete category: ' . $e->getMessage());
        }
    }

    /**
     * Toggle status
     */
    public function toggleStatus($id)
    {
        try {
            $category = VehicleCategory::findOrFail($id);
            $category->is_active = !$category->is_active;
            $category->save();

            return response()->json([
                'success' => true,
                'message' => 'Status updated successfully',
                'is_active' => $category->is_active
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update status'
            ], 500);
        }
    }
}
