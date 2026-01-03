<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use App\Models\Vendor;
use App\Models\Material;
use App\Models\TruckType;
use App\Models\PricingRule;
use App\Models\TruckBooking;
use App\Models\VehicleModel;
use Illuminate\Http\Request;
use App\Models\BookingRequest;
use App\Models\VehiclePricing;
use Illuminate\Http\JsonResponse;
use App\Models\TruckSpecification;
use Illuminate\Support\Facades\DB;
use App\Services\GoogleMapsService;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Services\TruckPricingService;
use Illuminate\Support\Facades\Validator;
use App\Services\VendorBookingService; // ✅ ADD THIS
use App\Models\Review;

class TruckBookingController extends Controller
{
    private $googleMapsService;
    private $pricingService;
    private $vendorBookingService; // ✅ ADD THIS

    public function __construct()
    {
        $this->googleMapsService = new GoogleMapsService();
        $this->pricingService = new TruckPricingService();
        $this->vendorBookingService = new VendorBookingService(); // ✅ ADD THIS
    }


    /**
     * ✅ GET FORM DATA
     * GET /api/truck-booking/form-data
     */
    public function getFormData(): JsonResponse
    {
        try {
            // Get materials
            $materials = Material::where('is_active', true)
                ->orderBy('name')
                ->get()
                ->map(function ($material) {
                    return [
                        'id' => $material->id,
                        'name' => $material->name,
                        'description' => $material->description ?? null,
                        'icon' => $material->icon ?? null
                    ];
                });

            // Get vehicle models/categories
            $vehicleModels = VehicleModel::where('is_active', true)
                ->orderBy('model_name')
                ->get()
                ->map(function ($model) {
                    return [
                        'id' => $model->id,
                        'model_name' => $model->model_name,
                        'truck_type_name' => $model->truck_type_name,
                        'body_length' => $model->body_length,
                        'body_height' => $model->body_height,
                        'tyre_count' => $model->tyre_count,
                        'capacity' => $model->carry_capacity_tons . ' tons',
                        'base_price_per_km' => 14
                    ];
                });

            return response()->json([
                'success' => true,
                'message' => 'Form data retrieved successfully',
                'data' => [
                    'materials' => $materials,
                    'vehicle_models' => $vehicleModels,
                    'payment_methods' => [
                        [
                            'value' => 'pickup',
                            'label' => 'Pay at Pickup Location',
                            'description' => 'Pay cash/UPI when driver arrives at pickup'
                        ],
                        [
                            'value' => 'drop',
                            'label' => 'Pay at Drop Location',
                            'description' => 'Pay cash/UPI after delivery at drop location'
                        ]
                    ]
                ]
            ], 200);
        } catch (\Exception $e) {
            Log::error('Get Form Data Error', [
                'error' => $e->getMessage(),
                'line' => $e->getLine()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve form data: ' . $e->getMessage()
            ], 500);
        }
    }



