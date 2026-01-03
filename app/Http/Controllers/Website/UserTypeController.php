<?php

namespace App\Http\Controllers\Website;

use App\Models\UserType;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class UserTypeController extends Controller
{
    /**
     * Display a listing of user types
     */
    public function index()
    {
        $userTypes = UserType::orderBy('display_order', 'asc')->get();

        return view('Website.UserType.index', compact('userTypes'));
    }

    /**
     * Show the form for creating a new user type
     */
    public function create()
    {
        return view('Website.UserType.create');
    }

    /**
     * Store a newly created user type
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'type_key' => 'required|string|max:255|unique:user_types,type_key',
            'title' => 'required|string|max:255',
            'subtitle' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'icon' => 'nullable|string|max:50',
            'features' => 'nullable|array',
            'features.*' => 'string',
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

            // ✅ Filter empty features and let Model cast handle JSON conversion
            if ($request->has('features')) {
                $data['features'] = array_filter($request->features);
            } else {
                $data['features'] = [];
            }

            // Set default values
            $data['is_active'] = $request->has('is_active') ? 1 : 0;

            UserType::create($data);

            return redirect()->route('user-types.index')
                ->with('success', 'User Type created successfully!');

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to create User Type: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Show the form for editing the specified user type
     */
    public function edit($id)
    {
        $userType = UserType::findOrFail($id);

        // ✅ No need to decode - Model cast automatically converts JSON to array
        // Features are already an array due to 'features' => 'array' cast in Model

        return view('Website.UserType.edit', compact('userType'));
    }

    /**
     * Update the specified user type
     */
    public function update(Request $request, $id)
    {
        $userType = UserType::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'type_key' => 'required|string|max:255|unique:user_types,type_key,' . $id,
            'title' => 'required|string|max:255',
            'subtitle' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'icon' => 'nullable|string|max:50',
            'features' => 'nullable|array',
            'features.*' => 'string',
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

            // ✅ Filter empty features and let Model cast handle JSON conversion
            if ($request->has('features')) {
                $data['features'] = array_filter($request->features);
            } else {
                $data['features'] = [];
            }

            // Set active status
            $data['is_active'] = $request->has('is_active') ? 1 : 0;

            $userType->update($data);

            return redirect()->route('user-types.index')
                ->with('success', 'User Type updated successfully!');

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to update User Type: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Remove the specified user type
     */
    public function destroy($id)
    {
        try {
            $userType = UserType::findOrFail($id);
            $userType->delete();

            return redirect()->route('user-types.index')
                ->with('success', 'User Type deleted successfully!');

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to delete User Type: ' . $e->getMessage());
        }
    }

    /**
     * Toggle active status
     */
    public function toggleStatus($id)
    {
        try {
            $userType = UserType::findOrFail($id);
            $userType->is_active = !$userType->is_active;
            $userType->save();

            return response()->json([
                'success' => true,
                'message' => 'Status updated successfully',
                'is_active' => $userType->is_active
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update status'
            ], 500);
        }
    }
}
