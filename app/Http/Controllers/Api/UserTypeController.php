<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UserType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class UserTypeController extends Controller
{
    /**
     * ✅ Get all active user types
     * GET /api/user-types
     */
    public function index(): JsonResponse
    {
        try {
            $userTypes = UserType::active()
                ->ordered()
                ->get()
                ->map(function ($type) {
                    return [
                        'id' => $type->id,
                        'type_key' => $type->type_key,
                        'title' => $type->title,
                        'subtitle' => $type->subtitle,
                        'description' => $type->description,
                        'icon' => $type->icon,
                        'features' => $type->features,
                        'display_order' => $type->display_order
                    ];
                });

            return response()->json([
                'success' => true,
                'message' => 'User types retrieved successfully',
                'data' => [
                    'user_types' => $userTypes,
                    'total_count' => $userTypes->count()
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('Get User Types Error', [
                'error' => $e->getMessage(),
                'line' => $e->getLine()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve user types',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * ✅ Get single user type by ID
     * GET /api/user-types/{id}
     */
    public function show($id): JsonResponse
    {
        try {
            $userType = UserType::active()->find($id);

            if (!$userType) {
                return response()->json([
                    'success' => false,
                    'message' => 'User type not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'User type retrieved successfully',
                'data' => [
                    'id' => $userType->id,
                    'type_key' => $userType->type_key,
                    'title' => $userType->title,
                    'subtitle' => $userType->subtitle,
                    'description' => $userType->description,
                    'icon' => $userType->icon,
                    'features' => $userType->features,
                    'display_order' => $userType->display_order
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('Get User Type Error', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve user type',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * ✅ Get user type by type_key
     * GET /api/user-types/by-key/{type_key}
     */
    public function getByKey($typeKey): JsonResponse
    {
        try {
            $userType = UserType::active()
                ->where('type_key', $typeKey)
                ->first();

            if (!$userType) {
                return response()->json([
                    'success' => false,
                    'message' => 'User type not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'User type retrieved successfully',
                'data' => [
                    'id' => $userType->id,
                    'type_key' => $userType->type_key,
                    'title' => $userType->title,
                    'subtitle' => $userType->subtitle,
                    'description' => $userType->description,
                    'icon' => $userType->icon,
                    'features' => $userType->features,
                    'display_order' => $userType->display_order
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('Get User Type By Key Error', [
                'type_key' => $typeKey,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve user type',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
