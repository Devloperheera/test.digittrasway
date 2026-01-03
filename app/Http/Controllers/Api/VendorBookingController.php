<?php

namespace App\Http\Controllers\Api;

use App\Models\Vendor;
use App\Models\Review;
use App\Models\TruckBooking;
use Illuminate\Http\Request;
use App\Models\BookingRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Services\VendorBookingService;

class VendorBookingController extends Controller
{
    private $vendorBookingService;

    public function __construct()
    {
        $this->vendorBookingService = new VendorBookingService();
    }

    /**
     * Get vendor ID from token
     */
    private function getVendorIdFromToken($request)
    {
        try {
            $authHeader = $request->header('Authorization');
            if (!$authHeader) {
                Log::error('No Authorization header found');
                return null;
            }

            $token = str_replace('Bearer ', '', $authHeader);
            $decodedToken = base64_decode($token);
            $tokenParts = explode(':', $decodedToken);

            $vendorId = $tokenParts[0] ?? null;

            Log::info('Token decoded', [
                'vendor_id' => $vendorId,
                'token_parts_count' => count($tokenParts)
            ]);

            return $vendorId;
        } catch (\Exception $e) {
            Log::error('Token decode error', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * ✅ GET PENDING BOOKING REQUESTS (With Full Details)
     */
    public function getPendingRequests(Request $request): JsonResponse
    {
        try {
            $vendorId = $this->getVendorIdFromToken($request);

            if (!$vendorId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid or missing authorization token'
                ], 401);
            }

            $vendor = Vendor::find($vendorId);
            if (!$vendor) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vendor not found'
                ], 404);
            }

