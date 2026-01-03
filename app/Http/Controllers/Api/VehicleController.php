<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\VehicleCategory;
use App\Models\VehicleModel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class VehicleController extends Controller
{
    // ========================================
    // GET METHODS
    // ========================================

    /**
     * ✅ GET - Get all vehicle categories
     * GET /api/vehicle/categories
     */
    public function getCategories(): JsonResponse
    {
        try {
            $categories = VehicleCategory::active()
                ->ordered()
                ->withCount('vehicleModels')
                ->get()
                ->map(function ($category) {
                    return [
                        'id' => $category->id,
                        'category_key' => $category->category_key,
                        'category_name' => $category->category_name,
                        'description' => $category->description,
                        'icon' => $category->icon,
                        'vehicle_count' => $category->vehicle_models_count,
                        'display_order' => $category->display_order
                    ];
                });

            return response()->json([
                'success' => true,
                'message' => 'Vehicle categories retrieved successfully',
                'data' => [
                    'categories' => $categories,
                    'total_categories' => $categories->count()
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('Get Categories Error', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve categories',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * ✅ GET - Get vehicles by category ID
     * GET /api/vehicle/category/{category_id}
     */
    public function getVehiclesByCategory($category_id): JsonResponse
    {
        try {
            $category = VehicleCategory::active()->find($category_id);

            if (!$category) {
                return response()->json([
                    'success' => false,
                    'message' => 'Category not found'
                ], 404);
            }

            $vehicles = VehicleModel::active()
                ->byCategory($category->id)
                ->ordered()
                ->get()
                ->map(function ($vehicle) {
                    return [
                        'id' => $vehicle->id,
                        'model_name' => $vehicle->model_name,
                        'vehicle_type_desc' => $vehicle->vehicle_type_desc,
                        'body_length' => $vehicle->body_length,
                        'body_width' => $vehicle->body_width,
                        'body_height' => $vehicle->body_height,
                        'carry_capacity_kgs' => $vehicle->carry_capacity_kgs,
                        'carry_capacity_tons' => $vehicle->carry_capacity_tons
                    ];
                });

            return response()->json([
                'success' => true,
                'message' => 'Vehicles retrieved successfully',
                'data' => [
                    'category' => [
                        'id' => $category->id,
                        'category_key' => $category->category_key,
                        'category_name' => $category->category_name
                    ],
                    'vehicles' => $vehicles,
                    'total_vehicles' => $vehicles->count()
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('Get Vehicles By Category Error', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve vehicles',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * ✅ GET - Get vehicles by category key
     * GET /api/vehicle/category-key/{category_key}
     */
    public function getVehiclesByCategoryKey($category_key): JsonResponse
    {
        try {
            $category = VehicleCategory::active()
                ->where('category_key', $category_key)
                ->first();

            if (!$category) {
                return response()->json([
                    'success' => false,
                    'message' => 'Category not found'
                ], 404);
            }

            $vehicles = VehicleModel::active()
                ->byCategory($category->id)
                ->ordered()
                ->get()
                ->map(function ($vehicle) {
                    return [
                        'id' => $vehicle->id,
                        'model_name' => $vehicle->model_name,
                        'vehicle_type_desc' => $vehicle->vehicle_type_desc,
                        'body_length' => $vehicle->body_length,
                        'carry_capacity_tons' => $vehicle->carry_capacity_tons
                    ];
                });

            return response()->json([
                'success' => true,
                'message' => 'Vehicles retrieved successfully',
                'data' => [
                    'category' => [
                        'category_key' => $category->category_key,
                        'category_name' => $category->category_name
                    ],
                    'vehicles' => $vehicles
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('Get Vehicles By Category Key Error', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve vehicles',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * ✅ GET - Get single vehicle details
     * GET /api/vehicle/{vehicle_id}
     */
    public function getVehicleDetails($vehicle_id): JsonResponse
    {
        try {
            $vehicle = VehicleModel::active()
                ->with('category')
                ->find($vehicle_id);

            if (!$vehicle) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vehicle not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Vehicle details retrieved successfully',
                'data' => [
                    'id' => $vehicle->id,
                    'model_name' => $vehicle->model_name,
                    'vehicle_type_desc' => $vehicle->vehicle_type_desc,
                    'body_length' => $vehicle->body_length,
                    'body_width' => $vehicle->body_width,
                    'body_height' => $vehicle->body_height,
                    'carry_capacity_kgs' => $vehicle->carry_capacity_kgs,
                    'carry_capacity_tons' => $vehicle->carry_capacity_tons,
                    'category' => [
                        'id' => $vehicle->category->id ?? null,
                        'category_key' => $vehicle->category->category_key ?? null,
                        'category_name' => $vehicle->category->category_name ?? null
                    ]
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('Get Vehicle Details Error', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve vehicle details',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // ========================================
    // POST METHODS
    // ========================================

    /**
     * ✅ POST - Get all vehicle categories
     * POST /api/vehicle/get-categories
     */
    public function getCategoriesPost(Request $request): JsonResponse
    {
        return $this->getCategories();
    }

    /**
     * ✅ POST - Get vehicles by category
     * POST /api/vehicle/get-by-category
     */
    public function getVehiclesByCategoryPost(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'category_id' => 'nullable|integer|exists:vehicle_categories,id',
                'category_key' => 'nullable|string|exists:vehicle_categories,category_key'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 400);
            }

            if ($request->has('category_id')) {
                return $this->getVehiclesByCategory($request->category_id);
            } elseif ($request->has('category_key')) {
                return $this->getVehiclesByCategoryKey($request->category_key);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Please provide category_id or category_key'
                ], 400);
            }

        } catch (\Exception $e) {
            Log::error('Get Vehicles By Category POST Error', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve vehicles',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * ✅ POST - Get single vehicle details
     * POST /api/vehicle/get-details
     */
    public function getVehicleDetailsPost(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'vehicle_id' => 'required|integer|exists:vehicle_models,id'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 400);
            }

            return $this->getVehicleDetails($request->vehicle_id);

        } catch (\Exception $e) {
            Log::error('Get Vehicle Details POST Error', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve vehicle details',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * ✅ POST - Search vehicles
     * POST /api/vehicle/search
     */
    public function searchVehicles(Request $request): JsonResponse
    {
        try {
            $query = VehicleModel::active()->with('category');

            // Filter by category
            if ($request->has('category_key')) {
                $category = VehicleCategory::where('category_key', $request->category_key)->first();
                if ($category) {
                    $query->where('category_id', $category->id);
                }
            }

            if ($request->has('category_id')) {
                $query->where('category_id', $request->category_id);
            }

            // Filter by capacity
            if ($request->has('min_capacity_tons')) {
                $query->where('carry_capacity_tons', '>=', $request->min_capacity_tons);
            }

            if ($request->has('max_capacity_tons')) {
                $query->where('carry_capacity_tons', '<=', $request->max_capacity_tons);
            }

            // Filter by vehicle type
            if ($request->has('vehicle_type')) {
                $query->where('vehicle_type_desc', 'LIKE', '%' . $request->vehicle_type . '%');
            }

            $vehicles = $query->ordered()->get()->map(function ($vehicle) {
                return [
                    'id' => $vehicle->id,
                    'model_name' => $vehicle->model_name,
                    'vehicle_type_desc' => $vehicle->vehicle_type_desc,
                    'body_length' => $vehicle->body_length,
                    'carry_capacity_tons' => $vehicle->carry_capacity_tons,
                    'category' => [
                        'category_key' => $vehicle->category->category_key ?? null,
                        'category_name' => $vehicle->category->category_name ?? null
                    ]
                ];
            });

            return response()->json([
                'success' => true,
                'message' => 'Search results retrieved successfully',
                'data' => [
                    'vehicles' => $vehicles,
                    'total_results' => $vehicles->count()
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('Search Vehicles Error', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Search failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
