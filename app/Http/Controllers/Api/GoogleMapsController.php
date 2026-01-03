<?php
// app/Http/Controllers/Api/GoogleMapsController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\GoogleMapsService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class GoogleMapsController extends Controller
{
    private $googleMapsService;

    public function __construct(GoogleMapsService $googleMapsService)
    {
        $this->googleMapsService = $googleMapsService;
    }

    // ✅ CALCULATE DISTANCE
    public function calculateDistance(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'from_lat' => 'required|numeric|between:-90,90',
                'from_lng' => 'required|numeric|between:-180,180',
                'to_lat' => 'required|numeric|between:-90,90',
                'to_lng' => 'required|numeric|between:-180,180',
                'mode' => 'nullable|in:driving,walking,bicycling,transit'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 400);
            }

            $mode = $request->mode ?? 'driving';

            $result = $this->googleMapsService->calculateDistance(
                $request->from_lat,
                $request->from_lng,
                $request->to_lat,
                $request->to_lng,
                $mode
            );

            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $result['message']
                ], 400);
            }

            return response()->json([
                'success' => true,
                'message' => 'Distance calculated successfully',
                'data' => [
                    'from' => [
                        'latitude' => $request->from_lat,
                        'longitude' => $request->from_lng
                    ],
                    'to' => [
                        'latitude' => $request->to_lat,
                        'longitude' => $request->to_lng
                    ],
                    'distance' => [
                        'kilometers' => $result['distance_km'],
                        'meters' => $result['distance_meters'],
                        'text' => $result['distance_text']
                    ],
                    'duration' => [
                        'minutes' => $result['duration_minutes'],
                        'seconds' => $result['duration_seconds'],
                        'text' => $result['duration_text']
                    ],
                    'mode' => $mode
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Calculate Distance Error', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Distance calculation failed: ' . $e->getMessage()
            ], 500);
        }
    }

    // ✅ REVERSE GEOCODE
    public function reverseGeocode(Request $request): JsonResponse
    {
        try {
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

            $result = $this->googleMapsService->getAddressFromCoordinates(
                $request->latitude,
                $request->longitude
            );

            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $result['message']
                ], 400);
            }

            return response()->json([
                'success' => true,
                'message' => 'Address retrieved successfully',
                'data' => [
                    'coordinates' => [
                        'latitude' => $request->latitude,
                        'longitude' => $request->longitude
                    ],
                    'address' => $result['address'],
                    'place_id' => $result['place_id'],
                    'address_components' => $result['address_components']
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Reverse Geocode Error', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Reverse geocoding failed: ' . $e->getMessage()
            ], 500);
        }
    }

    // ✅ GEOCODE
    public function geocode(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'address' => 'required|string|min:3|max:500'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 400);
            }

            $result = $this->googleMapsService->getCoordinatesFromAddress($request->address);

            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $result['message']
                ], 400);
            }

            return response()->json([
                'success' => true,
                'message' => 'Coordinates retrieved successfully',
                'data' => [
                    'input_address' => $request->address,
                    'formatted_address' => $result['formatted_address'],
                    'coordinates' => [
                        'latitude' => $result['latitude'],
                        'longitude' => $result['longitude']
                    ],
                    'place_id' => $result['place_id']
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Geocode Error', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Geocoding failed: ' . $e->getMessage()
            ], 500);
        }
    }

    // ✅ BATCH DISTANCE
    public function batchDistance(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'origin.latitude' => 'required|numeric|between:-90,90',
                'origin.longitude' => 'required|numeric|between:-180,180',
                'destinations' => 'required|array|min:1|max:10',
                'destinations.*.latitude' => 'required|numeric|between:-90,90',
                'destinations.*.longitude' => 'required|numeric|between:-180,180',
                'destinations.*.name' => 'nullable|string|max:100'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 400);
            }

            $origin = $request->origin;
            $destinations = $request->destinations;
            $results = [];

            foreach ($destinations as $index => $destination) {
                $distanceResult = $this->googleMapsService->calculateDistance(
                    $origin['latitude'],
                    $origin['longitude'],
                    $destination['latitude'],
                    $destination['longitude']
                );

                $results[] = [
                    'destination_index' => $index,
                    'destination_name' => $destination['name'] ?? "Destination " . ($index + 1),
                    'destination' => [
                        'latitude' => $destination['latitude'],
                        'longitude' => $destination['longitude']
                    ],
                    'distance' => $distanceResult['success'] ? [
                        'kilometers' => $distanceResult['distance_km'],
                        'text' => $distanceResult['distance_text']
                    ] : null,
                    'duration' => $distanceResult['success'] ? [
                        'minutes' => $distanceResult['duration_minutes'],
                        'text' => $distanceResult['duration_text']
                    ] : null,
                    'success' => $distanceResult['success']
                ];
            }

            $successCount = collect($results)->where('success', true)->count();

            return response()->json([
                'success' => true,
                'message' => 'Batch distance calculated successfully',
                'data' => [
                    'origin' => $origin,
                    'total_destinations' => count($destinations),
                    'successful_calculations' => $successCount,
                    'results' => $results
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Batch Distance Error', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Batch distance calculation failed: ' . $e->getMessage()
            ], 500);
        }
    }

    // ✅ SEARCH PLACES
    public function searchPlaces(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'query' => 'required|string|min:2|max:200',
                'latitude' => 'nullable|numeric|between:-90,90',
                'longitude' => 'nullable|numeric|between:-180,180',
                'radius' => 'nullable|integer|min:1|max:50000'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 400);
            }

            $result = $this->googleMapsService->searchPlaces(
                $request->query,
                $request->latitude,
                $request->longitude,
                $request->radius ?? 10000
            );

            return response()->json([
                'success' => $result['success'],
                'message' => $result['success'] ? 'Places found successfully' : $result['message'],
                'data' => $result
            ]);

        } catch (\Exception $e) {
            Log::error('Search Places Error', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Place search failed: ' . $e->getMessage()
            ], 500);
        }
    }

    // ✅ GET ROUTE DETAILS
    public function getRouteDetails(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'from_lat' => 'required|numeric|between:-90,90',
                'from_lng' => 'required|numeric|between:-180,180',
                'to_lat' => 'required|numeric|between:-90,90',
                'to_lng' => 'required|numeric|between:-180,180',
                'waypoints' => 'nullable|array',
                'waypoints.*.lat' => 'required_with:waypoints|numeric|between:-90,90',
                'waypoints.*.lng' => 'required_with:waypoints|numeric|between:-180,180'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 400);
            }

            $result = $this->googleMapsService->getRouteDetails(
                $request->from_lat,
                $request->from_lng,
                $request->to_lat,
                $request->to_lng,
                $request->waypoints ?? []
            );

            return response()->json([
                'success' => $result['success'],
                'message' => $result['success'] ? 'Route details retrieved' : $result['message'],
                'data' => $result
            ]);

        } catch (\Exception $e) {
            Log::error('Get Route Details Error', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to get route details: ' . $e->getMessage()
            ], 500);
        }
    }

    // ✅ GET API STATUS
    public function getApiStatus(): JsonResponse
    {
        try {
            $apiKey = env('GOOGLE_MAPS_API_KEY');

            // Test API with Delhi to Mumbai distance
            $testResult = $this->googleMapsService->calculateDistance(
                28.6139, 77.2090, // Delhi
                19.0760, 72.8777  // Mumbai
            );

            return response()->json([
                'success' => true,
                'message' => 'Google Maps API status retrieved',
                'data' => [
                    'api_key_configured' => !empty($apiKey),
                    'api_key_masked' => $apiKey ? substr($apiKey, 0, 10) . '...' . substr($apiKey, -5) : null,
                    'api_functional' => $testResult['success'],
                    'test_calculation' => $testResult['success'] ? [
                        'route' => 'Delhi to Mumbai',
                        'distance_km' => $testResult['distance_km'],
                        'distance_text' => $testResult['distance_text'],
                        'duration_text' => $testResult['duration_text']
                    ] : null,
                    'error' => $testResult['success'] ? null : $testResult['message'],
                    'checked_at' => now()->toDateTimeString()
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('API Status Check Error', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'API status check failed: ' . $e->getMessage()
            ], 500);
        }
    }
}