            // Get pending requests with relationships
            $requests = BookingRequest::where('vendor_id', $vendorId)
                ->where('status', 'pending')
                ->where('expires_at', '>', now())
                ->with(['booking.user', 'booking.material', 'booking.vehicleModel'])
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($req) {
                    $booking = $req->booking;
                    $user = $booking->user;
                    $secondsLeft = max(0, now()->diffInSeconds($req->expires_at, false));

                    // ✅ GET COORDINATES
                    $pickupLat = floatval($booking->pickup_latitude ?? 0);
                    $pickupLng = floatval($booking->pickup_longitude ?? 0);
                    $dropLat = floatval($booking->drop_latitude ?? 0);
                    $dropLng = floatval($booking->drop_longitude ?? 0);

                    // ✅ CALCULATE DISTANCE
                    $aerialDistance = 0;
                    $truckDistance = 0;
                    if ($pickupLat && $pickupLng && $dropLat && $dropLng) {
                        $aerialDistance = $this->calculateDistance($pickupLat, $pickupLng, $dropLat, $dropLng);
                        $truckDistance = $aerialDistance * 1.18;
                    }

                    return [
                        'request_id' => $req->id,
                        'booking_id' => $booking->booking_id,
                        'status' => 'pending',
                        'status_label' => 'Awaiting Your Response',

                        // ✅ USER DETAILS (Who created the booking)
                        'user' => [
                            'id' => $user->id,
                            'name' => $user->name,
                            'contact_number' => $user->contact_number ?? 'N/A',
                            'email' => $user->email ?? 'N/A'
                        ],

                        // ✅ PICKUP LOCATION WITH COORDINATES
                        'pickup_location' => [
                            'address' => $booking->pickup_address,
                            'latitude' => $pickupLat,
                            'longitude' => $pickupLng
                        ],

                        // ✅ DROP LOCATION WITH COORDINATES
                        'drop_location' => [
                            'address' => $booking->drop_address,
                            'latitude' => $dropLat,
                            'longitude' => $dropLng
                        ],

                        // ✅ DISTANCE INFO
                        'distance' => [
                            'aerial_km' => round($aerialDistance, 2),
                            'truck_route_km' => round($truckDistance, 2),
                            'display' => round($booking->distance_km ?? $truckDistance, 2) . ' km',
                            'google_maps_url' => "https://www.google.com/maps/dir/?api=1&origin={$pickupLat},{$pickupLng}&destination={$dropLat},{$dropLng}&travelmode=driving"
                        ],

                        // ✅ MATERIAL DETAILS
                        'material' => [
                            'name' => $booking->material_name,
                            'weight' => floatval($booking->material_weight),
                            'weight_display' => $booking->material_weight . ' tons'
                        ],

                        // ✅ VEHICLE DETAILS
                        'vehicle' => [
                            'model' => $booking->vehicleModel->model_name ?? 'N/A',
                            'specifications' => [
                                'length' => $booking->truck_length . ' ft',
                                'tyre_count' => $booking->tyre_count . ' Tyre',
                                'height' => $booking->truck_height . ' ft'
                            ]
                        ],

                        // ✅ PRICING DETAILS
                        'pricing' => [
                            'estimated_price' => floatval($booking->estimated_price),
                            'adjusted_price' => $booking->adjusted_price ? floatval($booking->adjusted_price) : null,
                            'final_amount' => floatval($booking->final_amount),
                            'price_per_km' => $booking->price_per_km ? floatval($booking->price_per_km) : null,
                            'currency' => 'INR',
                            'display' => '₹' . number_format($booking->final_amount, 2)
                        ],

                        // ✅ PAYMENT INFO
                        'payment' => [
                            'method' => $booking->payment_method,
                            'status' => $booking->payment_status ?? 'pending',
                            'pay_at' => $booking->payment_method === 'pickup' ? 'Pickup Location' : 'Drop Location'
                        ],

                        // ✅ SCHEDULE
                        'schedule' => [
                            'pickup_datetime' => $booking->pickup_datetime ? $booking->pickup_datetime->format('Y-m-d H:i:s') : null,
                            'pickup_formatted' => $booking->pickup_datetime ? $booking->pickup_datetime->format('d M Y, h:i A') : null,
                            'created_at' => $booking->created_at->format('Y-m-d H:i:s')
                        ],

                        // ✅ REQUEST EXPIRY
                        'expiry' => [
                            'expires_at' => $req->expires_at->format('Y-m-d H:i:s'),
                            'expires_in_seconds' => $secondsLeft,
                            'expires_in_minutes' => round($secondsLeft / 60, 1),
                            'can_accept' => $secondsLeft > 0
                        ],

                        // ✅ SPECIAL INSTRUCTIONS
                        'special_instructions' => $booking->special_instructions ?? 'No special instructions'
                    ];
                });

            return response()->json([
                'success' => true,
                'message' => 'Pending requests retrieved successfully',
                'data' => [
                    'requests' => $requests,
                    'total_pending' => $requests->count(),
                    'vendor_info' => [
                        'id' => $vendor->id,
                        'name' => $vendor->name,
                        'availability_status' => $vendor->availability_status
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Get Pending Requests Error', [
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve requests',
                'error' => config('app.debug') ? $e->getMessage() : 'Server error'
            ], 500);
        }
    }


    /**
     * ✅ ACCEPT BOOKING REQUEST (With Full Details Response)
     */
    public function acceptBookingRequest(Request $request, $requestId): JsonResponse
    {
        try {
            $vendorId = $this->getVendorIdFromToken($request);

            Log::info('Accept booking attempt', [
                'request_id' => $requestId,
                'vendor_id' => $vendorId
            ]);

            if (!$vendorId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid or missing authorization token'
                ], 401);
            }

            DB::beginTransaction();

            try {
                // Find booking request with relationships
                $bookingRequest = BookingRequest::with(['booking.user', 'booking.material', 'booking.vehicleModel'])
                    ->find($requestId);

                if (!$bookingRequest) {
                    Log::error('Booking request not found', ['request_id' => $requestId]);
                    return response()->json([
                        'success' => false,
                        'message' => 'Booking request not found'
                    ], 404);
                }

                // Verify vendor
                if ($bookingRequest->vendor_id != $vendorId) {
                    Log::error('Unauthorized vendor', [
                        'expected_vendor' => $bookingRequest->vendor_id,
                        'actual_vendor' => $vendorId
                    ]);

                    return response()->json([
                        'success' => false,
                        'message' => 'Unauthorized to accept this booking'
                    ], 403);
                }

                // Check if expired
                if ($bookingRequest->isExpired()) {
                    Log::warning('Request expired', [
                        'request_id' => $requestId,
                        'expires_at' => $bookingRequest->expires_at
                    ]);

                    return response()->json([
                        'success' => false,
                        'message' => 'Request has expired'
                    ], 400);
                }

                // Check if already processed
                if ($bookingRequest->status !== 'pending') {
                    Log::warning('Request already processed', [
                        'request_id' => $requestId,
                        'status' => $bookingRequest->status
                    ]);

                    return response()->json([
                        'success' => false,
                        'message' => 'Request already ' . $bookingRequest->status
                    ], 400);
                }

                // Get vendor and booking
                $vendor = Vendor::findOrFail($vendorId);
                $booking = $bookingRequest->booking;
                $user = $booking->user;

                // Accept request
                $bookingRequest->update([
                    'status' => 'accepted',
                    'responded_at' => now()
                ]);

                // Assign vendor to booking
                $booking->update([
                    'assigned_vendor_id' => $vendor->id,
                    'vendor_name' => $vendor->name,
                    'vendor_vehicle_number' => $vendor->vehicle_registration_number,
                    'vendor_contact' => $vendor->contact_number,
                    'status' => 'confirmed',
                    'vendor_accepted_at' => now()
                ]);

                // Mark vendor as unavailable
                $vendor->update([
                    'availability_status' => 'out',
                    'is_available_for_booking' => false
                ]);

                // Expire other pending requests for this booking
                BookingRequest::where('booking_id', $booking->id)
                    ->where('id', '!=', $requestId)
                    ->where('status', 'pending')
                    ->update(['status' => 'expired']);

                DB::commit();

                Log::info('Booking accepted successfully', [
                    'booking_id' => $booking->booking_id,
                    'vendor_id' => $vendor->id,
                    'request_id' => $requestId
                ]);

                // ✅ GET COORDINATES
                $pickupLat = floatval($booking->pickup_latitude ?? 0);
                $pickupLng = floatval($booking->pickup_longitude ?? 0);
                $dropLat = floatval($booking->drop_latitude ?? 0);
                $dropLng = floatval($booking->drop_longitude ?? 0);

                // ✅ CALCULATE DISTANCE
                $aerialDistance = 0;
                $truckDistance = 0;
                if ($pickupLat && $pickupLng && $dropLat && $dropLng) {
                    $aerialDistance = $this->calculateDistance($pickupLat, $pickupLng, $dropLat, $dropLng);
                    $truckDistance = $aerialDistance * 1.18;
                }

                return response()->json([
                    'success' => true,
                    'message' => 'Booking accepted successfully! Get ready for pickup.',
                    'data' => [
                        'booking' => [
                            'id' => $booking->id,
                            'booking_id' => $booking->booking_id,
                            'status' => 'confirmed',
                            'status_label' => 'Booking Confirmed',

                            // ✅ USER DETAILS
                            'user' => [
                                'id' => $user->id,
                                'name' => $user->name,
                                'contact_number' => $user->contact_number ?? 'N/A',
                                'email' => $user->email ?? 'N/A'
                            ],

                            // ✅ PICKUP LOCATION WITH COORDINATES
                            'pickup_location' => [
                                'address' => $booking->pickup_address,
                                'latitude' => $pickupLat,
                                'longitude' => $pickupLng
                            ],

                            // ✅ DROP LOCATION WITH COORDINATES
                            'drop_location' => [
                                'address' => $booking->drop_address,
                                'latitude' => $dropLat,
                                'longitude' => $dropLng
                            ],

                            // ✅ DISTANCE INFO
                            'distance' => [
                                'aerial_km' => round($aerialDistance, 2),
                                'truck_route_km' => round($truckDistance, 2),
                                'display' => round($booking->distance_km ?? $truckDistance, 2) . ' km',
                                'google_maps_url' => "https://www.google.com/maps/dir/?api=1&origin={$pickupLat},{$pickupLng}&destination={$dropLat},{$dropLng}&travelmode=driving"
                            ],

                            // ✅ MATERIAL DETAILS
                            'material' => [
                                'name' => $booking->material_name,
                                'weight' => floatval($booking->material_weight),
                                'weight_display' => $booking->material_weight . ' tons'
                            ],

                            // ✅ VEHICLE DETAILS
                            'vehicle' => [
                                'model' => $booking->vehicleModel->model_name ?? 'N/A',
                                'specifications' => [
                                    'length' => $booking->truck_length . ' ft',
                                    'tyre_count' => $booking->tyre_count . ' Tyre',
                                    'height' => $booking->truck_height . ' ft'
                                ]
                            ],

                            // ✅ PRICING DETAILS
                            'pricing' => [
                                'estimated_price' => floatval($booking->estimated_price),
                                'adjusted_price' => $booking->adjusted_price ? floatval($booking->adjusted_price) : null,
                                'final_amount' => floatval($booking->final_amount),
                                'price_per_km' => $booking->price_per_km ? floatval($booking->price_per_km) : null,
                                'currency' => 'INR',
                                'display' => '₹' . number_format($booking->final_amount, 2)
                            ],

                            // ✅ PAYMENT INFO
                            'payment' => [
                                'method' => $booking->payment_method,
                                'status' => $booking->payment_status ?? 'pending',
                                'pay_at' => $booking->payment_method === 'pickup' ? 'Pickup Location' : 'Drop Location'
                            ],

                            // ✅ SCHEDULE
                            'schedule' => [
                                'pickup_datetime' => $booking->pickup_datetime ? $booking->pickup_datetime->format('Y-m-d H:i:s') : null,
                                'pickup_formatted' => $booking->pickup_datetime ? $booking->pickup_datetime->format('d M Y, h:i A') : null,
                                'accepted_at' => $booking->vendor_accepted_at->format('Y-m-d H:i:s'),
                                'accepted_formatted' => $booking->vendor_accepted_at->format('d M Y, h:i A'),
                                'created_at' => $booking->created_at->format('Y-m-d H:i:s')
                            ],

                            // ✅ SPECIAL INSTRUCTIONS
                            'special_instructions' => $booking->special_instructions ?? 'No special instructions',

                            // ✅ VENDOR STATUS
                            'vendor_status' => [
                                'availability' => 'out',
                                'status_label' => 'On Job',
                                'next_action' => 'Navigate to pickup location'
                            ]
                        ]
                    ]
                ], 200);
            } catch (\Exception $e) {
                DB::rollback();
                throw $e;
            }
        } catch (\Exception $e) {
            Log::error('Accept Booking Error', [
                'request_id' => $requestId,
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to accept booking',
                'error' => config('app.debug') ? $e->getMessage() : 'Server error'
            ], 500);
        }
    }

    /**
     * ✅ REJECT BOOKING REQUEST (Improved with validations)
     */
    public function rejectBookingRequest(Request $request, $requestId): JsonResponse
    {
        try {
            $vendorId = $this->getVendorIdFromToken($request);

            Log::info('Reject booking attempt', [
                'request_id' => $requestId,
                'vendor_id' => $vendorId
            ]);

            if (!$vendorId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid or missing authorization token'
                ], 401);
            }

            DB::beginTransaction();

            try {
                // Find booking request with relationships
                $bookingRequest = BookingRequest::with(['booking.user', 'booking.vehicleModel'])
                    ->find($requestId);

                if (!$bookingRequest) {
                    Log::error('Booking request not found', ['request_id' => $requestId]);
                    return response()->json([
                        'success' => false,
                        'message' => 'Booking request not found'
                    ], 404);
                }

                // Verify vendor ownership
                if ($bookingRequest->vendor_id != $vendorId) {
                    Log::error('Unauthorized vendor', [
                        'expected_vendor' => $bookingRequest->vendor_id,
                        'actual_vendor' => $vendorId
                    ]);

                    return response()->json([
                        'success' => false,
                        'message' => 'Unauthorized to reject this booking'
                    ], 403);
                }

                // Check if already processed
                if ($bookingRequest->status !== 'pending') {
                    Log::warning('Request already processed', [
                        'request_id' => $requestId,
                        'status' => $bookingRequest->status
                    ]);

                    return response()->json([
                        'success' => false,
                        'message' => 'Request already ' . $bookingRequest->status
                    ], 400);
                }

                $booking = $bookingRequest->booking;
                $vendor = Vendor::find($vendorId);

                // Reject request
                $bookingRequest->update([
                    'status' => 'rejected',
                    'responded_at' => now()
                ]);

                // ✅ MAKE VENDOR AVAILABLE AGAIN
                $vendor->update([
                    'availability_status' => 'available',
                    'is_available_for_booking' => true
                ]);

                Log::info('Booking rejected successfully', [
                    'request_id' => $requestId,
                    'booking_id' => $booking->booking_id,
                    'vendor_id' => $vendorId,
                    'vendor_name' => $vendor->name
                ]);

                // ✅ SEND TO NEXT VENDOR (if service exists)
                $nextVendorResult = null;
                try {
                    if (property_exists($this, 'vendorBookingService') && $this->vendorBookingService) {
                        $nextVendorResult = $this->vendorBookingService->sendBookingRequest($booking->id);
                    }
                } catch (\Exception $e) {
                    Log::warning('Failed to send to next vendor', [
                        'booking_id' => $booking->id,
                        'error' => $e->getMessage()
                    ]);
                }

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Booking rejected successfully',
                    'data' => [
                        'rejected_booking' => [
                            'booking_id' => $booking->booking_id,
                            'pickup_address' => $booking->pickup_address,
                            'drop_address' => $booking->drop_address,
                            'rejected_at' => now()->format('Y-m-d H:i:s'),
                            'rejected_formatted' => now()->format('d M Y, h:i A')
                        ],
                        'vendor_status' => [
                            'availability' => 'available',
                            'status_label' => 'Available for New Bookings',
                            'message' => 'You are now available to receive new booking requests'
                        ],
                        'next_action' => $nextVendorResult && $nextVendorResult['success']
                            ? 'Booking sent to next available vendor'
                            : 'Searching for other vendors...'
                    ]
                ], 200);
            } catch (\Exception $e) {
                DB::rollback();
                throw $e;
            }
        } catch (\Exception $e) {
            Log::error('Reject Booking Error', [
                'request_id' => $requestId,
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to reject booking',
                'error' => config('app.debug') ? $e->getMessage() : 'Server error'
            ], 500);
        }
    }


