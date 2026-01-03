<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Vendor;
use App\Models\VendorVehicleType;
use App\Services\DocumentVerificationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class VendorVehicleController extends Controller
{
    private $docVerificationService;

    public function __construct()
    {
        $this->docVerificationService = new DocumentVerificationService();
    }

    // ✅ GET VEHICLE FORM DATA
    public function getVehicleFormData(): JsonResponse
    {
        try {
            $vehicleTypes = VendorVehicleType::active()->ordered()->get();

            // ✅ Ye sirf reference ke liye hai - validation mein use nahi hota
            $vehicleOptions = [
                'vehicle_types' => [
                    'Mini Truck',
                    'Pickup 8ft',
                    'Pickup 14ft',
                    'Truck',
                    'Container'
                ],
                'brand_models' => [
                    'Tata Ace',
                    'Mahindra Bolero Pickup',
                    'Ashok Leyland Dost',
                    'Bajaj Maxitruck',
                    'Force Traveller',
                    'Eicher Pro 1049'
                ],
                'lengths' => [
                    ['value' => 8, 'text' => '8 ft'],
                    ['value' => 16, 'text' => '16 ft'],
                    ['value' => 20, 'text' => '20 ft'],
                    ['value' => 22, 'text' => '22 ft'],
                    ['value' => 24, 'text' => '24 ft'],
                    ['value' => 28, 'text' => '28 ft']
                ],
                'tyre_variants' => [
                    ['value' => 5, 'text' => '5 Tyre'],
                    ['value' => 10, 'text' => '10 Tyre'],
                    ['value' => 14, 'text' => '14 Tyre'],
                    ['value' => 18, 'text' => '18 Tyre'],
                    ['value' => 22, 'text' => '22 Tyre']
                ],
                'weight_capacities' => [
                    ['value' => 2, 'text' => '2 Ton'],
                    ['value' => 5, 'text' => '5 Ton'],
                    ['value' => 10, 'text' => '10 Ton'],
                    ['value' => 15, 'text' => '15 Ton'],
                    ['value' => 20, 'text' => '20 Ton'],
                    ['value' => 30, 'text' => '30 Ton'],
                    ['value' => 40, 'text' => '40 Ton'],
                    ['value' => 50, 'text' => '50 Ton']
                ]
            ];

            return response()->json([
                'success' => true,
                'message' => 'Vehicle form data retrieved successfully',
                'data' => [
                    'vehicle_types_db' => $vehicleTypes,
                    'options' => $vehicleOptions,
                    'required_documents' => [
                        'vehicle_image' => 'Vehicle Photo (required)',
                        'vehicle_rc_document' => 'Registration Certificate (required)',
                        'vehicle_insurance_document' => 'Vehicle Insurance (optional)'
                    ],
                    'note' => 'You can enter custom values if your vehicle specs are not in the list'
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Get Vehicle Form Data Error', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve form data: ' . $e->getMessage()
            ], 500);
        }
    }

    // ✅ LIST VENDOR VEHICLE (WITH RC VERIFICATION) - FLEXIBLE VALIDATION
    public function listVehicle(Request $request): JsonResponse
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
            $vendor = Vendor::find($vendorId);

            if (!$vendor || !$vendor->is_completed) {
                return response()->json([
                    'success' => false,
                    'message' => 'Please complete vendor registration first'
                ], 403);
            }

            // ✅ FLEXIBLE VALIDATION - KUCH BHI ALLOW (No strict dropdown validation)
            $validator = Validator::make($request->all(), [
                'vehicle_registration_number' => 'required|string|max:20',
                'vehicle_type' => 'required|string|max:100',
                'vehicle_brand_model' => 'required|string|max:100',
                'vehicle_length' => 'nullable|numeric|min:1|max:100',  // ✅ Any number 1-100
                'vehicle_tyre_count' => 'nullable|integer|min:1|max:100',  // ✅ Any number 1-100
                'weight_capacity' => 'nullable|numeric|min:0.1|max:100',  // ✅ Decimal allowed
                'vehicle_image' => 'required|image|mimes:jpeg,png,jpg|max:5120',
                'vehicle_rc_document' => 'required|mimes:pdf,jpeg,png,jpg|max:5120',
                'vehicle_insurance_document' => 'nullable|mimes:pdf,jpeg,png,jpg|max:5120'
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
                $rcNumber = strtoupper(trim($request->vehicle_registration_number));

                // ✅ VERIFY RC NUMBER
                Log::info('RC Verification Started', [
                    'vendor_id' => $vendorId,
                    'rc_number' => $rcNumber
                ]);

                $rcVerification = $this->docVerificationService->verifyRc($rcNumber, null);

                if (!$rcVerification['success'] || !($rcVerification['verified'] ?? false)) {
                    DB::rollback();

                    return response()->json([
                        'success' => false,
                        'message' => 'RC verification failed. Please provide a valid RC number.',
                        'error_code' => 'RC_VERIFICATION_FAILED',
                        'details' => [
                            'rc_number' => $rcNumber,
                            'verification_message' => $rcVerification['message'] ?? 'Invalid RC number'
                        ]
                    ], 400);
                }

                // ✅ RC VERIFIED - Get verified data
                $verifiedData = $rcVerification['data'];

                Log::info('RC Verified Successfully', [
                    'vendor_id' => $vendorId,
                    'rc_number' => $rcNumber,
                    'owner_name' => $verifiedData['owner_name']
                ]);

                // Handle file uploads
                $documentPaths = $this->handleVehicleDocuments($request, $vendorId);

                // Update vendor with vehicle information + verified RC data
                $vendor->update([
                    'vehicle_registration_number' => $rcNumber,
                    'vehicle_type' => $request->vehicle_type,
                    'vehicle_brand_model' => $request->vehicle_brand_model,
                    'vehicle_length' => $request->vehicle_length,
                    'vehicle_length_unit' => 'ft',
                    'vehicle_tyre_count' => $request->vehicle_tyre_count,
                    'weight_capacity' => $request->weight_capacity,
                    'weight_unit' => 'ton',
                    'vehicle_image' => $documentPaths['vehicle_image'] ?? null,
                    'vehicle_rc_document' => $documentPaths['vehicle_rc_document'] ?? null,
                    'vehicle_insurance_document' => $documentPaths['vehicle_insurance_document'] ?? null,
                    'vehicle_listed' => true,
                    'vehicle_status' => 'approved',  // ✅ Changed: pending → approved
                    'vehicle_listed_at' => now(),
                    'vehicle_approved_at' => now(),  // ✅ Added: Approval timestamp

                    // ✅ RC Verification Data
                    'rc_verified' => true,
                    'rc_verification_date' => now(),
                    'rc_verified_data' => json_encode($verifiedData),
                    'rc_owner_name' => $verifiedData['owner_name'] ?? null
                ]);

                DB::commit();

                Log::info('Vehicle listed successfully', [
                    'vendor_id' => $vendorId,
                    'registration_number' => $rcNumber,
                    'vehicle_type' => $request->vehicle_type,
                    'rc_verified' => true
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Vehicle listed and approved successfully! RC verified.',
                    'data' => [
                        'vehicle' => [
                            'id' => $vendor->id,
                            'registration_number' => $vendor->vehicle_registration_number,
                            'type' => $vendor->vehicle_type,
                            'brand_model' => $vendor->vehicle_brand_model,
                            'specifications' => $vendor->vehicle_full_spec,
                            'status' => $vendor->vehicle_status,
                            'listed_at' => $vendor->vehicle_listed_at,
                            'approved_at' => $vendor->vehicle_approved_at,  // ✅ Added
                            'rc_verified' => true,
                            'rc_owner_name' => $verifiedData['owner_name'],
                            'documents' => [
                                'vehicle_image' => $vendor->vehicle_image ? url('storage/' . $vendor->vehicle_image) : null,
                                'rc_document' => $vendor->vehicle_rc_document ? url('storage/' . $vendor->vehicle_rc_document) : null,
                                'insurance_document' => $vendor->vehicle_insurance_document ? url('storage/' . $vendor->vehicle_insurance_document) : null
                            ]
                        ],
                        'rc_verification' => [
                            'verified' => true,
                            'owner_name' => $verifiedData['owner_name'],
                            'vehicle_category' => $verifiedData['vehicle_category'] ?? null,
                            'maker_model' => $verifiedData['maker_model'] ?? null,
                            'registration_date' => $verifiedData['registration_date'] ?? null
                        ],
                        'next_step' => [
                            'message' => 'Your vehicle is approved! You can now go online.',
                            'action' => 'go_online'
                        ]
                    ]
                ]);

            } catch (\Exception $e) {
                DB::rollback();
                throw $e;
            }
        } catch (\Exception $e) {
            Log::error('List Vehicle Error', [
                'vendor_id' => $vendorId ?? null,
                'error' => $e->getMessage(),
                'line' => $e->getLine()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to list vehicle: ' . $e->getMessage()
            ], 500);
        }
    }

    // ✅ HANDLE VEHICLE DOCUMENT UPLOADS
    private function handleVehicleDocuments($request, $vendorId): array
    {
        $documentPaths = [];
        $uploadPath = 'vendor_vehicles/' . $vendorId;

        try {
            // Vehicle Image
            if ($request->hasFile('vehicle_image')) {
                $file = $request->file('vehicle_image');
                if ($file->isValid()) {
                    $fileName = 'vehicle_image_' . time() . '.' . $file->getClientOriginalExtension();
                    $path = $file->storeAs($uploadPath, $fileName, 'public');
                    $documentPaths['vehicle_image'] = $path;
                    Log::info('Vehicle image uploaded', ['path' => $path]);
                }
            }

            // RC Document
            if ($request->hasFile('vehicle_rc_document')) {
                $file = $request->file('vehicle_rc_document');
                if ($file->isValid()) {
                    $fileName = 'rc_document_' . time() . '.' . $file->getClientOriginalExtension();
                    $path = $file->storeAs($uploadPath, $fileName, 'public');
                    $documentPaths['vehicle_rc_document'] = $path;
                    Log::info('RC document uploaded', ['path' => $path]);
                }
            }

            // Insurance Document
            if ($request->hasFile('vehicle_insurance_document')) {
                $file = $request->file('vehicle_insurance_document');
                if ($file->isValid()) {
                    $fileName = 'insurance_document_' . time() . '.' . $file->getClientOriginalExtension();
                    $path = $file->storeAs($uploadPath, $fileName, 'public');
                    $documentPaths['vehicle_insurance_document'] = $path;
                    Log::info('Insurance document uploaded', ['path' => $path]);
                }
            }

            return $documentPaths;
        } catch (\Exception $e) {
            Log::error('Vehicle document upload error', [
                'vendor_id' => $vendorId,
                'error' => $e->getMessage()
            ]);
            throw new \Exception('Document upload failed: ' . $e->getMessage());
        }
    }

    // ✅ GET VENDOR VEHICLE STATUS
    public function getVehicleStatus(Request $request): JsonResponse
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
            $vendor = Vendor::with('activePlanSubscription')->find($vendorId);

            if (!$vendor) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vendor not found'
                ], 404);
            }

            $vehicleStatus = [
                'has_vehicle' => !empty($vendor->vehicle_registration_number),
                'vehicle_status' => $vendor->vehicle_status,
                'is_listed' => $vendor->vehicle_listed,
                'needs_plan' => !$vendor->has_active_plan,
                'rc_verified' => $vendor->rc_verified ?? false,
                'vehicle_info' => null
            ];

            if ($vehicleStatus['has_vehicle']) {
                $vehicleStatus['vehicle_info'] = [
                    'registration_number' => $vendor->vehicle_registration_number,
                    'type' => $vendor->vehicle_type,
                    'brand_model' => $vendor->vehicle_brand_model,
                    'specifications' => $vendor->vehicle_full_spec,
                    'status' => $vendor->vehicle_status,
                    'rc_verified' => $vendor->rc_verified ?? false,
                    'rc_owner_name' => $vendor->rc_owner_name,
                    'listed_at' => $vendor->vehicle_listed_at,
                    'approved_at' => $vendor->vehicle_approved_at,
                    'rejection_reason' => $vendor->vehicle_rejection_reason,
                    'documents' => [
                        'vehicle_image' => $vendor->vehicle_image ? url('storage/' . $vendor->vehicle_image) : null,
                        'rc_document' => $vendor->vehicle_rc_document ? url('storage/' . $vendor->vehicle_rc_document) : null,
                        'insurance_document' => $vendor->vehicle_insurance_document ? url('storage/' . $vendor->vehicle_insurance_document) : null
                    ]
                ];
            }

            $planStatus = [
                'has_active_plan' => $vendor->has_active_plan,
                'current_plan' => null
            ];

            if ($vendor->activePlanSubscription) {
                $planStatus['current_plan'] = [
                    'id' => $vendor->activePlanSubscription->id,
                    'plan_name' => $vendor->activePlanSubscription->plan_name,
                    'expires_at' => $vendor->activePlanSubscription->expires_at,
                    'days_remaining' => $vendor->activePlanSubscription->days_remaining,
                    'features' => $vendor->activePlanSubscription->plan_features
                ];
            }

            return response()->json([
                'success' => true,
                'message' => 'Vehicle status retrieved successfully',
                'data' => [
                    'vehicle_status' => $vehicleStatus,
                    'plan_status' => $planStatus,
                    'next_actions' => $this->getNextActions($vendor)
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Get Vehicle Status Error', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve status: ' . $e->getMessage()
            ], 500);
        }
    }

    // ✅ GET NEXT ACTIONS FOR VENDOR
    private function getNextActions($vendor): array
    {
        $actions = [];

        if (!$vendor->vehicle_registration_number) {
            $actions[] = [
                'action' => 'list_vehicle',
                'title' => 'List Your Vehicle',
                'description' => 'Add your vehicle details to start getting bookings',
                'priority' => 'high'
            ];
        } elseif ($vendor->vehicle_status === 'pending') {
            $actions[] = [
                'action' => 'wait_approval',
                'title' => 'Vehicle Under Review',
                'description' => 'Your vehicle is being reviewed. You will be notified once approved.',
                'priority' => 'medium'
            ];
        } elseif ($vendor->vehicle_status === 'rejected') {
            $actions[] = [
                'action' => 'update_vehicle',
                'title' => 'Update Vehicle Information',
                'description' => 'Your vehicle was rejected. Please update the information.',
                'priority' => 'high'
            ];
        }

        if (!$vendor->has_active_plan && $vendor->vehicle_status === 'active') {
            $actions[] = [
                'action' => 'choose_plan',
                'title' => 'Choose a Plan',
                'description' => 'Select a plan to activate your vehicle listing',
                'priority' => 'high'
            ];
        }

        if ($vendor->has_active_plan && $vendor->vehicle_status === 'active') {
            $actions[] = [
                'action' => 'start_earning',
                'title' => 'Start Earning',
                'description' => 'Your vehicle is active. Start accepting bookings!',
                'priority' => 'low'
            ];
        }

        return $actions;
    }
}
