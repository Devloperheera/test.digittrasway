<?php
// app/Services/GoogleMapsService.php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GoogleMapsService
{
    private $apiKey;
    private $baseUrl = 'https://maps.googleapis.com/maps/api';

    public function __construct()
    {
        $this->apiKey = env('GOOGLE_MAPS_API_KEY', 'AIzaSyDnrWUIvxphrz5qBWOyQX8SpOfG8LIlXdA');
    }

    /**
     * Calculate distance between two coordinates
     */
    public function calculateDistance($fromLat, $fromLng, $toLat, $toLng, $mode = 'driving')
    {
        try {
            $url = $this->baseUrl . '/distancematrix/json';

            $origin = "{$fromLat},{$fromLng}";
            $destination = "{$toLat},{$toLng}";

            $response = Http::timeout(30)->get($url, [
                'origins' => $origin,
                'destinations' => $destination,
                'mode' => $mode,
                'units' => 'metric',
                'key' => $this->apiKey
            ]);

            if ($response->successful()) {
                $data = $response->json();

                if ($data['status'] === 'OK' && isset($data['rows'][0]['elements'][0])) {
                    $element = $data['rows'][0]['elements'][0];

                    if ($element['status'] === 'OK') {
                        $distanceMeters = $element['distance']['value'];
                        $durationSeconds = $element['duration']['value'];

                        return [
                            'success' => true,
                            'distance_km' => round($distanceMeters / 1000, 2),
                            'distance_meters' => $distanceMeters,
                            'distance_text' => $element['distance']['text'],
                            'duration_minutes' => round($durationSeconds / 60, 0),
                            'duration_seconds' => $durationSeconds,
                            'duration_text' => $element['duration']['text']
                        ];
                    } else {
                        return [
                            'success' => false,
                            'message' => 'Route not found: ' . $element['status']
                        ];
                    }
                } else {
                    return [
                        'success' => false,
                        'message' => 'API returned error: ' . ($data['status'] ?? 'Unknown')
                    ];
                }
            }

            Log::error('Google Maps Distance API Error', [
                'status' => $response->status(),
                'response' => $response->json()
            ]);

            return [
                'success' => false,
                'message' => 'Failed to calculate distance'
            ];

        } catch (\Exception $e) {
            Log::error('Distance Calculation Exception', [
                'error' => $e->getMessage(),
                'from' => "{$fromLat},{$fromLng}",
                'to' => "{$toLat},{$toLng}"
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Get address from coordinates (Reverse Geocoding)
     */
    public function getAddressFromCoordinates($latitude, $longitude)
    {
        try {
            $url = $this->baseUrl . '/geocode/json';

            $response = Http::timeout(30)->get($url, [
                'latlng' => "{$latitude},{$longitude}",
                'key' => $this->apiKey
            ]);

            if ($response->successful()) {
                $data = $response->json();

                if ($data['status'] === 'OK' && !empty($data['results'])) {
                    return [
                        'success' => true,
                        'address' => $data['results'][0]['formatted_address'],
                        'place_id' => $data['results'][0]['place_id'],
                        'address_components' => $data['results'][0]['address_components'],
                        'types' => $data['results'][0]['types'] ?? []
                    ];
                } else {
                    return [
                        'success' => false,
                        'message' => 'Address not found: ' . ($data['status'] ?? 'Unknown')
                    ];
                }
            }

            return [
                'success' => false,
                'message' => 'Failed to get address'
            ];

        } catch (\Exception $e) {
            Log::error('Reverse Geocoding Exception', [
                'error' => $e->getMessage(),
                'coordinates' => "{$latitude},{$longitude}"
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Get coordinates from address (Geocoding)
     */
    public function getCoordinatesFromAddress($address)
    {
        try {
            $url = $this->baseUrl . '/geocode/json';

            $response = Http::timeout(30)->get($url, [
                'address' => $address,
                'key' => $this->apiKey
            ]);

            if ($response->successful()) {
                $data = $response->json();

                if ($data['status'] === 'OK' && !empty($data['results'])) {
                    $location = $data['results'][0]['geometry']['location'];

                    return [
                        'success' => true,
                        'latitude' => $location['lat'],
                        'longitude' => $location['lng'],
                        'formatted_address' => $data['results'][0]['formatted_address'],
                        'place_id' => $data['results'][0]['place_id'],
                        'address_components' => $data['results'][0]['address_components'],
                        'types' => $data['results'][0]['types'] ?? []
                    ];
                } else {
                    return [
                        'success' => false,
                        'message' => 'Coordinates not found: ' . ($data['status'] ?? 'Unknown')
                    ];
                }
            }

            return [
                'success' => false,
                'message' => 'Failed to geocode address'
            ];

        } catch (\Exception $e) {
            Log::error('Geocoding Exception', [
                'error' => $e->getMessage(),
                'address' => $address
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Search places using Places API
     */
    public function searchPlaces($query, $latitude = null, $longitude = null, $radius = 10000)
    {
        try {
            $url = $this->baseUrl . '/place/textsearch/json';

            $params = [
                'query' => $query,
                'key' => $this->apiKey
            ];

            if ($latitude && $longitude) {
                $params['location'] = "{$latitude},{$longitude}";
                $params['radius'] = $radius;
            }

            $response = Http::timeout(30)->get($url, $params);

            if ($response->successful()) {
                $data = $response->json();

                if ($data['status'] === 'OK' && !empty($data['results'])) {
                    $places = collect($data['results'])->map(function ($place) {
                        return [
                            'place_id' => $place['place_id'],
                            'name' => $place['name'],
                            'formatted_address' => $place['formatted_address'] ?? null,
                            'geometry' => [
                                'location' => [
                                    'lat' => $place['geometry']['location']['lat'],
                                    'lng' => $place['geometry']['location']['lng']
                                ]
                            ],
                            'rating' => $place['rating'] ?? null,
                            'user_ratings_total' => $place['user_ratings_total'] ?? 0,
                            'types' => $place['types'] ?? [],
                            'business_status' => $place['business_status'] ?? null
                        ];
                    });

                    return [
                        'success' => true,
                        'places' => $places,
                        'total_results' => count($places)
                    ];
                } else {
                    return [
                        'success' => false,
                        'message' => 'No places found: ' . ($data['status'] ?? 'Unknown')
                    ];
                }
            }

            return [
                'success' => false,
                'message' => 'Failed to search places'
            ];

        } catch (\Exception $e) {
            Log::error('Places Search Exception', [
                'error' => $e->getMessage(),
                'query' => $query
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Calculate distance using Haversine formula (fallback method)
     */
    public function calculateHaversineDistance($lat1, $lon1, $lat2, $lon2)
    {
        $earthRadius = 6371; // km

        $lat1 = deg2rad($lat1);
        $lon1 = deg2rad($lon1);
        $lat2 = deg2rad($lat2);
        $lon2 = deg2rad($lon2);

        $deltaLat = $lat2 - $lat1;
        $deltaLon = $lon2 - $lon1;

        $a = sin($deltaLat / 2) * sin($deltaLat / 2) +
             cos($lat1) * cos($lat2) *
             sin($deltaLon / 2) * sin($deltaLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    /**
     * Find nearby vendors
     */
    public function findNearbyVendors($latitude, $longitude, $radiusKm = 10)
    {
        try {
            $vendors = \App\Models\Vendor::available()
                ->whereNotNull('current_latitude')
                ->whereNotNull('current_longitude')
                ->get()
                ->map(function ($vendor) use ($latitude, $longitude) {
                    $distance = $this->calculateHaversineDistance(
                        $latitude,
                        $longitude,
                        $vendor->current_latitude,
                        $vendor->current_longitude
                    );

                    return [
                        'vendor' => $vendor,
                        'distance_km' => round($distance, 2)
                    ];
                })
                ->filter(function ($item) use ($radiusKm) {
                    return $item['distance_km'] <= $radiusKm;
                })
                ->sortBy('distance_km')
                ->values();

            return [
                'success' => true,
                'vendors' => $vendors,
                'total_found' => $vendors->count()
            ];

        } catch (\Exception $e) {
            Log::error('Find Nearby Vendors Error', [
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Get route details between two points
     */
    public function getRouteDetails($fromLat, $fromLng, $toLat, $toLng, $waypoints = [])
    {
        try {
            $url = $this->baseUrl . '/directions/json';

            $params = [
                'origin' => "{$fromLat},{$fromLng}",
                'destination' => "{$toLat},{$toLng}",
                'mode' => 'driving',
                'key' => $this->apiKey
            ];

            if (!empty($waypoints)) {
                $waypointStr = implode('|', array_map(function ($wp) {
                    return "{$wp['lat']},{$wp['lng']}";
                }, $waypoints));
                $params['waypoints'] = $waypointStr;
            }

            $response = Http::timeout(30)->get($url, $params);

            if ($response->successful()) {
                $data = $response->json();

                if ($data['status'] === 'OK' && !empty($data['routes'])) {
                    $route = $data['routes'][0];
                    $leg = $route['legs'][0];

                    return [
                        'success' => true,
                        'distance' => [
                            'text' => $leg['distance']['text'],
                            'value' => $leg['distance']['value']
                        ],
                        'duration' => [
                            'text' => $leg['duration']['text'],
                            'value' => $leg['duration']['value']
                        ],
                        'start_address' => $leg['start_address'],
                        'end_address' => $leg['end_address'],
                        'steps' => collect($leg['steps'])->map(function ($step) {
                            return [
                                'instruction' => strip_tags($step['html_instructions']),
                                'distance' => $step['distance']['text'],
                                'duration' => $step['duration']['text']
                            ];
                        }),
                        'overview_polyline' => $route['overview_polyline']['points']
                    ];
                } else {
                    return [
                        'success' => false,
                        'message' => 'Route not found: ' . ($data['status'] ?? 'Unknown')
                    ];
                }
            }

            return [
                'success' => false,
                'message' => 'Failed to get route details'
            ];

        } catch (\Exception $e) {
            Log::error('Route Details Exception', [
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Get estimated price based on distance
     */
    public function calculateEstimatedPrice($distanceKm, $basePrice = 100, $pricePerKm = 10)
    {
        try {
            $totalPrice = $basePrice + ($distanceKm * $pricePerKm);

            return [
                'success' => true,
                'base_price' => $basePrice,
                'distance_km' => $distanceKm,
                'price_per_km' => $pricePerKm,
                'distance_charge' => $distanceKm * $pricePerKm,
                'total_price' => round($totalPrice, 2),
                'gst' => round($totalPrice * 0.18, 2),
                'final_price' => round($totalPrice * 1.18, 2)
            ];
        } catch (\Exception $e) {
            Log::error('Price Calculation Error', [
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Validate coordinates
     */
    public function validateCoordinates($latitude, $longitude)
    {
        return is_numeric($latitude) &&
               is_numeric($longitude) &&
               $latitude >= -90 &&
               $latitude <= 90 &&
               $longitude >= -180 &&
               $longitude <= 180;
    }

    /**
     * Format coordinates
     */
    public function formatCoordinates($latitude, $longitude)
    {
        return [
            'latitude' => round((float)$latitude, 8),
            'longitude' => round((float)$longitude, 8),
            'lat_lng_string' => "{$latitude},{$longitude}"
        ];
    }

    public function getRouteDirections($originLat, $originLng, $destLat, $destLng)
{
    try {
        $apiKey = env('GOOGLE_MAPS_API_KEY');

        $url = "https://maps.googleapis.com/maps/api/directions/json?" . http_build_query([
            'origin' => "{$originLat},{$originLng}",
            'destination' => "{$destLat},{$destLng}",
            'key' => $apiKey,
            'mode' => 'driving'
        ]);

        $response = file_get_contents($url);
        $data = json_decode($response, true);

        if ($data['status'] === 'OK' && !empty($data['routes'])) {
            $route = $data['routes'][0];

            return [
                'success' => true,
                'polyline' => $route['overview_polyline']['points'] ?? null,
                'steps' => array_map(function($step) {
                    return [
                        'instruction' => strip_tags($step['html_instructions']),
                        'distance' => $step['distance']['text'],
                        'duration' => $step['duration']['text']
                    ];
                }, $route['legs'][0]['steps'] ?? [])
            ];
        }

        return ['success' => false, 'message' => 'No route found'];

    } catch (\Exception $e) {
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

}
