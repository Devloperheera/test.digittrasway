<?php

namespace App\Http\Controllers\Website;

use App\Models\BookingRequest;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class BookingRequestController extends Controller
{
    /**
     * Display all booking requests with search and filters
     */
    public function index(Request $request)
    {
        $query = BookingRequest::with(['booking', 'vendor']);

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('booking_id', 'LIKE', "%{$search}%")
                  ->orWhere('id', 'LIKE', "%{$search}%")
                  ->orWhere('sequence_number', 'LIKE', "%{$search}%")
                  ->orWhereHas('vendor', function($vendorQuery) use ($search) {
                      $vendorQuery->where('name', 'LIKE', "%{$search}%")
                                  ->orWhere('email', 'LIKE', "%{$search}%")
                                  ->orWhere('contact_number', 'LIKE', "%{$search}%");
                  });
            });
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by vendor
        if ($request->filled('vendor_id')) {
            $query->where('vendor_id', $request->vendor_id);
        }

        // Filter by booking
        if ($request->filled('booking_id')) {
            $query->where('booking_id', $request->booking_id);
        }

        // Date range filter
        if ($request->filled('start_date')) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        // Order by latest
        $bookingRequests = $query->latest()->paginate(25)->withQueryString();

        return view('Website.BookingRequest.index', compact('bookingRequests'));
    }

    /**
     * Show single booking request details
     */
    public function show($id)
    {
        $bookingRequest = BookingRequest::with(['booking', 'vendor'])->findOrFail($id);

        return view('Website.BookingRequest.show', compact('bookingRequest'));
    }

    /**
     * Get all booking requests (API/AJAX)
     */
    public function getAllRequests()
    {
        $bookingRequests = BookingRequest::with(['booking', 'vendor'])
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'data' => $bookingRequests
        ]);
    }

    /**
     * Get booking requests by booking ID
     */
    public function getByBooking($bookingId)
    {
        $bookingRequests = BookingRequest::with(['vendor'])
            ->where('booking_id', $bookingId)
            ->orderBy('sequence_number', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $bookingRequests
        ]);
    }

    /**
     * Get booking requests by vendor ID
     */
    public function getByVendor($vendorId)
    {
        $bookingRequests = BookingRequest::with(['booking'])
            ->where('vendor_id', $vendorId)
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'data' => $bookingRequests
        ]);
    }

    /**
     * Get pending requests
     */
    public function getPendingRequests()
    {
        $bookingRequests = BookingRequest::with(['booking', 'vendor'])
            ->pending()
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'data' => $bookingRequests
        ]);
    }
}