public function getVendorBookingHistory(Request $request): JsonResponse
{
    try {
        $vendorId = $this->getVendorIdFromToken($request);

        if (!$vendorId) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or missing authorization token'
            ], 401);
        }

        $vendor = Vendor::find($vendorId);
        if (!$vendor) {
            return response()->json([
                'success' => false,
                'message' => 'Vendor not found'
            ], 404);
        }

        // Get all bookings assigned to this vendor
        $allBookings = TruckBooking::where('assigned_vendor_id', $vendorId)
            ->with(['user', 'material', 'vehicleModel'])
            ->orderBy('created_at', 'desc')
            ->get();

        // Categorize bookings
        $activeBookings = $allBookings->whereIn('status', [
            'confirmed', 'in_transit', 'arrived', 'loading', 'in_progress'
        ]);
        $completedBookings = $allBookings->where('status', 'completed');
        $cancelledBookings = $allBookings->where('status', 'cancelled');

        // Format booking with safe null checks
        $formatBooking = function ($booking) {
            // Relation safe access
            $user = $booking->user ?? null;
            $vehicleModel = $booking->vehicleModel ?? null;
            $material = $booking->material ?? null;

            // Get coordinates
            $pickupLat = floatval($booking->pickup_latitude ?? 0);
            $pickupLng = floatval($booking->pickup_longitude ?? 0);
            $dropLat = floatval($booking->drop_latitude ?? 0);
            $dropLng = floatval($booking->drop_longitude ?? 0);

            // Calculate distance
            $aerialDistance = 0;
            $truckDistance = 0;
            if ($pickupLat && $pickupLng && $dropLat && $dropLng) {
                $aerialDistance = $this->calculateDistance($pickupLat, $pickupLng, $dropLat, $dropLng);
                $truckDistance = $aerialDistance * 1.18;
            }

            // Status labels
            $statusLabels = [
                'confirmed' => 'Booking Confirmed',
                'in_transit' => 'Trip in Progress',
                'arrived' => 'Arrived at Pickup',
                'loading' => 'Loading in Progress',
                'in_progress' => 'On the Way to Drop',
                'completed' => 'Trip Completed',
                'cancelled' => 'Booking Cancelled'
            ];

            return [
                'id' => $booking->id,
                'booking_id' => $booking->booking_id,
                'status' => $booking->status,
                'status_label' => $statusLabels[$booking->status] ?? ucfirst($booking->status),

                // User details (safe)
                'user' => [
                    'id' => $user?->id ?? null,
                    'name' => $user?->name ?? 'N/A',
                    'contact_number' => $user?->contact_number ?? 'N/A',
                    'email' => $user?->email ?? 'N/A'
                ],

                'pickup_location' => [
                    'address' => $booking->pickup_address,
                    'latitude' => $pickupLat,
                    'longitude' => $pickupLng
                ],

                'drop_location' => [
                    'address' => $booking->drop_address,
                    'latitude' => $dropLat,
                    'longitude' => $dropLng
                ],

                'distance' => [
                    'aerial_km' => round($aerialDistance, 2),
                    'truck_route_km' => round($truckDistance, 2),
                    'display' => round($booking->distance_km ?? $truckDistance, 2) . ' km',
                    'google_maps_url' => "https://www.google.com/maps/dir/?api=1&origin={$pickupLat},{$pickupLng}&destination={$dropLat},{$dropLng}&travelmode=driving"
                ],

                // Material
                'material' => [
                    'name' => $material?->name ?? $booking->material_name ?? 'N/A',
                    'weight' => floatval($booking->material_weight),
                    'weight_display' => $booking->material_weight . ' tons'
                ],

                // Vehicle
                'vehicle' => [
                    'model' => $vehicleModel?->model_name ?? 'N/A',
                    'specifications' => [
                        'length' => $booking->truck_length ? $booking->truck_length . ' ft' : 'N/A',
                        'tyre_count' => $booking->tyre_count ? $booking->tyre_count . ' Tyre' : 'N/A',
                        'height' => $booking->truck_height ? $booking->truck_height . ' ft' : 'N/A'
                    ]
                ],

                'pricing' => [
                    'estimated_price' => floatval($booking->estimated_price),
                    'adjusted_price' => $booking->adjusted_price ? floatval($booking->adjusted_price) : null,
                    'final_amount' => floatval($booking->final_amount),
                    'currency' => 'INR',
                    'display' => '₹' . number_format($booking->final_amount, 2)
                ],

                'payment' => [
                    'method' => $booking->payment_method,
                    'status' => $booking->payment_status ?? 'pending',
                    'completed_at' => $booking->payment_completed_at ? $booking->payment_completed_at->format('Y-m-d H:i:s') : null
                ],

                'timeline' => [
                    'booking_created' => $booking->created_at ? $booking->created_at->format('Y-m-d H:i:s') : null,
                    'vendor_accepted' => $booking->vendor_accepted_at ? $booking->vendor_accepted_at->format('Y-m-d H:i:s') : null,
                    'pickup_scheduled' => $booking->pickup_datetime ? $booking->pickup_datetime->format('Y-m-d H:i:s') : null,
                    'trip_started' => $booking->trip_started_at ? $booking->trip_started_at->format('Y-m-d H:i:s') : null,
                    'trip_completed' => $booking->trip_completed_at ? $booking->trip_completed_at->format('Y-m-d H:i:s') : null,
                    'cancelled_at' => $booking->cancelled_at ? $booking->cancelled_at->format('Y-m-d H:i:s') : null
                ],

                'special_instructions' => $booking->special_instructions ?? 'No special instructions'
            ];
        };

        // Calculate earnings
        $totalEarnings = $completedBookings->sum('final_amount');
        $pendingPayments = $completedBookings->where('payment_status', 'pending')->sum('final_amount');

        return response()->json([
            'success' => true,
            'message' => 'Booking history retrieved successfully',
            'data' => [
                'active' => [
                    'count' => $activeBookings->count(),
                    'bookings' => $activeBookings->map($formatBooking)->values()
                ],
                'completed' => [
                    'count' => $completedBookings->count(),
                    'bookings' => $completedBookings->map($formatBooking)->values()
                ],
                'cancelled' => [
                    'count' => $cancelledBookings->count(),
                    'bookings' => $cancelledBookings->map($formatBooking)->values()
                ],
                'summary' => [
                    'total_bookings' => $allBookings->count(),
                    'active_count' => $activeBookings->count(),
                    'completed_count' => $completedBookings->count(),
                    'cancelled_count' => $cancelledBookings->count(),
                    'total_earnings' => floatval($totalEarnings),
                    'total_earnings_display' => '₹' . number_format($totalEarnings, 2),
                    'pending_payments' => floatval($pendingPayments),
                    'pending_payments_display' => '₹' . number_format($pendingPayments, 2)
                ],
                'vendor_info' => [
                    'id' => $vendor->id,
                    'name' => $vendor->name,
                    'contact_number' => $vendor->contact_number,
                    'vehicle_registration_number' => $vendor->vehicle_registration_number,
                    'availability_status' => $vendor->availability_status
                ]
            ]
        ]);
    } catch (\Exception $e) {
        Log::error('Get Vendor History Error', [
            'error' => $e->getMessage(),
            'line' => $e->getLine(),
            'file' => $e->getFile()
        ]);
        return response()->json([
            'success' => false,
            'message' => 'Failed to retrieve booking history',
            'error' => config('app.debug') ? $e->getMessage() : 'Server error'
        ], 500);
    }
}



    public function getVendorActiveBookings(Request $request): JsonResponse
    {
        try {
            $vendorId = $this->getVendorIdFromToken($request);

            if (!$vendorId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid or missing authorization token'
                ], 401);
            }

            // Get active bookings
            $bookings = TruckBooking::where('assigned_vendor_id', $vendorId)
                ->whereIn('status', ['confirmed', 'in_transit', 'arrived', 'loading', 'in_progress'])
                ->with(['user', 'material', 'vehicleModel'])
                ->orderBy('created_at', 'desc')
                ->paginate(10);

            // Format bookings
            $formattedBookings = $bookings->map(function ($booking) {
                // Get coordinates
                $pickupLat = floatval($booking->pickup_latitude ?? 0);
                $pickupLng = floatval($booking->pickup_longitude ?? 0);
                $dropLat = floatval($booking->drop_latitude ?? 0);
                $dropLng = floatval($booking->drop_longitude ?? 0);

                // Calculate distance
                $aerialDistance = 0;
                $truckDistance = 0;
                if ($pickupLat && $pickupLng && $dropLat && $dropLng) {
                    $aerialDistance = $this->calculateDistance($pickupLat, $pickupLng, $dropLat, $dropLng);
                    $truckDistance = $aerialDistance * 1.18;
                }

                // Status labels
                $statusLabels = [
                    'confirmed' => 'Booking Confirmed',
                    'in_transit' => 'Trip in Progress',
                    'arrived' => 'Arrived at Pickup',
                    'loading' => 'Loading in Progress',
                    'in_progress' => 'On the Way to Drop'
                ];

                return [
                    'id' => $booking->id,
                    'booking_id' => $booking->booking_id,
                    'status' => $booking->status,
                    'status_label' => $statusLabels[$booking->status] ?? ucfirst($booking->status),

                    // User details
                    'user' => [
                        'id' => $booking->user->id,
                        'name' => $booking->user->name,
                        'contact_number' => $booking->user->contact_number ?? 'N/A',
                        'email' => $booking->user->email ?? 'N/A'
                    ],

                    // Pickup location
                    'pickup_location' => [
                        'address' => $booking->pickup_address,
                        'latitude' => $pickupLat,
                        'longitude' => $pickupLng
                    ],

                    // Drop location
                    'drop_location' => [
                        'address' => $booking->drop_address,
                        'latitude' => $dropLat,
                        'longitude' => $dropLng
                    ],

                    // Distance
                    'distance' => [
                        'aerial_km' => round($aerialDistance, 2),
                        'truck_route_km' => round($truckDistance, 2),
                        'display' => round($booking->distance_km ?? $truckDistance, 2) . ' km',
                        'google_maps_url' => "https://www.google.com/maps/dir/?api=1&origin={$pickupLat},{$pickupLng}&destination={$dropLat},{$dropLng}&travelmode=driving"
                    ],

                    // Material
                    'material' => [
                        'name' => $booking->material_name,
                        'weight' => floatval($booking->material_weight),
                        'weight_display' => $booking->material_weight . ' tons'
                    ],

                    // Vehicle
                    'vehicle' => [
                        'model' => $booking->vehicleModel->model_name ?? 'N/A',
                        'specifications' => [
                            'length' => $booking->truck_length . ' ft',
                            'tyre_count' => $booking->tyre_count . ' Tyre',
                            'height' => $booking->truck_height . ' ft'
                        ]
                    ],

                    // Pricing
                    'pricing' => [
                        'estimated_price' => floatval($booking->estimated_price),
                        'adjusted_price' => $booking->adjusted_price ? floatval($booking->adjusted_price) : null,
                        'final_amount' => floatval($booking->final_amount),
                        'currency' => 'INR',
                        'display' => '₹' . number_format($booking->final_amount, 2)
                    ],

                    // Payment
                    'payment' => [
                        'method' => $booking->payment_method,
                        'status' => $booking->payment_status ?? 'pending',
                        'pay_at' => $booking->payment_method === 'pickup' ? 'Pickup Location' : 'Drop Location'
                    ],

                    // Schedule
                    'schedule' => [
                        'pickup_datetime' => $booking->pickup_datetime ? $booking->pickup_datetime->format('Y-m-d H:i:s') : null,
                        'pickup_formatted' => $booking->pickup_datetime ? $booking->pickup_datetime->format('d M Y, h:i A') : null,
                        'vendor_accepted_at' => $booking->vendor_accepted_at ? $booking->vendor_accepted_at->format('Y-m-d H:i:s') : null,
                        'trip_started_at' => $booking->trip_started_at ? $booking->trip_started_at->format('Y-m-d H:i:s') : null,
                        'created_at' => $booking->created_at->format('Y-m-d H:i:s')
                    ],

                    'special_instructions' => $booking->special_instructions ?? 'No special instructions'
                ];
            });

            return response()->json([
                'success' => true,
                'message' => $bookings->total() . ' active booking(s) found',
                'data' => [
                    'bookings' => $formattedBookings,
                    'pagination' => [
                        'current_page' => $bookings->currentPage(),
                        'last_page' => $bookings->lastPage(),
                        'per_page' => $bookings->perPage(),
                        'total' => $bookings->total(),
                        'from' => $bookings->firstItem(),
                        'to' => $bookings->lastItem()
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Get Vendor Active Bookings Error', [
                'error' => $e->getMessage(),
                'line' => $e->getLine()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve active bookings',
                'error' => config('app.debug') ? $e->getMessage() : 'Server error'
            ], 500);
        }
    }


