<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Vendor;
use App\Models\VendorVehicle;
use App\Models\VehicleCategory;
use App\Models\VehicleModel;
use App\Services\DocumentVerificationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class VendorFleetController extends Controller
{
    protected $docVerificationService;

    public function __construct()
    {
        $this->docVerificationService = new DocumentVerificationService();
    }

    // ✅ 1. ADD VEHICLE TO FLEET (WITH RC & DL VERIFICATION)
    public function addVehicle(Request $request): JsonResponse
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
            $vendor = Vendor::with('userType')->find($vendorId);

            if (!$vendor) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vendor not found'
                ], 404);
            }

            // Check if vendor is fleet owner
            if (!$vendor->isFleetOwner()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only fleet owners can add multiple vehicles'
                ], 403);
            }

            // Validation
            $validator = Validator::make($request->all(), [
                'vehicle_category_id' => 'required|integer|exists:vehicle_categories,id',
                'vehicle_model_id' => 'required|integer|exists:vehicle_models,id',
                'vehicle_registration_number' => 'required|string|max:20|unique:vendor_vehicles,vehicle_registration_number',
                'vehicle_name' => 'nullable|string|max:100',
                'owner_name' => 'required|string|max:255',
                'rc_number' => 'required|string|max:20|unique:vendor_vehicles,rc_number',
                'manufacturing_year' => 'nullable|integer|digits:4|min:1990|max:' . (date('Y') + 1),
                'vehicle_color' => 'nullable|string|max:50',
                'chassis_number' => 'nullable|string|max:50',
                'engine_number' => 'nullable|string|max:50',
                'insurance_number' => 'nullable|string|max:50',
                'insurance_expiry' => 'nullable|date|after:today',
                'fitness_expiry' => 'nullable|date|after:today',
                'permit_expiry' => 'nullable|date|after:today',
                'dl_number' => 'nullable|string|max:20',
                'description' => 'nullable|string|max:500',
                'has_gps' => 'nullable|boolean',

                // Images
                'vehicle_image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
                'rc_front_image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
                'rc_back_image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
                'insurance_image' => 'nullable|image|mimes:jpeg,png,jpg,pdf|max:2048',
                'fitness_certificate' => 'nullable|image|mimes:jpeg,png,jpg,pdf|max:2048',
                'permit_image' => 'nullable|image|mimes:jpeg,png,jpg,pdf|max:2048',
                'dl_image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 400);
            }

            // Validate vehicle model belongs to category
            $vehicleModel = VehicleModel::where('id', $request->vehicle_model_id)
                ->where('category_id', $request->vehicle_category_id)
                ->first();

            if (!$vehicleModel) {
                return response()->json([
                    'success' => false,
                    'message' => 'Selected vehicle model does not belong to the chosen category'
                ], 400);
            }

            DB::beginTransaction();

            try {
                $verificationResults = [];

                // ✅ RC VERIFICATION (SurePass API)
                if ($request->rc_number && $request->owner_name) {
                    try {
                        $rcVerification = $this->docVerificationService->verifyRc(
                            $request->rc_number,
                            $request->owner_name
                        );

                        if ($rcVerification['success']) {
                            $verificationResults['rc_verified'] = true;
                            $verificationResults['rc_verified_data'] = json_encode($rcVerification['data']);
                            $verificationResults['rc_verification_date'] = now();
                            $verificationResults['rc_verification_status'] = 'success';

                            Log::info('RC verification successful', [
                                'rc_number' => $request->rc_number,
                                'vendor_id' => $vendorId
                            ]);
                        } else {
                            $verificationResults['rc_verified'] = false;
                            $verificationResults['rc_verification_status'] = 'failed';
                        }
                    } catch (\Exception $e) {
                        Log::error('RC verification failed', [
                            'rc_number' => $request->rc_number,
                            'error' => $e->getMessage()
                        ]);
                        $verificationResults['rc_verified'] = false;
                        $verificationResults['rc_verification_status'] = 'failed';
                    }
                }

                // ✅ DL VERIFICATION (if provided)
                if ($request->dl_number && $vendor->dob) {
                    try {
                        $dlVerification = $this->docVerificationService->verifyDl(
                            $request->dl_number,
                            $vendor->dob
                        );

                        if ($dlVerification['success']) {
                            $verificationResults['dl_verified'] = true;
                            $verificationResults['dl_verified_data'] = json_encode($dlVerification['data']);
                            $verificationResults['dl_verification_date'] = now();

                            Log::info('DL verification successful', [
                                'dl_number' => $request->dl_number,
                                'vendor_id' => $vendorId
                            ]);
                        }
                    } catch (\Exception $e) {
                        Log::error('DL verification failed', [
                            'dl_number' => $request->dl_number,
                            'error' => $e->getMessage()
                        ]);
                    }
                }

                // Handle document uploads
                $documentPaths = $this->handleVehicleDocuments($request, $vendorId);

                // Create vehicle record
                $vehicleData = array_merge(
                    $validator->validated(),
                    $documentPaths,
                    $verificationResults,
                    [
                        'vendor_id' => $vendorId,
                        'status' => 'pending',
                        'is_listed' => false,
                        'is_available' => false,
                        'availability_status' => 'offline',
                        'can_accept_bookings' => false
                    ]
                );

                $vehicle = VendorVehicle::create($vehicleData);

                DB::commit();

                Log::info('Vehicle added to fleet successfully', [
                    'vendor_id' => $vendorId,
                    'vehicle_id' => $vehicle->id,
                    'registration' => $vehicle->vehicle_registration_number,
                    'rc_verified' => $vehicle->rc_verified
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Vehicle added successfully! It will be reviewed by admin.',
                    'data' => [
                        'vehicle' => $this->formatVehicleData($vehicle),
                        'verification_status' => [
                            'rc_verified' => $vehicle->rc_verified,
                            'rc_status' => $vehicle->rc_verification_status,
                            'dl_verified' => $vehicle->dl_verified ?? false
                        ]
                    ]
                ]);

            } catch (\Exception $e) {
                DB::rollback();
                throw $e;
            }

        } catch (\Exception $e) {
            Log::error('Add vehicle to fleet error', [
                'vendor_id' => $vendorId ?? null,
                'error' => $e->getMessage(),
                'line' => $e->getLine()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to add vehicle: ' . $e->getMessage()
            ], 500);
        }
    }

    // ✅ 2. GET ALL FLEET VEHICLES
    public function getFleetVehicles(Request $request): JsonResponse
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

            $vehicles = VendorVehicle::where('vendor_id', $vendorId)
                ->with(['vehicleCategory', 'vehicleModel'])
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($vehicle) {
                    return $this->formatVehicleData($vehicle);
                });

            return response()->json([
                'success' => true,
                'message' => 'Fleet vehicles retrieved successfully',
                'data' => [
                    'vehicles' => $vehicles,
                    'stats' => [
                        'total_vehicles' => $vehicles->count(),
                        'active_vehicles' => $vehicles->where('status', 'active')->count(),
                        'pending_vehicles' => $vehicles->where('status', 'pending')->count(),
                        'listed_vehicles' => $vehicles->where('is_listed', true)->count(),
                        'available_vehicles' => $vehicles->where('availability_status', 'available')->count(),
                        'verified_vehicles' => $vehicles->where('rc_verified', true)->count()
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Get fleet vehicles error', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve fleet vehicles'
            ], 500);
        }
    }

    // ✅ 3. GET SINGLE VEHICLE DETAILS
    public function getVehicleDetails(Request $request, $vehicleId): JsonResponse
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

            $vehicle = VendorVehicle::with(['vehicleCategory', 'vehicleModel', 'vendor'])
                ->where('id', $vehicleId)
                ->where('vendor_id', $vendorId)
                ->first();

            if (!$vehicle) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vehicle not found in your fleet'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Vehicle details retrieved successfully',
                'data' => [
                    'vehicle' => $this->formatVehicleData($vehicle, true)
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Get vehicle details error', [
                'vehicle_id' => $vehicleId ?? null,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve vehicle details'
            ], 500);
        }
    }

    // ✅ 4. UPDATE VEHICLE
    public function updateVehicle(Request $request, $vehicleId): JsonResponse
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

            $vehicle = VendorVehicle::where('id', $vehicleId)
                ->where('vendor_id', $vendorId)
                ->first();

            if (!$vehicle) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vehicle not found in your fleet'
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'vehicle_name' => 'nullable|string|max:100',
                'vehicle_color' => 'nullable|string|max:50',
                'insurance_number' => 'nullable|string|max:50',
                'insurance_expiry' => 'nullable|date|after:today',
                'fitness_expiry' => 'nullable|date|after:today',
                'permit_expiry' => 'nullable|date|after:today',
                'description' => 'nullable|string|max:500',
                'has_gps' => 'nullable|boolean',
                'vehicle_image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
                'insurance_image' => 'nullable|image|mimes:jpeg,png,jpg,pdf|max:2048',
                'fitness_certificate' => 'nullable|image|mimes:jpeg,png,jpg,pdf|max:2048',
                'permit_image' => 'nullable|image|mimes:jpeg,png,jpg,pdf|max:2048'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 400);
            }

            $documentPaths = $this->handleVehicleDocuments($request, $vendorId, $vehicleId);
            $updateData = array_merge($validator->validated(), $documentPaths);

            $vehicle->update($updateData);

            Log::info('Vehicle updated', [
                'vehicle_id' => $vehicleId,
                'vendor_id' => $vendorId
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Vehicle updated successfully',
                'data' => [
                    'vehicle' => $this->formatVehicleData($vehicle->fresh())
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Update vehicle error', [
                'vehicle_id' => $vehicleId ?? null,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update vehicle'
            ], 500);
        }
    }

    // ✅ 5. DELETE VEHICLE
    public function deleteVehicle(Request $request, $vehicleId): JsonResponse
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

            $vehicle = VendorVehicle::where('id', $vehicleId)
                ->where('vendor_id', $vendorId)
                ->first();

            if (!$vehicle) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vehicle not found in your fleet'
                ], 404);
            }

            // Check if vehicle has active bookings
            $activeBookings = $vehicle->bookings()->whereIn('status', ['pending', 'confirmed', 'in_progress'])->count();

            if ($activeBookings > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete vehicle with active bookings. Please complete or cancel all bookings first.'
                ], 400);
            }

            $regNumber = $vehicle->vehicle_registration_number;
            $vehicle->delete();

            Log::info('Vehicle deleted from fleet', [
                'vehicle_id' => $vehicleId,
                'vendor_id' => $vendorId,
                'registration' => $regNumber
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Vehicle deleted successfully from your fleet'
            ]);

        } catch (\Exception $e) {
            Log::error('Delete vehicle error', [
                'vehicle_id' => $vehicleId ?? null,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete vehicle'
            ], 500);
        }
    }

    // ✅ 6. TOGGLE VEHICLE AVAILABILITY
    public function toggleAvailability(Request $request, $vehicleId): JsonResponse
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

            $vehicle = VendorVehicle::where('id', $vehicleId)
                ->where('vendor_id', $vendorId)
                ->first();

            if (!$vehicle) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vehicle not found'
                ], 404);
            }

            if ($vehicle->status !== 'active') {
                return response()->json([
                    'success' => false,
                    'message' => 'Only active vehicles can change availability status'
                ], 400);
            }

            $newStatus = $vehicle->is_available ? false : true;
            $availabilityStatus = $newStatus ? 'available' : 'offline';

            $vehicle->update([
                'is_available' => $newStatus,
                'availability_status' => $availabilityStatus
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Vehicle availability updated',
                'data' => [
                    'vehicle_id' => $vehicle->id,
                    'is_available' => $vehicle->is_available,
                    'availability_status' => $vehicle->availability_status
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update availability'
            ], 500);
        }
    }

    // ✅ HELPER METHODS
    private function handleVehicleDocuments($request, $vendorId, $vehicleId = null): array
    {
        $documentPaths = [];
        $uploadPath = 'vendors/' . $vendorId . '/fleet/' . ($vehicleId ?? 'new_' . time());

        $documents = [
            'vehicle_image' => 'vehicle_',
            'rc_front_image' => 'rc_front_',
            'rc_back_image' => 'rc_back_',
            'insurance_image' => 'insurance_',
            'fitness_certificate' => 'fitness_',
            'permit_image' => 'permit_',
            'dl_image' => 'dl_'
        ];

        foreach ($documents as $fieldName => $prefix) {
            if ($request->hasFile($fieldName)) {
                $file = $request->file($fieldName);
                if ($file->isValid()) {
                    $fileName = $prefix . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                    $path = $file->storeAs($uploadPath, $fileName, 'public');
                    $documentPaths[$fieldName] = $path;

                    Log::info("Document uploaded", [
                        'field' => $fieldName,
                        'path' => $path
                    ]);
                }
            }
        }

        return $documentPaths;
    }

    private function formatVehicleData($vehicle, $detailed = false): array
    {
        $data = [
            'id' => $vehicle->id,
            'vehicle_registration_number' => $vehicle->vehicle_registration_number,
            'vehicle_name' => $vehicle->vehicle_name,
            'vehicle_category' => $vehicle->vehicleCategory ? [
                'id' => $vehicle->vehicleCategory->id,
                'name' => $vehicle->vehicleCategory->category_name,
                'key' => $vehicle->vehicleCategory->category_key
            ] : null,
            'vehicle_model' => $vehicle->vehicleModel ? [
                'id' => $vehicle->vehicleModel->id,
                'name' => $vehicle->vehicleModel->model_name,
                'type_desc' => $vehicle->vehicleModel->vehicle_type_desc,
                'capacity_tons' => $vehicle->vehicleModel->carry_capacity_tons,
                'body_length' => $vehicle->vehicleModel->body_length
            ] : null,
            'status' => $vehicle->status,
            'is_listed' => $vehicle->is_listed,
            'is_available' => $vehicle->is_available,
            'availability_status' => $vehicle->availability_status,
            'rc_verified' => $vehicle->rc_verified,
            'rc_verification_status' => $vehicle->rc_verification_status,
            'dl_verified' => $vehicle->dl_verified,
            'vehicle_image' => $vehicle->vehicle_image ? url('storage/' . $vehicle->vehicle_image) : null,
            'created_at' => $vehicle->created_at,
            'can_accept_bookings' => $vehicle->can_accept_bookings
        ];

        if ($detailed) {
            $data = array_merge($data, [
                'owner_name' => $vehicle->owner_name,
                'rc_number' => $vehicle->rc_number,
                'manufacturing_year' => $vehicle->manufacturing_year,
                'vehicle_color' => $vehicle->vehicle_color,
                'chassis_number' => $vehicle->chassis_number,
                'engine_number' => $vehicle->engine_number,
                'insurance_number' => $vehicle->insurance_number,
                'insurance_expiry' => $vehicle->insurance_expiry,
                'fitness_expiry' => $vehicle->fitness_expiry,
                'permit_expiry' => $vehicle->permit_expiry,
                'dl_number' => $vehicle->dl_number,
                'description' => $vehicle->description,
                'has_gps' => $vehicle->has_gps,
                'completed_trips' => $vehicle->completed_trips,
                'cancelled_trips' => $vehicle->cancelled_trips,
                'average_rating' => $vehicle->average_rating,
                'total_ratings' => $vehicle->total_ratings,
                'current_location' => $vehicle->current_location,
                'documents' => $vehicle->documents,
                'rejection_reason' => $vehicle->rejection_reason,
                'approved_at' => $vehicle->approved_at,
                'listed_at' => $vehicle->listed_at
            ]);
        }

        return $data;
    }
}