    public function startTrip(Request $request, $bookingId): JsonResponse
    {
        try {
            $booking = TruckBooking::findOrFail($bookingId);

            // Only confirmed/accepted bookings can be started
            if (!in_array($booking->status, ['confirmed', 'accepted'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Booking must be confirmed to start trip',
                    'current_status' => $booking->status
                ], 400);
            }

            $booking->update([
                'status' => 'in_transit',
                'trip_started_at' => now()
            ]);

            Log::info('Trip started', [
                'booking_id' => $booking->booking_id,
                'vendor_id' => $booking->assigned_vendor_id
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Trip started successfully!',
                'data' => [
                    'booking_id' => $booking->booking_id,
                    'status' => 'in_transit',
                    'started_at' => $booking->trip_started_at->format('Y-m-d H:i:s')
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Start Trip Error', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to start trip'
            ], 500);
        }
    }



/**
 * ✅ Create Booking with Auto Vendor Search (Ola/Uber Style)
 */
public function createBookingWithAutoVendorSearch(Request $request): JsonResponse
{
    try {
        // ✅ Token validation
        $authHeader = $request->header('Authorization');
        if (!$authHeader || !str_contains($authHeader, 'Bearer ')) {
            Log::error('Authorization header missing');
            return response()->json([
                'success' => false,
                'message' => 'Authorization token required'
            ], 401);
        }

        $token = str_replace('Bearer ', '', $authHeader);
        $decodedToken = base64_decode($token);
        $tokenParts = explode(':', $decodedToken);

        if (count($tokenParts) < 3) {
            Log::error('Invalid token format', ['token' => $token]);
            return response()->json([
                'success' => false,
                'message' => 'Invalid token format'
            ], 401);
        }

        $userId = $tokenParts[0];
        $user = User::find($userId);

        if (!$user) {
            Log::error('User not found', ['user_id' => $userId]);
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }

        // ✅ IMPROVED VALIDATION
        $validator = Validator::make($request->all(), [
            'vehicle_model_id' => 'required|exists:vehicle_models,id',
            'pickup_address' => 'required|string',
            'pickup_latitude' => 'required|numeric|between:-90,90',
            'pickup_longitude' => 'required|numeric|between:-180,180',
            'drop_address' => 'required|string',
            'drop_latitude' => 'required|numeric|between:-90,90',
            'drop_longitude' => 'required|numeric|between:-180,180',
            'material_id' => 'required|exists:materials,id',
            'material_weight' => 'required|numeric|min:0.1',
            'distance_km' => 'required|numeric|min:0.1',
            'estimated_price' => 'required|numeric|min:1',
            'adjusted_price' => 'nullable|numeric|min:1',
            'payment_method' => 'required|in:pickup,drop',
            // ✅ FIXED: Accept datetime within 5 minutes tolerance
            'pickup_datetime' => [
                'nullable',
                'date',
                function ($attribute, $value, $fail) {
                    if ($value && \Carbon\Carbon::parse($value)->isPast() &&
                        \Carbon\Carbon::parse($value)->diffInMinutes(now(), false) > 5) {
                        $fail('The pickup datetime must be in the future or within 5 minutes of now.');
                    }
                },
            ],
            'special_instructions' => 'nullable|string|max:1000'
        ]);

        if ($validator->fails()) {
            Log::error('Validation failed', ['errors' => $validator->errors()->all()]);
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 400);
        }

        DB::beginTransaction();
        try {
            $material = Material::findOrFail($request->material_id);
            $vehicleModel = VehicleModel::findOrFail($request->vehicle_model_id);

            // ✅ PROPER COORDINATE CONVERSION
            $pickupLat = floatval($request->pickup_latitude);
            $pickupLng = floatval($request->pickup_longitude);
            $dropLat = floatval($request->drop_latitude);
            $dropLng = floatval($request->drop_longitude);

            // ✅ CALCULATE ACCURATE DISTANCE
            $aerialDistance = $this->calculateDistance($pickupLat, $pickupLng, $dropLat, $dropLng);
            $calculatedDistance = $aerialDistance * 1.18;

            // Compare with provided distance
            $providedDistance = floatval($request->distance_km);
            if (abs($providedDistance - $calculatedDistance) > ($calculatedDistance * 0.5)) {
                Log::warning('Distance mismatch detected', [
                    'provided' => $providedDistance,
                    'calculated' => round($calculatedDistance, 2),
                    'difference_percent' => round((($providedDistance - $calculatedDistance) / $calculatedDistance) * 100, 2)
                ]);
            }

            // Use calculated distance for accuracy
            $finalDistance = $calculatedDistance;

            $finalPrice = $request->adjusted_price ?? $request->estimated_price;
            $truckTypeId = $vehicleModel->truck_type_id ?? null;
            $truckTypeName = $vehicleModel->truck_type_name ?? $vehicleModel->model_name;
            $truckLength = $vehicleModel->body_length ?? null;
            $truckHeight = $vehicleModel->body_height ?? null;
            $tyreCount = $vehicleModel->tyre_count ?? 6;

            // ✅ HANDLE PICKUP DATETIME
            $pickupDatetime = $request->pickup_datetime
                ? \Carbon\Carbon::parse($request->pickup_datetime)
                : now()->addHours(2);

            // ✅ CREATE BOOKING WITH VALIDATED DATA
            $booking = TruckBooking::create([
                'user_id' => $userId,
                'vehicle_model_id' => $request->vehicle_model_id,
                'truck_type_id' => $truckTypeId,
                'truck_type_name' => $truckTypeName,
                'truck_specification_id' => null,
                'truck_length' => $truckLength,
                'tyre_count' => $tyreCount,
                'truck_height' => $truckHeight,

                // ✅ VALIDATED COORDINATES
                'pickup_address' => $request->pickup_address,
                'pickup_latitude' => $pickupLat,
                'pickup_longitude' => $pickupLng,
                'drop_address' => $request->drop_address,
                'drop_latitude' => $dropLat,
                'drop_longitude' => $dropLng,

                'material_id' => $material->id,
                'material_name' => $material->name,
                'material_weight' => $request->material_weight,

                // ✅ ACCURATE CALCULATED DISTANCE
                'distance_km' => round($finalDistance, 2),

                'pickup_datetime' => $pickupDatetime,
                'special_instructions' => $request->special_instructions,
                'price_per_km' => round($finalPrice / $finalDistance, 2),
                'estimated_price' => $request->estimated_price,
                'adjusted_price' => $request->adjusted_price,
                'final_amount' => $finalPrice,
                'final_price' => $finalPrice,
                'payment_method' => $request->payment_method,
                'payment_status' => 'pending',
                'status' => 'searching_vendor'
            ]);

            Log::info('Booking created successfully', [
                'booking_id' => $booking->booking_id,
                'user_id' => $userId,
                'pickup_coords' => "{$pickupLat},{$pickupLng}",
                'drop_coords' => "{$dropLat},{$dropLng}",
                'distance_aerial' => round($aerialDistance, 2),
                'distance_truck' => round($calculatedDistance, 2),
                'distance_provided' => $providedDistance,
                'distance_used' => round($finalDistance, 2)
            ]);

            // ✅ Vendor request creation
            $vendorResult = app(\App\Services\VendorBookingService::class)->sendBookingRequest($booking->id);

            if ($vendorResult['success']) {
                Log::info('Booking request sent to vendor', [
                    'booking_id' => $booking->booking_id,
                    'vendor_id' => $vendorResult['vendor']->id ?? 'N/A',
                    'vendor_name' => $vendorResult['vendor']->name ?? 'N/A',
                    'booking_request_id' => $vendorResult['request']->id ?? 'N/A'
                ]);
            } else {
                Log::warning('No vendor available', [
                    'booking_id' => $booking->booking_id,
                    'message' => $vendorResult['message']
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Booking created! Searching for drivers...',
                'data' => [
                    'booking' => [
                        'id' => $booking->id,
                        'booking_id' => $booking->booking_id,
                        'status' => $booking->status,
                        'status_message' => $vendorResult['success'] ? 'Looking for nearby drivers...' : 'No vendor available',

                        // ✅ LOCATION WITH COORDINATES
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

                        // ✅ DISTANCE INFO
                        'distance' => [
                            'aerial_km' => round($aerialDistance, 2),
                            'truck_route_km' => round($calculatedDistance, 2),
                            'display' => round($finalDistance, 2) . ' km'
                        ],

                        'material_name' => $booking->material_name,
                        'material_weight' => $booking->material_weight . ' tons',
                        'vehicle_model' => $vehicleModel->model_name,
                        'truck_type' => $truckTypeName,
                        'truck_length' => $truckLength ? $truckLength . ' ft' : 'N/A',
                        'tyre_count' => $tyreCount . ' Tyre',

                        'pricing' => [
                            'estimated_price' => (float) $booking->estimated_price,
                            'adjusted_price' => $booking->adjusted_price ? (float) $booking->adjusted_price : null,
                            'final_amount' => (float) $booking->final_amount,
                            'price_per_km' => (float) $booking->price_per_km,
                            'is_price_adjusted' => !is_null($booking->adjusted_price),
                            'currency' => 'INR'
                        ],

                        'payment' => [
                            'method' => $booking->payment_method,
                            'status' => $booking->payment_status,
                            'pay_at' => $booking->payment_method === 'pickup' ? 'Pickup Location' : 'Drop Location'
                        ],

                        'pickup_datetime' => $booking->pickup_datetime->format('Y-m-d H:i:s'),
                        'pickup_datetime_formatted' => $booking->pickup_datetime->format('d M Y, h:i A'),
                        'created_at' => $booking->created_at->format('Y-m-d H:i:s'),
                        'special_instructions' => $booking->special_instructions,
                        'vendor_search_status' => $vendorResult['success'] ? 'Vendor found' : 'Searching for vendors...',
                        'booking_request_id' => $vendorResult['request']->id ?? null
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Booking creation failed', [
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Booking creation failed: ' . $e->getMessage(),
                'error_details' => config('app.debug') ? [
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ] : null
            ], 500);
        }
    } catch (\Exception $e) {
        Log::error('Controller fatal error', [
            'error' => $e->getMessage(),
            'line' => $e->getLine(),
            'file' => $e->getFile()
        ]);
        return response()->json([
            'success' => false,
            'message' => 'Booking creation failed: ' . $e->getMessage(),
        ], 500);
    }
}



    // ✅ 1. GET VEHICLE MODELS/CATEGORIES (From vehicle_models table)
    public function getVehicleCategories(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'material_weight' => 'nullable|numeric|min:0.1|max:50'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 400);
            }

            $materialWeight = $request->material_weight ?? 1.0;

            // Get vehicle models/categories
            $vehicleModels = VehicleModel::where('is_active', true)
                ->orderBy('display_order')
                ->get()
                ->map(function ($model) use ($materialWeight) {
                    // Calculate base price
                    $basePricePerKm = 10 + ($model->display_order * 2);
                    $estimatedBasePrice = $basePricePerKm * 10; // For 10km base

                    return [
                        'id' => $model->id,
                        'category_id' => $model->category_id,
                        'name' => $model->model_name,
                        'description' => $model->vehicle_type_desc,
                        'body_length' => $model->body_length . ' ft',
                        'body_width' => $model->body_width . ' ft',
                        'body_height' => $model->body_height . ' ft',
                        'carry_capacity_kgs' => $model->carry_capacity_kgs . ' kg',
                        'carry_capacity_tons' => $model->carry_capacity_tons . ' tons',
                        'suitable_for' => 'Up to ' . $model->carry_capacity_tons . ' tons',
                        'estimated_time' => '10-15 mins',
                        'base_price' => $estimatedBasePrice,
                        'is_suitable' => $materialWeight <= $model->carry_capacity_tons
                    ];
                });

            return response()->json([
                'success' => true,
                'message' => 'Vehicle categories retrieved successfully',
                'data' => [
                    'categories' => $vehicleModels,
                    'total_categories' => $vehicleModels->count(),
                    'material_weight' => $materialWeight
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Get Vehicle Categories Error', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve categories: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getAvailableVendors(Request $request)
{
    try {
        // 1️⃣ Validation
        $validator = Validator::make($request->all(), [
            'pickup_latitude' => 'required|numeric|between:-90,90',
            'pickup_longitude' => 'required|numeric|between:-180,180',
            'vehicle_model_id' => 'required|exists:vehicle_models,id',
            'material_weight' => 'required|numeric|min:0.1|max:50',
            'vehicle_length' => 'nullable|numeric',
            'tyre_count' => 'nullable|integer'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 400);
        }

        $pickupLat = $request->pickup_latitude;
        $pickupLon = $request->pickup_longitude;
        $materialWeight = $request->material_weight;
        $requestedLength = $request->vehicle_length;
        $requestedTyres = $request->tyre_count;

        // 2️⃣ Fetch vendors
        $vendors = DB::table('vendors')
            ->where('vehicle_listed', true)
            ->where('vehicle_status', 'approved')
            ->where('availability_status', 'in')
            ->get();

        // 3️⃣ Filter vendors with location
        $vendors = $vendors->filter(function($vendor) {
            return $vendor->current_latitude && $vendor->current_longitude;
        });

        // 4️⃣ Calculate distance
        $vendors = $vendors->map(function($vendor) use ($pickupLat, $pickupLon) {
            $vendor->distance_km = $this->calculateDistance(
                $pickupLat, $pickupLon,
                $vendor->current_latitude, $vendor->current_longitude
            );
            $vendor->estimated_arrival_mins = ceil($vendor->distance_km / 30 * 60);
            return $vendor;
        });

        // 5️⃣ Apply filtering
        $vendors = $vendors->filter(function($vendor) use ($materialWeight, $requestedLength, $requestedTyres) {
            if ($vendor->distance_km > 30) return false;
            if ($vendor->weight_capacity && $materialWeight > $vendor->weight_capacity) return false;
            if ($requestedLength && $vendor->vehicle_length < $requestedLength * 0.8) return false;
            if ($requestedTyres && $vendor->vehicle_tyre_count < $requestedTyres) return false;
            return true;
        })->sortBy('distance_km')->values();

        // 6️⃣ Prepare final response
        $formattedVendors = $vendors->map(function($vendor) {

            // Fetch all reviews for this vendor (without relationship)
            $reviews = DB::table('reviews')
                ->where('vendor_id', $vendor->id)
                ->join('users', 'reviews.user_id', '=', 'users.id')
                ->select(
                    'reviews.id',
                    'reviews.user_id',
                    'users.name',
                    'users.email',
                    'users.contact_number',
                    'reviews.rating',
                    'reviews.comment',
                    'reviews.created_at'
                )
                ->orderBy('reviews.created_at', 'desc')
                ->get();

            // Compute average, min, max, total
            $ratings = $reviews->pluck('rating');
            $averageRating = $ratings->avg();
            $minRating = $ratings->min();
            $maxRating = $ratings->max();
            $totalReviews = $ratings->count();

            // Pricing example (replace with your PricingRule logic)
            $pricing = PricingRule::calculatePrice($vendor->distance_km, 1); // assuming 1 ton for demo

            return [
                'vendor_id' => $vendor->id,
                'vendor_name' => $vendor->name,
                'vehicle_number' => $vendor->vehicle_registration_number,
                'vehicle_type' => $vendor->vehicle_type,
                'vehicle_brand_model' => $vendor->vehicle_brand_model,
                'vehicle_length' => number_format($vendor->vehicle_length, 2) . ' ft',
                'tyre_count' => $vendor->vehicle_tyre_count . ' Tyre',
                'weight_capacity' => number_format($vendor->weight_capacity, 2) . ' tons',
                'distance_km' => round($vendor->distance_km,2),
                'estimated_arrival' => $vendor->estimated_arrival_mins . ' mins',
                'current_location' => $vendor->current_location,
                'contact_number' => $vendor->contact_number,
                'is_verified' => $vendor->rc_verified && $vendor->dl_verified,
                'availability_status' => 'in',
                'vehicle_image' => $vendor->vehicle_image ? url('storage/'.$vendor->vehicle_image) : null,
                'pricing' => $pricing,
               
                // Reviews
                'average_rating' => $averageRating ? round($averageRating,1) : null,
                'min_rating' => $minRating,
                'max_rating' => $maxRating,
                'total_reviews' => $totalReviews,
                'reviews' => $reviews
            ];
        });

        return response()->json([
            'success' => true,
            'message' => count($formattedVendors) . ' vehicles found within 30km',
            'data' => [
                'vehicles' => $formattedVendors,
                'total_available' => count($formattedVendors),
                'search_radius_km' => 30,
                'filters' => [
                    'status' => 'in',
                    'max_distance' => '30 km'
                ]
            ]
        ]);

    } catch (\Exception $e) {
        Log::error('GetAvailableVendors Error', [
            'message' => $e->getMessage(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ]);
        return response()->json([
            'success' => false,
            'message' => 'Failed to fetch vendors: ' . $e->getMessage()
        ], 500);
    }
}


    public function calculateVendorPrice(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'vendor_id' => 'required|exists:vendors,id',
                'pickup_latitude' => 'required|numeric',
                'pickup_longitude' => 'required|numeric',
                'drop_latitude' => 'required|numeric',
                'drop_longitude' => 'required|numeric',
                'material_id' => 'required|exists:materials,id',
                'material_weight' => 'required|numeric|min:0.1'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 400);
            }

            // Calculate trip distance
            $distanceResult = $this->googleMapsService->calculateDistance(
                $request->pickup_latitude,
                $request->pickup_longitude,
                $request->drop_latitude,
                $request->drop_longitude
            );

            if (!$distanceResult['success']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unable to calculate distance'
                ], 400);
            }

            $vendor = Vendor::with('vehicleModel')->findOrFail($request->vendor_id);
            $material = Material::findOrFail($request->material_id);
            $distanceKm = $distanceResult['distance_km'];
            $materialWeight = $request->material_weight;

            // ✅ Calculate price using PricingRule table
            $pricing = PricingRule::calculatePrice($distanceKm, $materialWeight);

            return response()->json([
                'success' => true,
                'message' => 'Price calculated successfully',
                'data' => [
                    'vendor' => [
                        'id' => $vendor->id,
                        'name' => $vendor->name,
                        'vehicle_number' => $vendor->vehicle_registration_number,
                        'vehicle_model' => $vendor->vehicleModel->model_name ?? 'N/A',
                        'contact' => $vendor->contact_number,
                        'vehicle_image' => $vendor->vehicle_image ? url('storage/' . $vendor->vehicle_image) : null
                    ],
                    'trip_details' => [
                        'distance_km' => $distanceKm,
                        'distance_text' => $distanceResult['distance_text'],
                        'duration_text' => $distanceResult['duration_text'],
                        'material_name' => $material->name,
                        'material_weight' => $materialWeight . ' tons'
                    ],
                    'pricing' => [
                        'pricing_tier' => $pricing['pricing_tier'],
                        'base_price' => $pricing['base_price'],
                        'distance_charge' => $pricing['distance_charge'],
                        'weight_surcharge' => $pricing['weight_surcharge'],
                        'total_price' => $pricing['total_price'],
                        'price_per_km' => $pricing['price_per_km'],
                        'currency' => 'INR',
                        'is_editable' => true
                    ],
                    'locations' => [
                        'pickup' => [
                            'latitude' => $request->pickup_latitude,
                            'longitude' => $request->pickup_longitude
                        ],
                        'drop' => [
                            'latitude' => $request->drop_latitude,
                            'longitude' => $request->drop_longitude
                        ]
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Calculate Vendor Price Error', [
                'error' => $e->getMessage(),
                'line' => $e->getLine()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Price calculation failed: ' . $e->getMessage()
            ], 500);
        }
    }


    public function createBookingWithVendor(Request $request): JsonResponse
    {
        try {
            // ✅ Token validation
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

            $userId = $tokenParts[0];
            $user = User::find($userId);

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found'
                ], 404);
            }

            // ✅ FIXED VALIDATION - Simple date validation (no strict future check)
            $validator = Validator::make($request->all(), [
                'vendor_id' => 'required|exists:vendors,id',
                'vehicle_model_id' => 'required|exists:vehicle_models,id',
                'truck_length' => 'nullable|numeric|min:5|max:40',
                'tyre_count' => 'nullable|integer|in:4,5,6,10,12,14',
                'truck_height' => 'nullable|numeric|min:4|max:15',
                'pickup_address' => 'required|string',
                'pickup_latitude' => 'required|numeric|between:-90,90',
                'pickup_longitude' => 'required|numeric|between:-180,180',
                'drop_address' => 'required|string',
                'drop_latitude' => 'required|numeric|between:-90,90',
                'drop_longitude' => 'required|numeric|between:-180,180',
                'material_id' => 'required|exists:materials,id',
                'material_weight' => 'required|numeric|min:0.1',
                'distance_km' => 'required|numeric|min:0.1',
                'estimated_price' => 'required|numeric|min:1',
                'adjusted_price' => 'nullable|numeric|min:1',
                'payment_method' => 'required|in:pickup,drop',
                'pickup_datetime' => 'nullable|date', // ✅ SIMPLE - No strict future check
                'special_instructions' => 'nullable|string|max:1000'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 400);
            }

            DB::beginTransaction();

            try {
                $vendor = Vendor::findOrFail($request->vendor_id);
                $material = Material::findOrFail($request->material_id);
                $vehicleModel = VehicleModel::findOrFail($request->vehicle_model_id);

                // ✅ PROPER COORDINATE CONVERSION
                $pickupLat = floatval($request->pickup_latitude);
                $pickupLng = floatval($request->pickup_longitude);
                $dropLat = floatval($request->drop_latitude);
                $dropLng = floatval($request->drop_longitude);

                // ✅ CALCULATE ACCURATE DISTANCE
                $aerialDistance = $this->calculateDistance($pickupLat, $pickupLng, $dropLat, $dropLng);
                $calculatedDistance = $aerialDistance * 1.18;

                // Compare with provided distance
                $providedDistance = floatval($request->distance_km);
                if (abs($providedDistance - $calculatedDistance) > ($calculatedDistance * 0.5)) {
                    Log::warning('Distance mismatch detected', [
                        'provided' => $providedDistance,
                        'calculated' => round($calculatedDistance, 2)
                    ]);
                }

                // Use calculated distance
                $finalDistance = $calculatedDistance;

                $finalPrice = $request->adjusted_price ?? $request->estimated_price;
                $pricePerKm = round($finalPrice / $finalDistance, 2);

                // ✅ HANDLE PICKUP DATETIME - Accept any valid datetime
                $pickupDatetime = $request->pickup_datetime
                    ? \Carbon\Carbon::parse($request->pickup_datetime)
                    : now()->addHours(2);

                // ✅ CREATE BOOKING WITH VALIDATED DATA
                $booking = TruckBooking::create([
                    'user_id' => $userId,
                    'assigned_vendor_id' => $vendor->id,
                    'vendor_name' => $vendor->name,
                    'vendor_vehicle_number' => $vendor->vehicle_registration_number,
                    'vendor_contact' => $vendor->contact_number,
                    'vehicle_model_id' => $vehicleModel->id,
                    'truck_type_id' => $vehicleModel->truck_type_id,
                    'truck_type_name' => $vehicleModel->truck_type_name ?? 'Truck',
                    'truck_length' => $request->truck_length ?? $vendor->vehicle_length ?? 14,
                    'tyre_count' => $request->tyre_count ?? $vendor->vehicle_tyre_count ?? 6,
                    'truck_height' => $request->truck_height ?? $vendor->vehicle_height ?? 6,

                    // ✅ VALIDATED COORDINATES
                    'pickup_address' => $request->pickup_address,
                    'pickup_latitude' => $pickupLat,
                    'pickup_longitude' => $pickupLng,
                    'drop_address' => $request->drop_address,
                    'drop_latitude' => $dropLat,
                    'drop_longitude' => $dropLng,

                    'material_id' => $material->id,
                    'material_name' => $material->name,
                    'material_weight' => $request->material_weight,

                    // ✅ ACCURATE CALCULATED DISTANCE
                    'distance_km' => round($finalDistance, 2),

                    'pickup_datetime' => $pickupDatetime,
                    'special_instructions' => $request->special_instructions,
                    'price_per_km' => $pricePerKm,
                    'estimated_price' => $request->estimated_price,
                    'adjusted_price' => $request->adjusted_price,
                    'final_amount' => $finalPrice,
                    'final_price' => $finalPrice,
                    'payment_method' => $request->payment_method,
                    'payment_status' => 'pending',
                    'status' => 'pending'
                ]);

                // ✅ CREATE BOOKING REQUEST
                $bookingRequest = BookingRequest::create([
                    'booking_id' => $booking->id,
                    'vendor_id' => $vendor->id,
                    'status' => 'pending',
                    'sent_at' => now(),
                    'expires_at' => now()->addMinutes(10),
                    'sequence_number' => 1,
                    'pickup_datetime' => $booking->pickup_datetime,
                    'pickup_address' => $booking->pickup_address,
                    'drop_address' => $booking->drop_address,
                    'distance_km' => $booking->distance_km,
                    'final_amount' => $booking->final_amount,
                ]);

                // ✅ UPDATE VENDOR AVAILABILITY
                $vendor->update([
                    'availability_status' => 'requested',
                    'is_available_for_booking' => false
                ]);

                DB::commit();

                Log::info('Booking created with vendor', [
                    'booking_id' => $booking->booking_id,
                    'booking_request_id' => $bookingRequest->id,
                    'vendor_id' => $vendor->id,
                    'pickup_coords' => "{$pickupLat},{$pickupLng}",
                    'drop_coords' => "{$dropLat},{$dropLng}",
                    'distance_aerial' => round($aerialDistance, 2),
                    'distance_truck' => round($calculatedDistance, 2)
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Booking request sent to vendor! Waiting for acceptance.',
                    'data' => [
                        'booking' => [
                            'id' => $booking->id,
                            'booking_id' => $booking->booking_id,
                            'booking_request_id' => $bookingRequest->id,
                            'status' => 'pending',
                            'status_message' => 'Waiting for vendor to accept',

                            // ✅ LOCATION WITH COORDINATES
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

                            // ✅ DISTANCE INFO
                            'distance' => [
                                'aerial_km' => round($aerialDistance, 2),
                                'truck_route_km' => round($calculatedDistance, 2),
                                'display' => round($finalDistance, 2) . ' km'
                            ],

                            'truck_specifications' => [
                                'length' => $booking->truck_length . ' ft',
                                'tyre_count' => $booking->tyre_count . ' Tyre',
                                'height' => $booking->truck_height . ' ft'
                            ],

                            'material' => [
                                'name' => $booking->material_name,
                                'weight' => $booking->material_weight . ' tons',
                                'weight_value' => floatval($booking->material_weight)
                            ],

                            'vehicle_model' => $vehicleModel->model_name,

                            'vendor' => [
                                'id' => $vendor->id,
                                'name' => $booking->vendor_name,
                                'contact' => $booking->vendor_contact,
                                'vehicle_number' => $booking->vendor_vehicle_number,
                                'availability_status' => 'requested'
                            ],

                            'pricing' => [
                                'price_per_km' => floatval($booking->price_per_km),
                                'estimated_price' => floatval($booking->estimated_price),
                                'adjusted_price' => $booking->adjusted_price ? floatval($booking->adjusted_price) : null,
                                'final_amount' => floatval($booking->final_amount),
                                'is_price_adjusted' => !is_null($booking->adjusted_price),
                                'currency' => 'INR',
                                'display' => '₹' . number_format($booking->final_amount, 2)
                            ],

                            'payment' => [
                                'method' => $booking->payment_method,
                                'status' => $booking->payment_status,
                                'pay_at' => $booking->payment_method === 'pickup' ? 'Pickup Location' : 'Drop Location'
                            ],

                            'schedule' => [
                                'pickup_datetime' => $booking->pickup_datetime->format('Y-m-d H:i:s'),
                                'pickup_formatted' => $booking->pickup_datetime->format('d M Y, h:i A'),
                                'expires_at' => $bookingRequest->expires_at->format('Y-m-d H:i:s'),
                                'expires_in_minutes' => 10,
                                'created_at' => $booking->created_at->format('Y-m-d H:i:s')
                            ],

                            'special_instructions' => $booking->special_instructions,
                            'next_action' => 'Wait for vendor to accept your request (expires in 10 minutes)'
                        ]
                    ]
                ], 201);
            } catch (\Exception $e) {
                DB::rollback();
                throw $e;
            }
        } catch (\Exception $e) {
            Log::error('Create Booking With Vendor Error', [
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Booking creation failed: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getUserBookings(Request $request): JsonResponse
    {
        try {
            // ✅ Token validation
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

            $userId = $tokenParts[0];

            // Get all bookings with relationships
            $allBookings = TruckBooking::where('user_id', $userId)
                ->with(['material', 'assignedVendor', 'vehicleModel'])
                ->orderBy('created_at', 'desc')
                ->get();

            // ✅ Categorize bookings
            $pendingBookings = $allBookings->where('status', 'pending');
            $activeBookings = $allBookings->whereIn('status', ['confirmed', 'in_transit', 'arrived', 'loading', 'in_progress']);
            $completedBookings = $allBookings->where('status', 'completed');
            $cancelledBookings = $allBookings->where('status', 'cancelled');

            // ✅ Format booking with FULL DETAILS
            $formatBooking = function ($booking) {
                // Coordinates
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

                // Vendor details
                $vendorDetails = null;
                if ($booking->assignedVendor) {
                    $vendor = $booking->assignedVendor;
                    $vendorDetails = [
                        'id' => $vendor->id,
                        'name' => $vendor->name,
                        'contact_number' => $vendor->contact_number,
                        'email' => $vendor->email ?? 'N/A',
                        'vehicle_registration_number' => $vendor->vehicle_registration_number ?? 'N/A',
                        'vehicle_type' => $vendor->vehicle_type ?? 'N/A'
                    ];
                }

                return [
                    'id' => $booking->id,
                    'booking_id' => $booking->booking_id,
                    'status' => $booking->status,
                    'status_label' => $this->getStatusLabel($booking->status),

                    // Location with coordinates
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

                    // Distance
                    'distance' => [
                        'aerial_km' => round($aerialDistance, 2),
                        'truck_route_km' => round($truckDistance, 2),
                        'display' => round($booking->distance_km ?? $truckDistance, 2) . ' km'
                    ],

                    // Material
                    'material' => [
                        'name' => $booking->material_name,
                        'weight' => $booking->material_weight . ' tons',
                        'weight_value' => floatval($booking->material_weight)
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

                    // Vendor
                    'vendor' => $vendorDetails ?? [
                        'status' => 'no_vendor_assigned',
                        'message' => 'Waiting for vendor'
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
                        'status' => $booking->payment_status
                    ],

                    // Timeline
                    'timeline' => [
                        'pickup_datetime' => $booking->pickup_datetime ? $booking->pickup_datetime->format('Y-m-d H:i:s') : null,
                        'vendor_accepted_at' => $booking->vendor_accepted_at ? $booking->vendor_accepted_at->format('Y-m-d H:i:s') : null,
                        'trip_started_at' => $booking->trip_started_at ? $booking->trip_started_at->format('Y-m-d H:i:s') : null,
                        'trip_completed_at' => $booking->trip_completed_at ? $booking->trip_completed_at->format('Y-m-d H:i:s') : null,
                        'cancelled_at' => $booking->cancelled_at ? $booking->cancelled_at->format('Y-m-d H:i:s') : null,
                        'created_at' => $booking->created_at->format('Y-m-d H:i:s')
                    ],

                    'special_instructions' => $booking->special_instructions
                ];
            };

            return response()->json([
                'success' => true,
                'message' => 'All bookings retrieved successfully',
                'data' => [
                    // ✅ PENDING CATEGORY
                    'pending' => [
                        'count' => $pendingBookings->count(),
                        'bookings' => $pendingBookings->map($formatBooking)->values()
                    ],

                    // ✅ ACTIVE CATEGORY
                    'active' => [
                        'count' => $activeBookings->count(),
                        'bookings' => $activeBookings->map($formatBooking)->values()
                    ],

                    // ✅ COMPLETED CATEGORY
                    'completed' => [
                        'count' => $completedBookings->count(),
                        'bookings' => $completedBookings->map($formatBooking)->values()
                    ],

                    // ✅ CANCELLED CATEGORY
                    'cancelled' => [
                        'count' => $cancelledBookings->count(),
                        'bookings' => $cancelledBookings->map($formatBooking)->values()
                    ],

                    // ✅ SUMMARY
                    'summary' => [
                        'total_bookings' => $allBookings->count(),
                        'pending_count' => $pendingBookings->count(),
                        'active_count' => $activeBookings->count(),
                        'completed_count' => $completedBookings->count(),
                        'cancelled_count' => $cancelledBookings->count(),
                        'total_spent' => floatval($completedBookings->sum('final_amount')),
                        'total_spent_display' => '₹' . number_format($completedBookings->sum('final_amount'), 2)
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Get User Bookings Error', [
                'error' => $e->getMessage(),
                'line' => $e->getLine()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve bookings'
            ], 500);
        }
    }

    /**
     * ✅ Get status label
     */
    private function getStatusLabel($status)
    {
        $labels = [
            'pending' => 'Waiting for Vendor',
            'confirmed' => 'Vendor Confirmed',
            'in_transit' => 'Trip in Progress',
            'arrived' => 'Vendor Arrived',
            'loading' => 'Loading',
            'in_progress' => 'On the Way',
            'completed' => 'Trip Completed',
            'cancelled' => 'Booking Cancelled'
        ];

        return $labels[$status] ?? ucfirst(str_replace('_', ' ', $status));
    }

    // // ✅ Helper method for status labels
    // private function getStatusLabel($status)
    // {
    //     $labels = [
    //         'pending' => 'Waiting for Vendor',
    //         'confirmed' => 'Confirmed',
    //         'in_transit' => 'On the Way',
    //         'arrived' => 'Arrived',
    //         'loading' => 'Loading',
    //         'in_progress' => 'In Progress',
    //         'completed' => 'Completed',
    //         'cancelled' => 'Cancelled'
    //     ];

    //     return $labels[$status] ?? ucfirst($status);
    // }



    public function getActiveBookings(Request $request): JsonResponse
    {
        try {
            // ✅ Token validation
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

            $userId = $tokenParts[0];

            // Get active bookings (pending, confirmed, in_transit)
            $bookings = TruckBooking::where('user_id', $userId)
                ->whereIn('status', ['pending', 'confirmed', 'in_transit'])
                ->with(['material', 'assignedVendor', 'vehicleModel'])
                ->orderBy('created_at', 'desc')
                ->paginate(10);

            // Format bookings with full details
            $formattedBookings = $bookings->map(function ($booking) {
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

                // ✅ STATUS LABELS
                $statusLabels = [
                    'pending' => 'Waiting for Vendor',
                    'confirmed' => 'Vendor Confirmed',
                    'in_transit' => 'Trip in Progress'
                ];

                // ✅ VENDOR DETAILS
                $vendorDetails = null;
                if ($booking->assignedVendor) {
                    $vendor = $booking->assignedVendor;
                    $vendorDetails = [
                        'id' => $vendor->id,
                        'name' => $vendor->name,
                        'contact_number' => $vendor->contact_number,
                        'email' => $vendor->email ?? 'N/A',
                        'city' => $vendor->city ?? 'N/A',
                        'state' => $vendor->state ?? 'N/A',
                        'vehicle_registration_number' => $vendor->vehicle_registration_number ?? 'N/A',
                        'vehicle_type' => $vendor->vehicle_type ?? 'N/A',
                        'current_location' => $vendor->current_location ?? 'Location not available',
                        'current_latitude' => $vendor->current_latitude ?? null,
                        'current_longitude' => $vendor->current_longitude ?? null
                    ];
                }

                // ✅ TRACKING INFO (for in_transit)
                $trackingInfo = null;
                if ($booking->status === 'in_transit') {
                    $trackingInfo = [
                        'distance_covered_km' => $booking->distance_covered_km ?? 0,
                        'distance_remaining_km' => $booking->distance_remaining_km ?? $booking->distance_km,
                        'estimated_time_remaining_mins' => $booking->estimated_time_remaining_mins ?? null,
                        'current_speed_kmph' => $booking->current_speed_kmph ?? null,
                        'estimated_arrival_time' => $booking->estimated_arrival_time ? $booking->estimated_arrival_time->format('Y-m-d H:i:s') : null,
                        'progress_percentage' => $booking->distance_km > 0
                            ? round(($booking->distance_covered_km / $booking->distance_km) * 100, 2)
                            : 0
                    ];
                }

                return [
                    'id' => $booking->id,
                    'booking_id' => $booking->booking_id,
                    'status' => $booking->status,
                    'status_label' => $statusLabels[$booking->status] ?? ucfirst($booking->status),

                    // ✅ LOCATION DETAILS WITH COORDINATES
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

                    // ✅ DISTANCE INFO
                    'distance' => [
                        'aerial_km' => round($aerialDistance, 2),
                        'truck_route_km' => round($truckDistance, 2),
                        'actual_km' => round($booking->distance_km ?? $truckDistance, 2),
                        'display' => round($booking->distance_km ?? $truckDistance, 2) . ' km',
                        'google_maps_url' => "https://www.google.com/maps/dir/?api=1&origin={$pickupLat},{$pickupLng}&destination={$dropLat},{$dropLng}&travelmode=driving"
                    ],

                    // ✅ MATERIAL DETAILS
                    'material' => [
                        'name' => $booking->material_name,
                        'weight' => $booking->material_weight . ' tons',
                        'weight_value' => floatval($booking->material_weight)
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

                    // ✅ VENDOR DETAILS
                    'vendor' => $vendorDetails ?? [
                        'status' => 'no_vendor_assigned',
                        'message' => 'Searching for vendors...'
                    ],

                    // ✅ PRICING
                    'pricing' => [
                        'estimated_price' => floatval($booking->estimated_price),
                        'adjusted_price' => $booking->adjusted_price ? floatval($booking->adjusted_price) : null,
                        'final_amount' => floatval($booking->final_amount),
                        'currency' => 'INR',
                        'display' => '₹' . number_format($booking->final_amount, 2)
                    ],

                    // ✅ PAYMENT
                    'payment' => [
                        'method' => $booking->payment_method,
                        'status' => $booking->payment_status
                    ],

                    // ✅ TRACKING (for in_transit status)
                    'tracking' => $trackingInfo,

                    // ✅ SCHEDULE
                    'schedule' => [
                        'pickup_datetime' => $booking->pickup_datetime ? $booking->pickup_datetime->format('Y-m-d H:i:s') : null,
                        'pickup_formatted' => $booking->pickup_datetime ? $booking->pickup_datetime->format('d M Y, h:i A') : null,
                        'vendor_accepted_at' => $booking->vendor_accepted_at ? $booking->vendor_accepted_at->format('Y-m-d H:i:s') : null,
                        'trip_started_at' => $booking->trip_started_at ? $booking->trip_started_at->format('Y-m-d H:i:s') : null,
                        'created_at' => $booking->created_at->format('Y-m-d H:i:s'),
                        'created_formatted' => $booking->created_at->format('d M Y, h:i A')
                    ],

                    // ✅ ACTIONS
                    'actions' => [
                        'can_cancel' => in_array($booking->status, ['pending', 'confirmed']),
                        'can_track' => $booking->status === 'in_transit',
                        'can_contact_vendor' => !is_null($booking->assignedVendor)
                    ],

                    'special_instructions' => $booking->special_instructions
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
            Log::error('Get Active Bookings Error', [
                'error' => $e->getMessage(),
                'line' => $e->getLine()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve active bookings'
            ], 500);
        }
    }


    public function getCompletedBookings(Request $request): JsonResponse
    {
        try {
            // ✅ Token validation
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

            $userId = $tokenParts[0];

            // Get completed bookings with relationships
            $bookings = TruckBooking::where('user_id', $userId)
                ->where('status', 'completed')
                ->with(['material', 'assignedVendor', 'vehicleModel'])
                ->orderBy('trip_completed_at', 'desc')
                ->paginate(10);

            // Format bookings with full details
            $formattedBookings = $bookings->map(function ($booking) {
                $ratingExists = \DB::table('reviews')
                ->where('user_id', $booking->user_id)
                ->where('vendor_id', $booking->assigned_vendor_id)
                ->where('booking_id', $booking->id)
                ->exists();
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

                // ✅ VENDOR DETAILS
                $vendorDetails = null;
                if ($booking->assignedVendor) {
                    $vendor = $booking->assignedVendor;
                    $vendorDetails = [
                        'id' => $vendor->id,
                        'name' => $vendor->name,
                        'contact_number' => $vendor->contact_number,
                        'email' => $vendor->email ?? 'N/A',
                        'city' => $vendor->city ?? 'N/A',
                        'state' => $vendor->state ?? 'N/A',
                        'vehicle_registration_number' => $vendor->vehicle_registration_number ?? 'N/A',
                        'vehicle_type' => $vendor->vehicle_type ?? 'N/A'
                    ];
                }

                // ✅ CALCULATE TRIP DURATION
                $tripDuration = null;
                if ($booking->trip_started_at && $booking->trip_completed_at) {
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
                    'status' => 'completed',
                    'status_label' => 'Trip Completed Successfully',
                    
                     // ⭐ ADD THIS ⭐
                'rating_is_available' => $ratingExists ? 1 : 0,

                    // ✅ LOCATION DETAILS WITH COORDINATES
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

                    // ✅ DISTANCE INFO
                    'distance' => [
                        'aerial_km' => round($aerialDistance, 2),
                        'truck_route_km' => round($truckDistance, 2),
                        'actual_km' => round($booking->distance_km ?? $truckDistance, 2),
                        'display' => round($booking->distance_km ?? $truckDistance, 2) . ' km',
                        'google_maps_url' => "https://www.google.com/maps/dir/?api=1&origin={$pickupLat},{$pickupLng}&destination={$dropLat},{$dropLng}&travelmode=driving"
                    ],

                    // ✅ MATERIAL DETAILS
                    'material' => [
                        'name' => $booking->material_name,
                        'weight' => $booking->material_weight . ' tons',
                        'weight_value' => floatval($booking->material_weight)
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

                    // ✅ VENDOR DETAILS
                    'vendor' => $vendorDetails ?? [
                        'name' => $booking->vendor_name ?? 'N/A',
                        'contact' => $booking->vendor_contact ?? 'N/A',
                        'vehicle_number' => $booking->vendor_vehicle_number ?? 'N/A'
                    ],

                    // ✅ PRICING
                    'pricing' => [
                        'estimated_price' => floatval($booking->estimated_price),
                        'adjusted_price' => $booking->adjusted_price ? floatval($booking->adjusted_price) : null,
                        'final_amount' => floatval($booking->final_amount),
                        'currency' => 'INR',
                        'display' => '₹' . number_format($booking->final_amount, 2)
                    ],

                    // ✅ PAYMENT INFO
                    'payment' => [
                        'method' => $booking->payment_method,
                        'status' => $booking->payment_status,
                        'completed_at' => $booking->payment_completed_at ? $booking->payment_completed_at->format('Y-m-d H:i:s') : null,
                        'completed_formatted' => $booking->payment_completed_at ? $booking->payment_completed_at->format('d M Y, h:i A') : null
                    ],

                    // ✅ COMPLETE TIMELINE
                    'timeline' => [
                        // Booking created
                        'booking_created' => [
                            'timestamp' => $booking->created_at->format('Y-m-d H:i:s'),
                            'formatted' => $booking->created_at->format('d M Y, h:i A'),
                            'label' => 'Booking Created'
                        ],

                        // Vendor accepted
                        'vendor_accepted' => [
                            'timestamp' => $booking->vendor_accepted_at ? $booking->vendor_accepted_at->format('Y-m-d H:i:s') : null,
                            'formatted' => $booking->vendor_accepted_at ? $booking->vendor_accepted_at->format('d M Y, h:i A') : null,
                            'label' => 'Vendor Accepted'
                        ],

                        // Pickup scheduled
                        'pickup_scheduled' => [
                            'timestamp' => $booking->pickup_datetime ? $booking->pickup_datetime->format('Y-m-d H:i:s') : null,
                            'formatted' => $booking->pickup_datetime ? $booking->pickup_datetime->format('d M Y, h:i A') : null,
                            'label' => 'Scheduled Pickup'
                        ],

                        // Trip started
                        'trip_started' => [
                            'timestamp' => $booking->trip_started_at ? $booking->trip_started_at->format('Y-m-d H:i:s') : null,
                            'formatted' => $booking->trip_started_at ? $booking->trip_started_at->format('d M Y, h:i A') : null,
                            'label' => 'Trip Started'
                        ],

                        // Trip completed
                        'trip_completed' => [
                            'timestamp' => $booking->trip_completed_at ? $booking->trip_completed_at->format('Y-m-d H:i:s') : null,
                            'formatted' => $booking->trip_completed_at ? $booking->trip_completed_at->format('d M Y, h:i A') : null,
                            'label' => 'Trip Completed',
                            'time_ago' => $booking->trip_completed_at ? $booking->trip_completed_at->diffForHumans() : null
                        ],

                        // Trip duration
                        'duration' => $tripDuration
                    ],

                    'special_instructions' => $booking->special_instructions
                ];
            });

            // ✅ CALCULATE STATS
            $totalAmountPaid = TruckBooking::where('user_id', $userId)
                ->where('status', 'completed')
                ->sum('final_amount');

            return response()->json([
                'success' => true,
                'message' => $bookings->total() . ' completed booking(s) found',
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
                        'total_completed' => $bookings->total(),
                        'total_amount_paid' => floatval($totalAmountPaid),
                        'total_amount_display' => '₹' . number_format($totalAmountPaid, 2)
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Get Completed Bookings Error', [
                'error' => $e->getMessage(),
                'line' => $e->getLine()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve completed bookings'
            ], 500);
        }
    }



    public function completeTrip(Request $request, $bookingId): JsonResponse
    {
        try {
            $booking = TruckBooking::findOrFail($bookingId);

            // Can complete from multiple statuses
            if (!in_array($booking->status, ['in_transit', 'confirmed', 'accepted'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Booking cannot be completed from current status',
                    'current_status' => $booking->status
                ], 400);
            }

            $booking->update([
                'status' => 'completed',
                'trip_completed_at' => now(),
                'trip_started_at' => $booking->trip_started_at ?? now() // Set if not already set
            ]);

            // Update vendor availability back to online
            if ($booking->assignedVendor) {
                $booking->assignedVendor->update([
                    'availability_status' => 'in',
                    'is_available_for_booking' => true
                ]);
            }

            Log::info('Trip completed', [
                'booking_id' => $booking->booking_id,
                'previous_status' => $booking->getOriginal('status')
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Trip completed successfully!',
                'data' => [
                    'booking_id' => $booking->booking_id,
                    'status' => 'completed',
                    'completed_at' => $booking->trip_completed_at->format('Y-m-d H:i:s')
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Complete Trip Error', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to complete trip'
            ], 500);
        }
    }


    public function getCancelledBookings(Request $request): JsonResponse
    {
        try {
            // ✅ Token validation
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

            $userId = $tokenParts[0];

            // Get cancelled bookings with relationships
            $bookings = TruckBooking::where('user_id', $userId)
                ->where('status', 'cancelled')
                ->with(['material', 'assignedVendor', 'vehicleModel'])
                ->orderBy('cancelled_at', 'desc')
                ->paginate(10);

            // Format bookings with full details
            $formattedBookings = $bookings->map(function ($booking) {
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

                // ✅ VENDOR DETAILS
                $vendorDetails = null;
                if ($booking->assignedVendor) {
                    $vendor = $booking->assignedVendor;
                    $vendorDetails = [
                        'id' => $vendor->id,
                        'name' => $vendor->name,
                        'contact_number' => $vendor->contact_number,
                        'email' => $vendor->email ?? 'N/A',
                        'city' => $vendor->city ?? 'N/A',
                        'state' => $vendor->state ?? 'N/A',
                        'vehicle_registration_number' => $vendor->vehicle_registration_number ?? 'N/A',
                        'vehicle_type' => $vendor->vehicle_type ?? 'N/A'
                    ];
                }

                return [
                    'id' => $booking->id,
                    'booking_id' => $booking->booking_id,
                    'status' => 'cancelled',
                    'status_label' => 'Booking Cancelled',

                    // ✅ LOCATION DETAILS WITH COORDINATES
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
                        'weight' => $booking->material_weight . ' tons',
                        'weight_value' => floatval($booking->material_weight)
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

                    // ✅ VENDOR DETAILS
                    'vendor' => $vendorDetails ?? [
                        'status' => 'no_vendor_assigned',
                        'message' => 'No vendor was assigned'
                    ],

                    // ✅ PRICING
                    'pricing' => [
                        'estimated_price' => floatval($booking->estimated_price),
                        'adjusted_price' => $booking->adjusted_price ? floatval($booking->adjusted_price) : null,
                        'final_amount' => floatval($booking->final_amount),
                        'currency' => 'INR',
                        'display' => '₹' . number_format($booking->final_amount, 2)
                    ],

                    // ✅ PAYMENT
                    'payment' => [
                        'method' => $booking->payment_method,
                        'status' => $booking->payment_status
                    ],

                    // ✅ CANCELLATION INFO
                    'cancellation' => [
                        'reason' => $booking->cancellation_reason ?? 'No reason provided',
                        'cancelled_by' => $booking->cancelled_by ?? 'User',
                        'cancelled_at' => $booking->cancelled_at ? $booking->cancelled_at->format('Y-m-d H:i:s') : null,
                        'cancelled_formatted' => $booking->cancelled_at ? $booking->cancelled_at->format('d M Y, h:i A') : null,
                        'time_ago' => $booking->cancelled_at ? $booking->cancelled_at->diffForHumans() : null
                    ],

                    // ✅ SCHEDULE
                    'schedule' => [
                        'pickup_datetime' => $booking->pickup_datetime ? $booking->pickup_datetime->format('Y-m-d H:i:s') : null,
                        'pickup_formatted' => $booking->pickup_datetime ? $booking->pickup_datetime->format('d M Y, h:i A') : null,
                        'created_at' => $booking->created_at->format('Y-m-d H:i:s'),
                        'created_formatted' => $booking->created_at->format('d M Y, h:i A')
                    ],

                    'special_instructions' => $booking->special_instructions
                ];
            });

            return response()->json([
                'success' => true,
                'message' => $bookings->total() . ' cancelled booking(s) found',
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
            Log::error('Get Cancelled Bookings Error', [
                'error' => $e->getMessage(),
                'line' => $e->getLine()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve cancelled bookings'
            ], 500);
        }
    }

    // ✅ 6. UPDATE/ADJUST PRICE
    public function updatePrice(Request $request, $bookingId): JsonResponse
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

            $userId = $tokenParts[0];

            // Validation
            $validator = Validator::make($request->all(), [
                'adjusted_price' => 'required|numeric|min:1'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 400);
            }

            // Find booking
            $booking = TruckBooking::where('id', $bookingId)
                ->where('user_id', $userId)
                ->first();

            if (!$booking) {
                return response()->json([
                    'success' => false,
                    'message' => 'Booking not found'
                ], 404);
            }

            // Check if booking can be modified
            if (in_array($booking->status, ['completed', 'cancelled'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot update price for ' . $booking->status . ' booking'
                ], 400);
            }

            $oldPrice = $booking->final_amount ?? $booking->estimated_price;
            $newPrice = $request->adjusted_price;

            // Update price
            $booking->update([
                'adjusted_price' => $newPrice,
                'final_amount' => $newPrice
            ]);

            Log::info('Booking price updated', [
                'booking_id' => $booking->booking_id,
                'old_price' => $oldPrice,
                'new_price' => $newPrice,
                'user_id' => $userId
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Price updated successfully',
                'data' => [
                    'booking_id' => $booking->booking_id,
                    'old_price' => $oldPrice,
                    'new_price' => $newPrice,
                    'price_difference' => $newPrice - $oldPrice,
                    'status' => $booking->status,
                    'payment_method' => $booking->payment_method,
                    'updated_at' => $booking->updated_at
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Update Price Error', [
                'error' => $e->getMessage(),
                'line' => $e->getLine()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update price: ' . $e->getMessage()
            ], 500);
        }
    }

    // ✅ 7. CANCEL BOOKING
    public function cancelBooking(Request $request, $bookingId): JsonResponse
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

            $userId = $tokenParts[0];

            // Validation
            $validator = Validator::make($request->all(), [
                'cancellation_reason' => 'required|string|max:500'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 400);
            }

            // Find booking
            $booking = TruckBooking::where('id', $bookingId)
                ->where('user_id', $userId)
                ->first();

            if (!$booking) {
                return response()->json([
                    'success' => false,
                    'message' => 'Booking not found'
                ], 404);
            }

            // Check if already cancelled or completed
            if ($booking->status === 'cancelled') {
                return response()->json([
                    'success' => false,
                    'message' => 'Booking is already cancelled'
                ], 400);
            }

            if ($booking->status === 'completed') {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot cancel completed booking'
                ], 400);
            }

            DB::beginTransaction();

            try {
                // Cancel booking
                $booking->update([
                    'status' => 'cancelled',
                    'cancelled_at' => now(),
                    'cancellation_reason' => $request->cancellation_reason
                ]);

                // Free up vendor
                if ($booking->assigned_vendor_id) {
                    Vendor::where('id', $booking->assigned_vendor_id)
                        ->update([
                            'is_available_for_booking' => true,
                            'availability_status' => 'available'
                        ]);
                }

                DB::commit();

                Log::info('Booking cancelled', [
                    'booking_id' => $booking->booking_id,
                    'user_id' => $userId,
                    'reason' => $request->cancellation_reason
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Booking cancelled successfully',
                    'data' => [
                        'booking_id' => $booking->booking_id,
                        'status' => $booking->status,
                        'cancelled_at' => $booking->cancelled_at,
                        'cancellation_reason' => $booking->cancellation_reason,
                        'refund_info' => [
                            'eligible_for_refund' => $booking->payment_status === 'paid',
                            'refund_amount' => $booking->final_amount ?? $booking->estimated_price,
                            'processing_time' => '5-7 business days'
                        ]
                    ]
                ]);
            } catch (\Exception $e) {
                DB::rollback();
                throw $e;
            }
        } catch (\Exception $e) {
            Log::error('Cancel Booking Error', [
                'error' => $e->getMessage(),
                'line' => $e->getLine()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to cancel booking: ' . $e->getMessage()
            ], 500);
        }
    }

    // ✅ 8. GET SINGLE BOOKING DETAILS
    public function getBookingDetails(Request $request, $bookingId): JsonResponse
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

            $userId = $tokenParts[0];

            // Find booking with relationships
            $booking = TruckBooking::with(['material', 'assignedVendor', 'vehicleModel'])
                ->where('id', $bookingId)
                ->where('user_id', $userId)
                ->first();

            if (!$booking) {
                return response()->json([
                    'success' => false,
                    'message' => 'Booking not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Booking details retrieved successfully',
                'data' => [
                    'booking' => [
                        'id' => $booking->id,
                        'booking_id' => $booking->booking_id,
                        'status' => $booking->status,

                        // Location details
                        'pickup_address' => $booking->pickup_address,
                        'drop_address' => $booking->drop_address,
                        'distance_km' => $booking->distance_km,

                        // Material details
                        'material_name' => $booking->material_name,
                        'material_weight' => $booking->material_weight . ' tons',

                        // Vehicle details
                        'vehicle_model' => $booking->vehicleModel->model_name ?? 'N/A',
                        'truck_length' => $booking->truck_length . ' ft',
                        'tyre_count' => $booking->tyre_count . ' Tyre',

                        // Vendor details
                        'vendor' => [
                            'id' => $booking->assigned_vendor_id,
                            'name' => $booking->vendor_name,
                            'contact' => $booking->vendor_contact,
                            'vehicle_number' => $booking->vendor_vehicle_number
                        ],

                        // Pricing details
                        'pricing' => [
                            'estimated_price' => $booking->estimated_price,
                            'adjusted_price' => $booking->adjusted_price,
                            'final_amount' => $booking->final_amount,
                            'price_per_km' => $booking->price_per_km,
                            'is_price_adjusted' => !is_null($booking->adjusted_price)
                        ],

                        // Payment details
                        'payment' => [
                            'method' => $booking->payment_method,
                            'status' => $booking->payment_status,
                            'pay_at' => $booking->payment_method === 'pickup' ? 'Pickup Location' : 'Drop Location',
                            'completed_at' => $booking->payment_completed_at
                        ],

                        // Timeline
                        'timeline' => [
                            'pickup_datetime' => $booking->pickup_datetime,
                            'vendor_accepted_at' => $booking->vendor_accepted_at,
                            'trip_started_at' => $booking->trip_started_at,
                            'trip_completed_at' => $booking->trip_completed_at,
                            'cancelled_at' => $booking->cancelled_at
                        ],

                        // Other details
                        'special_instructions' => $booking->special_instructions,
                        'cancellation_reason' => $booking->cancellation_reason,
                        'created_at' => $booking->created_at,
                        'updated_at' => $booking->updated_at
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Get Booking Details Error', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve booking details'
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

    // ✅ 2. UPDATE VENDOR LOCATION (Called by Vendor App)
    public function updateVendorLocation(Request $request, $bookingId): JsonResponse
    {
        try {
            // Vendor token validation
            $authHeader = $request->header('Authorization');
            if (!$authHeader || !str_contains($authHeader, 'Bearer ')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Authorization token required'
                ], 401);
            }

            // Validation
            $validator = Validator::make($request->all(), [
                'latitude' => 'required|numeric|between:-90,90',
                'longitude' => 'required|numeric|between:-180,180',
                'speed' => 'nullable|numeric|min:0|max:200',
                'address' => 'nullable|string'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 400);
            }

            // Find booking
            $booking = TruckBooking::findOrFail($bookingId);

            if (!in_array($booking->status, ['confirmed', 'in_transit'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Booking is not active for tracking'
                ], 400);
            }

            $latitude = $request->latitude;
            $longitude = $request->longitude;
            $speed = $request->speed ?? 0;

            // Get address if not provided
            $address = $request->address;
            if (!$address) {
                $addressResult = $this->googleMapsService->getAddressFromCoordinates($latitude, $longitude);
                $address = $addressResult['success'] ? $addressResult['address'] : 'Location updating...';
            }

            // Calculate remaining distance
            $remainingDistance = $this->calculateDistance(
                $latitude,
                $longitude,
                $booking->drop_latitude,
                $booking->drop_longitude
            );

            $coveredDistance = $booking->distance_km - $remainingDistance;

            // Add to location history
            $locationHistory = $booking->location_history ?? [];
            $locationHistory[] = [
                'latitude' => $latitude,
                'longitude' => $longitude,
                'address' => $address,
                'speed' => $speed,
                'timestamp' => now()->toIso8601String()
            ];

            // Keep only last 100 locations
            if (count($locationHistory) > 100) {
                $locationHistory = array_slice($locationHistory, -100);
            }

            // Update booking
            $booking->update([
                'current_vendor_latitude' => $latitude,
                'current_vendor_longitude' => $longitude,
                'current_vendor_location' => $address,
                'location_updated_at' => now(),
                'current_speed_kmph' => $speed,
                'distance_covered_km' => round($coveredDistance, 2),
                'distance_remaining_km' => round($remainingDistance, 2),
                'location_history' => $locationHistory
            ]);

            // Also update vendor's current location
            if ($booking->assigned_vendor_id) {
                Vendor::where('id', $booking->assigned_vendor_id)->update([
                    'current_latitude' => $latitude,
                    'current_longitude' => $longitude,
                    'current_location' => $address
                ]);
            }

            Log::info('Vendor location updated', [
                'booking_id' => $booking->booking_id,
                'latitude' => $latitude,
                'longitude' => $longitude,
                'remaining_km' => round($remainingDistance, 2)
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Location updated successfully',
                'data' => [
                    'booking_id' => $booking->booking_id,
                    'current_location' => [
                        'latitude' => $latitude,
                        'longitude' => $longitude,
                        'address' => $address
                    ],
                    'distance_remaining_km' => round($remainingDistance, 2),
                    'distance_covered_km' => round($coveredDistance, 2),
                    'updated_at' => now()->format('Y-m-d H:i:s')
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Update Vendor Location Error', [
                'error' => $e->getMessage(),
                'line' => $e->getLine()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update location: ' . $e->getMessage()
            ], 500);
        }
    }

    // ✅ 3. GET LOCATION HISTORY
    public function getLocationHistory(Request $request, $bookingId): JsonResponse
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

            $userId = $tokenParts[0];

            $booking = TruckBooking::where('id', $bookingId)
                ->where('user_id', $userId)
                ->first();

            if (!$booking) {
                return response()->json([
                    'success' => false,
                    'message' => 'Booking not found'
                ], 404);
            }

            $locationHistory = $booking->location_history ?? [];

            return response()->json([
                'success' => true,
                'message' => 'Location history retrieved',
                'data' => [
                    'booking_id' => $booking->booking_id,
                    'total_locations' => count($locationHistory),
                    'locations' => $locationHistory,
                    'route_polyline' => $this->generatePolyline($locationHistory)
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Get Location History Error', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve location history'
            ], 500);
        }
    }

    // ✅ HELPER: Generate Polyline from location history
    private function generatePolyline($locations)
    {
        if (empty($locations)) {
            return null;
        }

        $coordinates = array_map(function ($loc) {
            return [
                'lat' => $loc['latitude'],
                'lng' => $loc['longitude']
            ];
        }, $locations);

        return $coordinates;
    }

    public function getPendingBookings(Request $request): JsonResponse
    {
        try {
            // ✅ Token validation
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

            $userId = $tokenParts[0];

            // Get pending bookings with vendor relationship
            $bookings = TruckBooking::where('user_id', $userId)
                ->where('status', 'pending')
                ->with(['vehicleModel', 'material', 'assignedVendor'])
                ->orderBy('created_at', 'desc')
                ->get();

            // Format bookings
            $formattedBookings = $bookings->map(function ($booking) {
                // ✅ GET COORDINATES
                $pickupLat = floatval($booking->pickup_latitude ?? 0);
                $pickupLng = floatval($booking->pickup_longitude ?? 0);
                $dropLat = floatval($booking->drop_latitude ?? 0);
                $dropLng = floatval($booking->drop_longitude ?? 0);

                // ✅ CALCULATE ACCURATE DISTANCE
                $aerialDistance = 0;
                $truckDistance = 0;

                if ($pickupLat && $pickupLng && $dropLat && $dropLng) {
                    $aerialDistance = $this->calculateDistance($pickupLat, $pickupLng, $dropLat, $dropLng);
                    $truckDistance = $aerialDistance * 1.18; // Add 18% for road route
                }

                // ✅ GET VENDOR DETAILS (if assigned)
                $vendorDetails = null;
                if ($booking->assignedVendor) {
                    $vendor = $booking->assignedVendor;
                    $vendorDetails = [
                        'id' => $vendor->id,
                        'name' => $vendor->name,
                        'contact_number' => $vendor->contact_number,
                        'email' => $vendor->email ?? 'N/A',
                        'city' => $vendor->city ?? 'N/A',
                        'state' => $vendor->state ?? 'N/A',
                        'vehicle_registration_number' => $vendor->vehicle_registration_number ?? 'N/A',
                        'vehicle_type' => $vendor->vehicle_type ?? 'N/A',
                        'availability_status' => $vendor->availability_status ?? 'N/A',
                        'current_location' => $vendor->current_location ?? 'Location not available',
                        'current_latitude' => $vendor->current_latitude ?? null,
                        'current_longitude' => $vendor->current_longitude ?? null,
                        'status_message' => 'Vendor has received your booking request'
                    ];
                }

                return [
                    'id' => $booking->id,
                    'booking_id' => $booking->booking_id,
                    'status' => 'pending',
                    'status_label' => 'Waiting for Vendor Acceptance',

                    // ✅ LOCATION DETAILS (WITH COORDINATES)
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

                    // ✅ ACCURATE DISTANCE
                    'distance' => [
                        'aerial_km' => round($aerialDistance, 2),
                        'truck_route_km' => round($truckDistance, 2),
                        'display' => round($truckDistance, 2) . ' km',
                        'google_maps_url' => "https://www.google.com/maps/dir/?api=1&origin={$pickupLat},{$pickupLng}&destination={$dropLat},{$dropLng}&travelmode=driving"
                    ],

                    // Material details
                    'material' => [
                        'name' => $booking->material_name,
                        'weight' => $booking->material_weight . ' tons',
                        'weight_value' => floatval($booking->material_weight)
                    ],

                    // Vehicle details
                    'vehicle' => [
                        'model' => $booking->vehicleModel->model_name ?? 'N/A',
                        'specifications' => [
                            'length' => $booking->truck_length . ' ft',
                            'tyre_count' => $booking->tyre_count . ' Tyre',
                            'height' => $booking->truck_height . ' ft'
                        ]
                    ],

                    // ✅ VENDOR DETAILS
                    'vendor' => $vendorDetails ?? [
                        'status' => 'no_vendor_assigned',
                        'message' => 'Searching for available vendors...'
                    ],

                    // Pricing
                    'pricing' => [
                        'estimated_price' => floatval($booking->estimated_price),
                        'adjusted_price' => floatval($booking->adjusted_price),
                        'final_amount' => floatval($booking->final_amount),
                        'currency' => 'INR',
                        'display' => '₹' . number_format($booking->final_amount, 2)
                    ],

                    // Payment
                    'payment' => [
                        'method' => $booking->payment_method,
                        'status' => $booking->payment_status
                    ],

                    // Timing
                    'schedule' => [
                        'pickup_datetime' => $booking->pickup_datetime,
                        'pickup_formatted' => \Carbon\Carbon::parse($booking->pickup_datetime)->format('d M Y, h:i A'),
                        'created_at' => $booking->created_at->format('Y-m-d H:i:s'),
                        'waiting_since' => $booking->created_at->diffForHumans()
                    ]
                ];
            });

            return response()->json([
                'success' => true,
                'message' => $bookings->count() . ' pending booking(s) found',
                'data' => [
                    'bookings' => $formattedBookings,
                    'total_count' => $bookings->count()
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Get Pending Bookings Error', [
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve pending bookings'
            ], 500);
        }
    }

    /**
     * ✅ CALCULATE DISTANCE BETWEEN TWO COORDINATES
     * Uses Haversine formula
     */
    private function calculateDistance($lat1, $lon1, $lat2, $lon2)
    {
        if (empty($lat1) || empty($lon1) || empty($lat2) || empty($lon2)) {
            return 0;
        }

        $earthRadius = 6371; // km
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

}
