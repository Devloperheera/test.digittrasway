<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class PincodeController extends Controller
{
    /**
     * ✅ GET LOCATION BY PINCODE
     * FREE API - No authentication required
     */
    public function getLocationByPincode(Request $request): JsonResponse
    {
        try {
            $pincode = $request->input('pincode');

            // Validate pincode
            if (!$pincode || !preg_match('/^[1-9][0-9]{5}$/', $pincode)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid pincode. Must be 6 digits.'
                ], 400);
            }

            // ✅ CHECK CACHE (Cache for 30 days)
            $cacheKey = 'pincode_' . $pincode;
            $cachedData = Cache::get($cacheKey);

            if ($cachedData) {
                Log::info('Pincode data from cache', ['pincode' => $pincode]);

                return response()->json([
                    'success' => true,
                    'message' => 'Location details retrieved successfully',
                    'data' => $cachedData,
                    'source' => 'cache'
                ]);
            }

            // ✅ CALL INDIA POST API (FREE - NO KEY NEEDED)
            $response = Http::timeout(10)->get("https://api.postalpincode.in/pincode/{$pincode}");

            if (!$response->successful()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to fetch pincode details'
                ], 500);
            }

            $data = $response->json();

            Log::info('Pincode API response', ['data' => $data]);

            // Check if valid response
            if (!isset($data[0]['Status']) || $data[0]['Status'] !== 'Success') {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid pincode or no data found',
                    'pincode' => $pincode
                ], 404);
            }

            $postOffices = $data[0]['PostOffice'] ?? [];

            if (empty($postOffices)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No post office data found for this pincode'
                ], 404);
            }

            // ✅ GET FIRST POST OFFICE DATA (Most accurate)
            $postOffice = $postOffices[0];

            $locationData = [
                'pincode' => $pincode,
                'city' => $postOffice['District'] ?? null,
                'district' => $postOffice['District'] ?? null,
                'state' => $postOffice['State'] ?? null,
                'country' => 'India',
                'post_office_name' => $postOffice['Name'] ?? null,
                'post_office_type' => $postOffice['BranchType'] ?? null,
                'delivery_status' => $postOffice['DeliveryStatus'] ?? null,
                'division' => $postOffice['Division'] ?? null,
                'region' => $postOffice['Region'] ?? null,
                'block' => $postOffice['Block'] ?? null,
                'all_post_offices' => array_map(function($po) {
                    return [
                        'name' => $po['Name'] ?? null,
                        'branch_type' => $po['BranchType'] ?? null,
                        'delivery_status' => $po['DeliveryStatus'] ?? null
                    ];
                }, $postOffices)
            ];

            // ✅ CACHE FOR 30 DAYS
            Cache::put($cacheKey, $locationData, now()->addDays(30));

            Log::info('Pincode data fetched and cached', ['pincode' => $pincode]);

            return response()->json([
                'success' => true,
                'message' => 'Location details retrieved successfully',
                'data' => $locationData,
                'source' => 'api'
            ]);

        } catch (\Exception $e) {
            Log::error('Get pincode location error', [
                'error' => $e->getMessage(),
                'line' => $e->getLine()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch location: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * ✅ SEARCH PINCODE BY CITY (OPTIONAL)
     */
    public function searchPincodeByCity(Request $request): JsonResponse
    {
        try {
            $city = $request->input('city');

            if (!$city) {
                return response()->json([
                    'success' => false,
                    'message' => 'City name required'
                ], 400);
            }

            $response = Http::timeout(10)->get("https://api.postalpincode.in/postoffice/{$city}");

            if (!$response->successful()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to search pincodes'
                ], 500);
            }

            $data = $response->json();

            if (!isset($data[0]['Status']) || $data[0]['Status'] !== 'Success') {
                return response()->json([
                    'success' => false,
                    'message' => 'No pincodes found for this city'
                ], 404);
            }

            $postOffices = $data[0]['PostOffice'] ?? [];

            $pincodes = array_map(function($po) {
                return [
                    'pincode' => $po['Pincode'] ?? null,
                    'post_office' => $po['Name'] ?? null,
                    'district' => $po['District'] ?? null,
                    'state' => $po['State'] ?? null
                ];
            }, $postOffices);

            return response()->json([
                'success' => true,
                'message' => 'Pincodes found',
                'data' => [
                    'city' => $city,
                    'total_results' => count($pincodes),
                    'pincodes' => $pincodes
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Search pincode error', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Search failed: ' . $e->getMessage()
            ], 500);
        }
    }
}
