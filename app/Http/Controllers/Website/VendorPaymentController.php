<?php

namespace App\Http\Controllers\Website;

use App\Models\VendorPayment;
use App\Models\Vendor;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class VendorPaymentController extends Controller
{
    /**
     * Display all vendor payments
     */
    public function index(Request $request)
    {
        $query = VendorPayment::with(['vendor', 'vendorPlan']);

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('razorpay_order_id', 'LIKE', "%{$search}%")
                  ->orWhere('razorpay_payment_id', 'LIKE', "%{$search}%")
                  ->orWhere('receipt_number', 'LIKE', "%{$search}%")
                  ->orWhere('email', 'LIKE', "%{$search}%")
                  ->orWhere('contact', 'LIKE', "%{$search}%")
                  ->orWhereHas('vendor', function($vendorQuery) use ($search) {
                      $vendorQuery->where('name', 'LIKE', "%{$search}%")
                                  ->orWhere('contact_number', 'LIKE', "%{$search}%");
                  });
            });
        }

        // Filter by payment status
        if ($request->filled('payment_status')) {
            $query->where('payment_status', $request->payment_status);
        }

        // Filter by payment method
        if ($request->filled('payment_method')) {
            $query->where('payment_method', $request->payment_method);
        }

        // Filter by vendor
        if ($request->filled('vendor_id')) {
            $query->where('vendor_id', $request->vendor_id);
        }

        // Date range filter
        if ($request->filled('start_date')) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        $payments = $query->latest()->paginate(25)->withQueryString();

        // Get vendors for filter
        $vendors = Vendor::where('is_verified', true)
                        ->orderBy('name', 'asc')
                        ->get();

        return view('Website.VendorPayment.index', compact('payments', 'vendors'));
    }

    /**
     * Show payment details
     */
    public function show($id)
    {
        $payment = VendorPayment::with(['vendor', 'vendorPlan', 'subscription'])->findOrFail($id);

        return view('Website.VendorPayment.show', compact('payment'));
    }

    /**
     * Export payments
     */
    public function export(Request $request)
    {
        // Implementation for export functionality
        return response()->json(['message' => 'Export functionality coming soon']);
    }
}
