<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class PincodeController extends Controller
{
    /**
     * ✅ GET LOCATION BY PINCODE - PRODUCTION READY
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

            // ✅ CALL INDIA POST API USING DIRECT cURL (MOST RELIABLE)
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, "https://api.postalpincode.in/pincode/{$pincode}");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Disable SSL verification
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 15);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);

            if ($curlError) {
                Log::error('cURL error', [
                    'pincode' => $pincode,
                    'error' => $curlError
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Failed to connect to pincode service'
                ], 500);
            }

            if ($httpCode !== 200) {
                Log::error('API HTTP error', [
                    'pincode' => $pincode,
                    'http_code' => $httpCode
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Pincode service unavailable'
                ], 500);
            }

            $data = json_decode($response, true);

            Log::info('Pincode API response', [
                'pincode' => $pincode,
                'response_status' => $data[0]['Status'] ?? 'unknown'
            ]);

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
                'line' => $e->getLine(),
                'file' => $e->getFile()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while fetching location details'
            ], 500);
        }
    }

    /**
     * ✅ SEARCH PINCODE BY CITY
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

            // ✅ USE cURL FOR CITY SEARCH
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, "https://api.postalpincode.in/postoffice/{$city}");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 15);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);

            if ($curlError) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to search pincodes'
                ], 500);
            }

            if ($httpCode !== 200) {
                return response()->json([
                    'success' => false,
                    'message' => 'Search service unavailable'
                ], 500);
            }

            $data = json_decode($response, true);

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
            Log::error('Search pincode error', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Search failed'
            ], 500);
        }
    }
}
