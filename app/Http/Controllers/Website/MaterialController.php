<?php

namespace App\Http\Controllers\Website;

use App\Models\Material;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class MaterialController extends Controller
{
    /**
     * Display a listing of materials
     */
    public function index(Request $request)
    {
        $query = Material::query();

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('description', 'LIKE', "%{$search}%");
            });
        }

        // Filter by status
        if ($request->filled('is_active')) {
            $query->where('is_active', $request->is_active);
        }

        $materials = $query->ordered()->paginate(25)->withQueryString();

        return view('Website.Material.index', compact('materials'));
    }

    /**
     * Show the form for creating a new material
     */
    public function create()
    {
        return view('Website.Material.create');
    }

    /**
     * Store a newly created material
     */
    public function store(Request $request)
    {
        Log::info('=== MATERIAL CREATION STARTED ===');
        Log::info('Form Data:', $request->all());

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:materials,name',
            'description' => 'nullable|string|max:1000',
            'is_active' => 'nullable|boolean',
            'sort_order' => 'required|integer|min:1'
        ]);

        if ($validator->fails()) {
            Log::error('Validation Failed:', $validator->errors()->toArray());
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            $data = [
                'name' => $request->name,
                'description' => $request->description,
                'sort_order' => $request->sort_order,
                'is_active' => $request->has('is_active') ? 1 : 0,
            ];

            Log::info('Data to be saved:', $data);

            $material = Material::create($data);

            Log::info('Material Created:', [
                'id' => $material->id,
                'name' => $material->name
            ]);

            return redirect()->route('materials.index')
                ->with('success', 'Material created successfully!');

        } catch (\Exception $e) {
            Log::error('=== ERROR CREATING MATERIAL ===');
            Log::error('Message: ' . $e->getMessage());
            Log::error('File: ' . $e->getFile());
            Log::error('Line: ' . $e->getLine());

            return redirect()->back()
                ->with('error', 'Failed to create material: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Show the form for editing the specified material
     */
    public function edit($id)
    {
        $material = Material::findOrFail($id);

        return view('Website.Material.edit', compact('material'));
    }

    /**
     * Update the specified material
     */
    public function update(Request $request, $id)
    {
        $material = Material::findOrFail($id);

        Log::info('=== MATERIAL UPDATE STARTED ===');
        Log::info('Material ID:', ['id' => $id]);
        Log::info('Form Data:', $request->all());

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:materials,name,' . $id,
            'description' => 'nullable|string|max:1000',
            'is_active' => 'nullable|boolean',
            'sort_order' => 'required|integer|min:1'
        ]);

        if ($validator->fails()) {
            Log::error('Validation Failed:', $validator->errors()->toArray());
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            $data = [
                'name' => $request->name,
                'description' => $request->description,
                'sort_order' => $request->sort_order,
                'is_active' => $request->has('is_active') ? 1 : 0,
            ];

            Log::info('Data to be updated:', $data);

            $material->update($data);

            Log::info('Material Updated Successfully');

            return redirect()->route('materials.index')
                ->with('success', 'Material updated successfully!');

        } catch (\Exception $e) {
            Log::error('=== ERROR UPDATING MATERIAL ===');
            Log::error('Message: ' . $e->getMessage());
            Log::error('File: ' . $e->getFile());
            Log::error('Line: ' . $e->getLine());

            return redirect()->back()
                ->with('error', 'Failed to update material: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Remove the specified material
     */
    public function destroy($id)
    {
        try {
            $material = Material::findOrFail($id);
            $material->delete();

            Log::info('Material Deleted:', ['id' => $id]);

            return redirect()->route('materials.index')
                ->with('success', 'Material deleted successfully!');

        } catch (\Exception $e) {
            Log::error('Error deleting material:', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);

            return redirect()->back()
                ->with('error', 'Failed to delete material: ' . $e->getMessage());
        }
    }

    /**
     * Toggle active status
     */
    public function toggleStatus($id)
    {
        try {
            $material = Material::findOrFail($id);
            $material->is_active = !$material->is_active;
            $material->save();

            return response()->json([
                'success' => true,
                'message' => 'Status updated successfully',
                'is_active' => $material->is_active
            ]);

        } catch (\Exception $e) {
            Log::error('Error toggling material status:', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update status'
            ], 500);
        }
    }
}