public function getVendorCompletedBookings(Request $request): JsonResponse
{
    try {
        $vendorId = $this->getVendorIdFromToken($request);

        if (!$vendorId) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or missing authorization token'
            ], 401);
        }

        // Get completed & cancelled bookings
        $bookings = TruckBooking::where('assigned_vendor_id', $vendorId)
            ->whereIn('status', ['completed', 'cancelled'])
            ->with(['user', 'material', 'vehicleModel'])
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        // Format bookings
        $formattedBookings = $bookings->map(function ($booking) {
            // Relations (safe)
            
            $user = $booking->user ?? null;
            $material = $booking->material ?? null;
            $vehicleModel = $booking->vehicleModel ?? null;

            // Get coordinates
            $pickupLat = floatval($booking->pickup_latitude ?? 0);
            $pickupLng = floatval($booking->pickup_longitude ?? 0);
            $dropLat = floatval($booking->drop_latitude ?? 0);
            $dropLng = floatval($booking->drop_longitude ?? 0);

            // Calculate distance
            $aerialDistance = 0;
            $truckDistance = 0;
            if ($pickupLat && $pickupLng && $dropLat && $dropLng) {
                $aerialDistance = $this->calculateDistance($pickupLat, $pickupLng, $dropLat, $dropLng);
                $truckDistance = $aerialDistance * 1.18;
            }

            // Calculate trip duration
            $tripDuration = null;
            if (
                $booking->status === 'completed'
                && $booking->trip_started_at && $booking->trip_completed_at
            ) {
                $start = \Carbon\Carbon::parse($booking->trip_started_at);
                $end = \Carbon\Carbon::parse($booking->trip_completed_at);
                $tripDuration = [
                    'hours' => $start->diffInHours($end),
                    'minutes' => $start->diffInMinutes($end),
                    'formatted' => $start->diff($end)->format('%h hours %i minutes')
                ];
            }

            return [
                'id' => $booking->id,
                'booking_id' => $booking->booking_id,
                'status' => $booking->status,
                'status_label' => $booking->status === 'completed' ? 'Trip Completed' : 'Booking Cancelled',
            

                // User details (safe)
                'user' => [
                    'id' => $user?->id ?? null,
                    'name' => $user?->name ?? 'N/A',
                    'contact_number' => $user?->contact_number ?? 'N/A',
                    'email' => $user?->email ?? 'N/A'
                ],

                // Pickup location
                'pickup_location' => [
                    'address' => $booking->pickup_address,
                    'latitude' => $pickupLat,
                    'longitude' => $pickupLng
                ],

                // Drop location
                'drop_location' => [
                    'address' => $booking->drop_address,
                    'latitude' => $dropLat,
                    'longitude' => $dropLng
                ],

                'distance' => [
                    'aerial_km' => round($aerialDistance, 2),
                    'truck_route_km' => round($truckDistance, 2),
                    'display' => round($booking->distance_km ?? $truckDistance, 2) . ' km'
                ],

                // Material (safe)
                'material' => [
                    'name' => $material?->name ?? $booking->material_name ?? '',
                    'weight' => floatval($booking->material_weight),
                    'weight_display' => $booking->material_weight . ' tons'
                ],

                // Vehicle (safe)
                'vehicle' => [
                    'model' => $vehicleModel?->model_name ?? 'N/A',
                    'specifications' => [
                        'length' => $booking->truck_length ? $booking->truck_length . ' ft' : 'N/A',
                        'tyre_count' => $booking->tyre_count ? $booking->tyre_count . ' Tyre' : 'N/A',
                        'height' => $booking->truck_height ? $booking->truck_height . ' ft' : 'N/A'
                    ]
                ],

                // Pricing
                'pricing' => [
                    'estimated_price' => floatval($booking->estimated_price),
                    'adjusted_price' => $booking->adjusted_price ? floatval($booking->adjusted_price) : null,
                    'final_amount' => floatval($booking->final_amount),
                    'currency' => 'INR',
                    'display' => '₹' . number_format($booking->final_amount, 2)
                ],

                // Payment
                'payment' => [
                    'method' => $booking->payment_method,
                    'status' => $booking->payment_status ?? 'pending',
                    'completed_at' => $booking->payment_completed_at ? $booking->payment_completed_at->format('Y-m-d H:i:s') : null
                ],

                // Timeline
                'timeline' => [
                    'booking_created' => $booking->created_at ? $booking->created_at->format('Y-m-d H:i:s') : null,
                    'vendor_accepted' => $booking->vendor_accepted_at ? $booking->vendor_accepted_at->format('Y-m-d H:i:s') : null,
                    'trip_started' => $booking->trip_started_at ? $booking->trip_started_at->format('Y-m-d H:i:s') : null,
                    'trip_completed' => $booking->trip_completed_at ? $booking->trip_completed_at->format('Y-m-d H:i:s') : null,
                    'cancelled_at' => $booking->cancelled_at ? $booking->cancelled_at->format('Y-m-d H:i:s') : null,
                    'duration' => $tripDuration
                ],

                'special_instructions' => $booking->special_instructions ?? 'No special instructions',
                'cancellation_reason' => $booking->cancellation_reason ?? null
            ];
        });

        // Calculate stats
        $completedBookings = $bookings->filter(fn($b) => $b->status === 'completed');
        $cancelledBookings = $bookings->filter(fn($b) => $b->status === 'cancelled');
        $totalEarnings = $completedBookings->sum('final_amount');

        return response()->json([
            'success' => true,
            'message' => $bookings->total() . ' completed/cancelled booking(s) found',
            'data' => [
                'bookings' => $formattedBookings,
                'pagination' => [
                    'current_page' => $bookings->currentPage(),
                    'last_page' => $bookings->lastPage(),
                    'per_page' => $bookings->perPage(),
                    'total' => $bookings->total(),
                    'from' => $bookings->firstItem(),
                    'to' => $bookings->lastItem()
                ],
                'stats' => [
                    'completed_count' => $completedBookings->count(),
                    'cancelled_count' => $cancelledBookings->count(),
                    'total_earnings' => floatval($totalEarnings),
                    'total_earnings_display' => '₹' . number_format($totalEarnings, 2)
                ]
            ]
        ]);
    } catch (\Exception $e) {
        Log::error('Get Vendor Completed Bookings Error', [
            'error' => $e->getMessage(),
            'line' => $e->getLine()
        ]);
        return response()->json([
            'success' => false,
            'message' => 'Failed to retrieve completed bookings',
            'error' => config('app.debug') ? $e->getMessage() : 'Server error'
        ], 500);
    }
}



    public function trackBooking(Request $request, $bookingId): JsonResponse
    {
        try {
            $booking = TruckBooking::findOrFail($bookingId);

            // Gather coordinates
            $pickupLat = $booking->pickup_latitude;
            $pickupLng = $booking->pickup_longitude;
            $dropLat = $booking->drop_latitude;
            $dropLng = $booking->drop_longitude;
            $currentLat = $booking->current_vendor_latitude;
            $currentLng = $booking->current_vendor_longitude;

            // Get route polyline and steps from service
            $mapsService = new \App\Services\GoogleMapsService();
            $routeResult = $mapsService->getRouteDirections($pickupLat, $pickupLng, $dropLat, $dropLng);

            // Collect past locations if you want to show the path covered
            $pathCoordinates = [];
            if ($booking->location_history) {
                $pathCoordinates = json_decode($booking->location_history, true) ?? [];
            }

            return response()->json([
                'success' => true,
                'message' => 'Booking tracking data retrieved',
                'data' => [
                    'status' => $booking->status,
                    'status_badge' => $booking->getStatusBadgeAttribute(),
                    'pickup' => [
                        'address' => $booking->pickup_address,
                        'lat' => (float) $pickupLat,
                        'lng' => (float) $pickupLng
                    ],
                    'drop' => [
                        'address' => $booking->drop_address,
                        'lat' => (float) $dropLat,
                        'lng' => (float) $dropLng
                    ],
                    'driver' => [
                        'name' => $booking->vendor_name,
                        'vehicle' => $booking->vendor_vehicle_number,
                        'lat' => (float) $currentLat,
                        'lng' => (float) $currentLng,
                        'last_updated' => $booking->location_updated_at?->format('Y-m-d H:i:s')
                    ],
                    'route' => [
                        'polyline' => $routeResult['polyline'] ?? null,
                        'steps' => $routeResult['steps'] ?? [],
                    ],
                    'journey_progress' => [
                        'location_history' => $pathCoordinates,
                        'distance_covered_km' => (float) ($booking->distance_covered_km ?? 0),
                        'distance_remaining_km' => (float) ($booking->distance_remaining_km ?? 0),
                        'estimated_arrival_time' => $booking->estimated_arrival_time
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Track Booking Error', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch booking tracking info'
            ], 500);
        }
    }


    public function getBookingLocation(Request $request, $bookingId, $type): JsonResponse
    {
        try {
            $booking = TruckBooking::findOrFail($bookingId);

            $pickupLat = floatval($booking->pickup_latitude);
            $pickupLng = floatval($booking->pickup_longitude);
            $dropLat = floatval($booking->drop_latitude);
            $dropLng = floatval($booking->drop_longitude);

            // ✅ Calculate Pickup to Drop distance (needed for both APIs)
            $pickupToDropAerial = $this->calculateDistance($pickupLat, $pickupLng, $dropLat, $dropLng);
            $pickupToDropTruck = $pickupToDropAerial * 1.18;

            if ($type === 'pickup') {
                // ✅ PICKUP: Driver's current location to pickup point
                $lat = $pickupLat;
                $lng = $pickupLng;
                $address = $booking->pickup_address;
                $label = "Pickup Location";

                // Get vendor's current location
                $vendor = $booking->assignedVendor;
                $vendorLat = $vendor ? floatval($vendor->current_latitude) : null;
                $vendorLng = $vendor ? floatval($vendor->current_longitude) : null;

                if ($vendorLat && $vendorLng) {
                    // Calculate distance from driver's current location to pickup
                    $driverToPickupAerial = $this->calculateDistance($vendorLat, $vendorLng, $pickupLat, $pickupLng);
                    $driverToPickupTruck = $driverToPickupAerial * 1.18;

                    $distanceData = [
                        'driver_to_pickup' => [
                            'aerial_km' => round($driverToPickupAerial, 2),
                            'road_km' => round($driverToPickupTruck, 2),
                            'display' => round($driverToPickupTruck, 2) . ' km'
                        ],
                        'pickup_to_drop' => [
                            'aerial_km' => round($pickupToDropAerial, 2),
                            'truck_route_km' => round($pickupToDropTruck, 2),
                            'display' => round($pickupToDropTruck, 2) . ' km'
                        ],
                        'note' => 'Driver to Pickup + Full Trip Distance',
                        'driver_location' => [
                            'latitude' => $vendorLat,
                            'longitude' => $vendorLng,
                            'address' => $vendor->current_location ?? 'Current Location'
                        ]
                    ];

                    // Direction URL: Driver's location to pickup
                    $directionsUrl = "https://www.google.com/maps/dir/?api=1&origin={$vendorLat},{$vendorLng}&destination={$pickupLat},{$pickupLng}&travelmode=driving";

                    // Pickup to drop route URL
                    $pickupToDropUrl = "https://www.google.com/maps/dir/?api=1&origin={$pickupLat},{$pickupLng}&destination={$dropLat},{$dropLng}&travelmode=driving";
                    $distanceData['pickup_to_drop_route_url'] = $pickupToDropUrl;
                } else {
                    // No driver location available, but still show pickup to drop
                    $distanceData = [
                        'driver_to_pickup' => [
                            'note' => 'Driver location not available yet'
                        ],
                        'pickup_to_drop' => [
                            'aerial_km' => round($pickupToDropAerial, 2),
                            'truck_route_km' => round($pickupToDropTruck, 2),
                            'display' => round($pickupToDropTruck, 2) . ' km'
                        ],
                        'driver_location' => null
                    ];
                    $directionsUrl = "https://www.google.com/maps/dir/?api=1&destination={$pickupLat},{$pickupLng}";

                    $pickupToDropUrl = "https://www.google.com/maps/dir/?api=1&origin={$pickupLat},{$pickupLng}&destination={$dropLat},{$dropLng}&travelmode=driving";
                    $distanceData['pickup_to_drop_route_url'] = $pickupToDropUrl;
                }
            } elseif ($type === 'drop') {
                // ✅ DROP: Pickup to drop full route distance
                $lat = $dropLat;
                $lng = $dropLng;
                $address = $booking->drop_address;
                $label = "Drop Location";

                $distanceData = [
                    'pickup_to_drop' => [
                        'aerial_km' => round($pickupToDropAerial, 2),
                        'truck_route_km' => round($pickupToDropTruck, 2),
                        'display' => round($pickupToDropTruck, 2) . ' km (Truck Route)'
                    ],
                    'note' => 'Total distance from pickup to drop location'
                ];

                // Direction URL: Pickup to drop
                $directionsUrl = "https://www.google.com/maps/dir/?api=1&origin={$pickupLat},{$pickupLng}&destination={$dropLat},{$dropLng}&travelmode=driving";
            } else {
                return response()->json([
                    'success' => false,
                    'message' => "Invalid type: use 'pickup' or 'drop'"
                ], 400);
            }

            $searchUrl = "https://www.google.com/maps/search/?api=1&query={$lat},{$lng}";

            return response()->json([
                'success' => true,
                'message' => $label . " of booking retrieved",
                'data' => [
                    'type' => $type,
                    'label' => $label,
                    'address' => $address,
                    'latitude' => $lat,
                    'longitude' => $lng,
                    'google_maps_search_url' => $searchUrl,
                    'google_maps_direction_url' => $directionsUrl,
                    'distance' => $distanceData,
                    'pickup_location' => [
                        'address' => $booking->pickup_address,
                        'latitude' => $pickupLat,
                        'longitude' => $pickupLng
                    ],
                    'drop_location' => [
                        'address' => $booking->drop_address,
                        'latitude' => $dropLat,
                        'longitude' => $dropLng
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Booking Location Error', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch location'
            ], 500);
        }
    }

    /**
     * Calculate distance between two coordinates (Haversine formula)
     */
    // private function calculateDistance($lat1, $lon1, $lat2, $lon2)
    // {
    //     $earthRadius = 6371;
    //     $dLat = deg2rad($lat2 - $lat1);
    //     $dLon = deg2rad($lon2 - $lon1);
    //     $a = sin($dLat / 2) * sin($dLat / 2) +
    //          cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
    //          sin($dLon / 2) * sin($dLon / 2);
    //     $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
    //     return $earthRadius * $c;
    // }

    /**
     * ✅ Calculate distance helper
     */
    private function calculateDistance($lat1, $lon1, $lat2, $lon2)
    {
        if (empty($lat1) || empty($lon1) || empty($lat2) || empty($lon2)) {
            return 0;
        }

        $earthRadius = 6371;
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }
}
