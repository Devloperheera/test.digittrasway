<?php
// app/Http/Controllers/Api/VendorAvailabilityController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Vendor;
use App\Services\GoogleMapsService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class VendorAvailabilityController extends Controller
{
    private $mapsService;

    public function __construct(GoogleMapsService $mapsService)
    {
        $this->mapsService = $mapsService;
    }

    // ✅ GO ONLINE WITH LOCATION
    public function goOnline(Request $request): JsonResponse
    {
        try {
            $authHeader = $request->header('Authorization');
            if (!$authHeader || !str_contains($authHeader, 'Bearer ')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Authorization token required'
                ], 401);
            }

            $token = str_replace('Bearer ', '', $authHeader);
            $decodedToken = base64_decode($token);
            $tokenParts = explode(':', $decodedToken);

            if (count($tokenParts) < 3) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid token format'
                ], 401);
            }

            $vendorId = $tokenParts[0];
            $vendor = Vendor::find($vendorId);

            if (!$vendor) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vendor not found'
                ], 404);
            }

            // Validate
            $validator = Validator::make($request->all(), [
                'latitude' => 'required|numeric|between:-90,90',
                'longitude' => 'required|numeric|between:-180,180'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 400);
            }

            // Check vehicle status
            if (!$vendor->vehicle_registration_number || $vendor->vehicle_status !== 'approved') {
                return response()->json([
                    'success' => false,
                    'message' => 'Your vehicle must be active to go online'
                ], 400);
            }

            if ($vendor->availability_status === 'in') {
                return response()->json([
                    'success' => false,
                    'message' => 'You are already online'
                ], 409);
            }

            // ✅ FIXED: Get address from coordinates
            $location = null;
            $geocodeResult = $this->mapsService->getAddressFromCoordinates(
                $request->latitude,
                $request->longitude
            );

            if ($geocodeResult['success']) {
                $location = $geocodeResult['address'];
            }

            // Go online
            $vendor->goOnline(
                $request->latitude,
                $request->longitude,
                $location
            );

            return response()->json([
                'success' => true,
                'message' => 'You are now online and available for bookings!',
                'data' => [
                    'vendor' => [
                        'id' => $vendor->id,
                        'name' => $vendor->name,
                        'contact_number' => $vendor->contact_number
                    ],
                    'status' => 'in',
                    'went_online_at' => $vendor->last_in_time,
                    'location' => [
                        'latitude' => $vendor->current_latitude,
                        'longitude' => $vendor->current_longitude,
                        'address' => $vendor->current_location
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Go Online Error', [
                'vendor_id' => $vendorId ?? null,
                'error' => $e->getMessage(),
                'line' => $e->getLine()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to go online: ' . $e->getMessage()
            ], 500);
        }
    }

    // ✅ GO OFFLINE
    public function goOffline(Request $request): JsonResponse
    {
        try {
            $authHeader = $request->header('Authorization');
            if (!$authHeader || !str_contains($authHeader, 'Bearer ')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Authorization token required'
                ], 401);
            }

            $token = str_replace('Bearer ', '', $authHeader);
            $decodedToken = base64_decode($token);
            $tokenParts = explode(':', $decodedToken);

            if (count($tokenParts) < 3) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid token format'
                ], 401);
            }

            $vendorId = $tokenParts[0];
            $vendor = Vendor::find($vendorId);

            if (!$vendor) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vendor not found'
                ], 404);
            }

            if ($vendor->availability_status === 'out') {
                return response()->json([
                    'success' => false,
                    'message' => 'You are already offline'
                ], 409);
            }

            $onlineDuration = $vendor->online_duration;
            $vendor->goOffline();

            return response()->json([
                'success' => true,
                'message' => 'You are now offline',
                'data' => [
                    'vendor' => [
                        'id' => $vendor->id,
                        'name' => $vendor->name
                    ],
                    'status' => 'out',
                    'went_offline_at' => $vendor->last_out_time,
                    'was_online_for' => $onlineDuration
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Go Offline Error', [
                'vendor_id' => $vendorId ?? null,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to go offline: ' . $e->getMessage()
            ], 500);
        }
    }

    // ✅ UPDATE LOCATION
    public function updateLocation(Request $request): JsonResponse
    {
        try {
            $authHeader = $request->header('Authorization');
            if (!$authHeader || !str_contains($authHeader, 'Bearer ')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Authorization token required'
                ], 401);
            }

            $token = str_replace('Bearer ', '', $authHeader);
            $decodedToken = base64_decode($token);
            $tokenParts = explode(':', $decodedToken);

            if (count($tokenParts) < 3) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid token format'
                ], 401);
            }

            $vendorId = $tokenParts[0];
            $vendor = Vendor::find($vendorId);

            if (!$vendor) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vendor not found'
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'latitude' => 'required|numeric|between:-90,90',
                'longitude' => 'required|numeric|between:-180,180'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 400);
            }

            if ($vendor->availability_status !== 'in') {
                return response()->json([
                    'success' => false,
                    'message' => 'You must be online to update location'
                ], 400);
            }

            // ✅ FIXED: Get address
            $location = null;
            $geocodeResult = $this->mapsService->getAddressFromCoordinates(
                $request->latitude,
                $request->longitude
            );

            if ($geocodeResult['success']) {
                $location = $geocodeResult['address'];
            }

            $vendor->updateLocation(
                $request->latitude,
                $request->longitude,
                $location
            );

            return response()->json([
                'success' => true,
                'message' => 'Location updated successfully',
                'data' => [
                    'latitude' => $vendor->current_latitude,
                    'longitude' => $vendor->current_longitude,
                    'address' => $vendor->current_location,
                    'updated_at' => now()
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Update Location Error', [
                'vendor_id' => $vendorId ?? null,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update location: ' . $e->getMessage()
            ], 500);
        }
    }

    // ✅ GET NEARBY AVAILABLE VENDORS
    public function getNearbyVendors(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'latitude' => 'required|numeric|between:-90,90',
                'longitude' => 'required|numeric|between:-180,180',
                'radius_km' => 'nullable|numeric|min:1|max:100'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 400);
            }

            $radiusKm = $request->radius_km ?? 10;

            $result = $this->mapsService->findNearbyVendors(
                $request->latitude,
                $request->longitude,
                $radiusKm
            );

            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $result['message']
                ], 500);
            }

            $vendors = $result['vendors']->map(function ($item) {
                return [
                    'id' => $item['vendor']->id,
                    'name' => $item['vendor']->name,
                    'contact_number' => $item['vendor']->contact_number,
                    'vehicle' => [
                        'type' => $item['vendor']->vehicle_type,
                        'number' => $item['vendor']->vehicle_registration_number,
                        'brand' => $item['vendor']->vehicle_brand_model,
                        'specifications' => $item['vendor']->vehicle_full_spec
                    ],
                    'location' => [
                        'latitude' => $item['vendor']->current_latitude,
                        'longitude' => $item['vendor']->current_longitude,
                        'address' => $item['vendor']->current_location
                    ],
                    'distance_km' => $item['distance_km'],
                    'online_since' => $item['vendor']->last_in_time,
                    'online_duration' => $item['vendor']->online_duration
                ];
            });

            return response()->json([
                'success' => true,
                'message' => 'Nearby vendors retrieved successfully',
                'data' => [
                    'search_location' => [
                        'latitude' => $request->latitude,
                        'longitude' => $request->longitude
                    ],
                    'vendors' => $vendors,
                    'total_found' => $vendors->count(),
                    'search_radius_km' => $radiusKm
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Get Nearby Vendors Error', [
                'error' => $e->getMessage(),
                'line' => $e->getLine()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to get nearby vendors: ' . $e->getMessage()
            ], 500);
        }
    }

    // ✅ GET VENDOR STATUS
    public function getStatus(Request $request): JsonResponse
    {
        try {
            $authHeader = $request->header('Authorization');
            if (!$authHeader || !str_contains($authHeader, 'Bearer ')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Authorization token required'
                ], 401);
            }

            $token = str_replace('Bearer ', '', $authHeader);
            $decodedToken = base64_decode($token);
            $tokenParts = explode(':', $decodedToken);

            if (count($tokenParts) < 3) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid token format'
                ], 401);
            }

            $vendorId = $tokenParts[0];
            $vendor = Vendor::find($vendorId);

            if (!$vendor) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vendor not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Status retrieved successfully',
                'data' => [
                    'vendor' => [
                        'id' => $vendor->id,
                        'name' => $vendor->name,
                        'contact_number' => $vendor->contact_number,
                        'email' => $vendor->email
                    ],
                    'availability' => [
                        'status' => $vendor->availability_status,
                        'is_online' => $vendor->is_online,
                        'can_accept_bookings' => $vendor->can_accept_bookings,
                        'last_in_time' => $vendor->last_in_time,
                        'last_out_time' => $vendor->last_out_time,
                        'online_duration' => $vendor->online_duration
                    ],
                    'location' => [
                        'latitude' => $vendor->current_latitude,
                        'longitude' => $vendor->current_longitude,
                        'address' => $vendor->current_location
                    ],
                    'vehicle' => [
                        'status' => $vendor->vehicle_status,
                        'type' => $vendor->vehicle_type,
                        'number' => $vendor->vehicle_registration_number,
                        'brand' => $vendor->vehicle_brand_model,
                        'is_listed' => $vendor->vehicle_listed
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Get Status Error', [
                'vendor_id' => $vendorId ?? null,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to get status: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * TOGGLE AVAILABILITY (ONLINE/OFFLINE)
     */
    public function toggleAvailability(Request $request)
    {
        try {
            // Token validation
            $authHeader = $request->header('Authorization');
            if (!$authHeader || !str_contains($authHeader, 'Bearer ')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Authorization token required'
                ], 401);
            }

            $token = str_replace('Bearer ', '', $authHeader);
            $decodedToken = base64_decode($token);
            $tokenParts = explode(':', $decodedToken);

            if (count($tokenParts) < 3) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid token format'
                ], 401);
            }

            $vendorId = $tokenParts[0];

            // ✅ FIXED: Correct Vendor model
            $vendor = Vendor::find($vendorId);

            if (!$vendor) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vendor not found'
                ], 404);
            }

            // Check current status
            if ($vendor->availability_status === 'in') {
                // ✅ GO OFFLINE
                $vendor->goOffline();

                Log::info('Vendor went offline', [
                    'vendor_id' => $vendor->id,
                    'name' => $vendor->name
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'You are now offline',
                    'data' => [
                        'status' => 'out',
                        'is_available_for_booking' => false,
                        'last_out_time' => now()->format('Y-m-d H:i:s')
                    ]
                ]);
            } else {
                // ✅ GO ONLINE - Validation checks

                // Check 1: Profile completed?
                if (!$vendor->is_completed) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Please complete your profile first.'
                    ], 400);
                }

                // Check 2: Vehicle registered?
                if (!$vendor->vehicle_registration_number) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Please add your vehicle details first.'
                    ], 400);
                }

                // Check 3: Vehicle approved?
                if ($vendor->vehicle_status !== 'approved') {
                    return response()->json([
                        'success' => false,
                        'message' => 'Cannot go online. Vehicle approval pending.',
                        'current_status' => $vendor->vehicle_status ?? 'not_submitted'
                    ], 400);
                }

                // Check 4: Vehicle listed/active?
                if (!$vendor->vehicle_listed || $vendor->vehicle_listed != 1) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Cannot go online. Vehicle must be active.'
                    ], 400);
                }

                // Validate location
                $validator = Validator::make($request->all(), [
                    'latitude' => 'required|numeric|between:-90,90',
                    'longitude' => 'required|numeric|between:-180,180'
                ]);

                if ($validator->fails()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Location required to go online',
                        'errors' => $validator->errors()
                    ], 400);
                }

                // Get address from coordinates
                $location = null;
                try {
                    $geocodeResult = $this->mapsService->getAddressFromCoordinates(
                        $request->latitude,
                        $request->longitude
                    );

                    if ($geocodeResult['success']) {
                        $location = $geocodeResult['address'];
                    }
                } catch (\Exception $e) {
                    Log::warning('Geocoding failed', ['error' => $e->getMessage()]);
                    $location = "Lat: {$request->latitude}, Lng: {$request->longitude}";
                }

                // ✅ GO ONLINE
                $vendor->goOnline(
                    $request->latitude,
                    $request->longitude,
                    $location
                );

                Log::info('Vendor went online', [
                    'vendor_id' => $vendor->id,
                    'name' => $vendor->name,
                    'location' => $location
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'You are now online and available for bookings',
                    'data' => [
                        'status' => 'in',
                        'is_available_for_booking' => true,
                        'location' => [
                            'latitude' => $request->latitude,
                            'longitude' => $request->longitude,
                            'address' => $location
                        ],
                        'last_in_time' => now()->format('Y-m-d H:i:s')
                    ]
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Toggle Availability Error', [
                'vendor_id' => $vendorId ?? null,
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to toggle availability: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET AVAILABILITY STATUS
     */
    public function getAvailabilityStatus(Request $request)
    {
        try {
            $authHeader = $request->header('Authorization');
            if (!$authHeader || !str_contains($authHeader, 'Bearer ')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Authorization token required'
                ], 401);
            }

            $token = str_replace('Bearer ', '', $authHeader);
            $decodedToken = base64_decode($token);
            $tokenParts = explode(':', $decodedToken);

            if (count($tokenParts) < 3) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid token format'
                ], 401);
            }

            $vendorId = $tokenParts[0];
            $vendor = Vendor::find($vendorId);

            if (!$vendor) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vendor not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'vendor' => [
                        'id' => $vendor->id,
                        'name' => $vendor->name,
                        'contact_number' => $vendor->contact_number
                    ],
                    'availability' => [
                        'status' => $vendor->availability_status,
                        'is_available_for_booking' => (bool) $vendor->is_available_for_booking,
                        'is_online' => $vendor->isOnline()
                    ],
                    'location' => [
                        'latitude' => $vendor->current_latitude,
                        'longitude' => $vendor->current_longitude,
                        'address' => $vendor->current_location
                    ],
                    'timing' => [
                        'last_in_time' => $vendor->last_in_time?->format('Y-m-d H:i:s'),
                        'last_out_time' => $vendor->last_out_time?->format('Y-m-d H:i:s')
                    ],
                    'vehicle' => [
                        'registration_number' => $vendor->vehicle_registration_number,
                        'type' => $vendor->vehicle_type,
                        'status' => $vendor->vehicle_status,
                        'listed' => (bool) $vendor->vehicle_listed
                    ],
                    'profile_completed' => (bool) $vendor->is_completed
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Get Availability Status Error', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to get status'
            ], 500);
        }
    }
}
