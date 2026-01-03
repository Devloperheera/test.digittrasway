<?php

namespace App\Http\Controllers\Website;

use App\Models\VendorVehicle;
use App\Models\Vendor;
use App\Models\VehicleCategory;
use App\Models\VehicleModel;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class VendorVehicleController extends Controller
{
    /**
     * Display all vehicles
     */
    public function index(Request $request)
    {
        $query = VendorVehicle::with(['vendor', 'category', 'model']);

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('vehicle_registration_number', 'LIKE', "%{$search}%")
                  ->orWhere('vehicle_name', 'LIKE', "%{$search}%")
                  ->orWhere('rc_number', 'LIKE', "%{$search}%")
                  ->orWhereHas('vendor', function($vendorQuery) use ($search) {
                      $vendorQuery->where('name', 'LIKE', "%{$search}%");
                  });
            });
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by category
        if ($request->filled('category_id')) {
            $query->where('vehicle_category_id', $request->category_id);
        }

        // Filter by availability
        if ($request->filled('is_available')) {
            $query->where('is_available', $request->is_available);
        }

        $vehicles = $query->latest()->paginate(25)->withQueryString();

        $categories = VehicleCategory::where('is_active', true)
                                    ->orderBy('display_order', 'asc')
                                    ->get();
        $vendors = Vendor::where('is_verified', true)->orderBy('name')->get();

        return view('Website.VendorVehicle.index', compact('vehicles', 'categories', 'vendors'));
    }

    /**
     * Show vehicle details
     */
    public function show($id)
    {
        $vehicle = VendorVehicle::with(['vendor', 'category', 'model'])->findOrFail($id);

        return view('Website.VendorVehicle.show', compact('vehicle'));
    }

    /**
     * Approve vehicle
     */
    public function approve($id)
    {
        try {
            $vehicle = VendorVehicle::findOrFail($id);

            // Get admin ID from session or use null
            $adminId = session('admin_id') ?? null;

            if ($vehicle->approve($adminId)) {
                return redirect()->back()
                    ->with('success', 'Vehicle approved successfully!');
            }

            return redirect()->back()
                ->with('error', 'Failed to approve vehicle');

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Error: ' . $e->getMessage());
        }
    }

    /**
     * Reject vehicle
     */
    public function reject(Request $request, $id)
    {
        $request->validate([
            'rejection_reason' => 'required|string|max:500'
        ]);

        try {
            $vehicle = VendorVehicle::findOrFail($id);

            if ($vehicle->reject($request->rejection_reason)) {
                return redirect()->back()
                    ->with('success', 'Vehicle rejected successfully!');
            }

            return redirect()->back()
                ->with('error', 'Failed to reject vehicle');

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Error: ' . $e->getMessage());
        }
    }

    /**
     * Toggle listing status
     */
    public function toggleListing($id)
    {
        try {
            $vehicle = VendorVehicle::findOrFail($id);

            if ($vehicle->is_listed) {
                $vehicle->unlist();
                $message = 'Vehicle unlisted successfully';
            } else {
                if ($vehicle->list()) {
                    $message = 'Vehicle listed successfully';
                } else {
                    return response()->json([
                        'success' => false,
                        'message' => 'Vehicle must be approved before listing'
                    ], 400);
                }
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'is_listed' => $vehicle->is_listed
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update listing status'
            ], 500);
        }
    }

    /**
     * Delete vehicle
     */
    public function destroy($id)
    {
        try {
            $vehicle = VendorVehicle::findOrFail($id);
            $vehicle->delete();

            return redirect()->route('vendor-vehicles.index')
                ->with('success', 'Vehicle deleted successfully!');

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to delete vehicle: ' . $e->getMessage());
        }
    }
}
