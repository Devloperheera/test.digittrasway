<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Vendor;
use App\Models\UserType;
use App\Models\VehicleCategory; // ✅ ADD
use App\Models\VehicleModel; // ✅ ADD
use App\Services\SmsService;
use App\Services\DocumentVerificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class VendorAuthController extends Controller
{
    protected $smsService;
    protected $docVerificationService;

    public function __construct()
    {
        $this->smsService = new SmsService();
        $this->docVerificationService = new DocumentVerificationService();
    }

    // ✅ 1. CHECK VENDOR STATUS
    public function checkVendorStatus(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'contact_number' => 'required|string|digits:10|regex:/^[6-9]\d{9}$/'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid contact number format',
                    'errors' => $validator->errors()
                ], 400);
            }

            $contactNumber = $request->contact_number;
            $vendor = Vendor::with(['userType', 'vehicleCategory', 'vehicleModel'])
                ->where('contact_number', $contactNumber)->first();

            if (!$vendor) {
                return response()->json([
                    'success' => true,
                    'message' => 'Vendor not found. Please register first.',
                    'status' => 'not_registered',
                    'action' => 'send_otp',
                    'redirect' => 'vendor_registration_page'
                ]);
            }

            if ($vendor->is_verified && !$vendor->is_completed) {
                $token = base64_encode($vendor->id . ':' . time() . ':' . $contactNumber);

                return response()->json([
                    'success' => true,
                    'message' => 'Please redirect to complete vendor registration page',
                    'status' => 'verified_incomplete',
                    'action' => 'redirect_to_complete_registration',
                    'redirect' => 'vendor_complete_registration_page',
                    'access_token' => $token,
                    'token_type' => 'bearer',
                    'expires_in' => 3600,
                    'vendor' => [
                        'id' => $vendor->id,
                        'contact_number' => $vendor->contact_number,
                        'is_verified' => $vendor->is_verified,
                        'is_completed' => $vendor->is_completed,
                        'user_type' => $vendor->userType ? [
                            'type_key' => $vendor->userType->type_key,
                            'title' => $vendor->userType->title
                        ] : null
                    ]
                ]);
            }

            if ($vendor->is_verified && $vendor->is_completed) {
                return response()->json([
                    'success' => true,
                    'message' => 'Vendor already registered. Please login.',
                    'status' => 'already_completed',
                    'action' => 'redirect_to_login',
                    'redirect' => 'vendor_login_page',
                    'vendor' => [
                        'id' => $vendor->id,
                        'contact_number' => $vendor->contact_number,
                        'name' => $vendor->name,
                        'email' => $vendor->email,
                        'is_verified' => $vendor->is_verified,
                        'is_completed' => $vendor->is_completed,
                        'user_type' => $vendor->userType ? [
                            'type_key' => $vendor->userType->type_key,
                            'title' => $vendor->userType->title
                        ] : null
                    ]
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Vendor found but not verified. Please verify OTP.',
                'status' => 'not_verified',
                'action' => 'send_otp',
                'redirect' => 'vendor_otp_verification_page',
                'vendor' => [
                    'id' => $vendor->id,
                    'contact_number' => $vendor->contact_number,
                    'is_verified' => $vendor->is_verified,
                    'is_completed' => $vendor->is_completed,
                    'user_type' => $vendor->userType ? [
                        'type_key' => $vendor->userType->type_key,
                        'title' => $vendor->userType->title
                    ] : null
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Check Vendor Status Error', [
                'contact_number' => $contactNumber ?? null,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to check vendor status: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * ✅ FIXED: Registration Send OTP - Allow Incomplete Registrations
     */

    public function sendOtp(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'user_type_key' => 'required|string|in:fleet_owner,professional_driver',
                'contact_number' => 'required|string|digits:10|regex:/^[6-9]\d{9}$/'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 400);
            }

            $userTypeKey = $request->user_type_key;
            $contactNumber = $request->contact_number;

            $userType = UserType::where('type_key', $userTypeKey)->first();

            if (!$userType) {
                return response()->json([
                    'success' => false,
                    'message' => 'Selected user type not configured in system'
                ], 500);
            }

            $vendor = Vendor::where('contact_number', $contactNumber)->first();

            // ✅ CASE 1: Already Verified & Completed - Redirect to Login
            if ($vendor && $vendor->is_verified && $vendor->is_completed) {
                return response()->json([
                    'success' => false,
                    'message' => 'Mobile number already registered. Please proceed to login.',
                    'status' => 'already_completed',
                    'error_code' => 'REGISTRATION_COMPLETED',
                    'data' => [
                        'vendor_id' => $vendor->vendor_id,
                        'contact_number' => $contactNumber,
                        'is_verified' => true,
                        'is_completed' => true,
                        'user_type' => $vendor->userType ? [
                            'type_key' => $vendor->userType->type_key,
                            'title' => $vendor->userType->title
                        ] : null,
                        'redirect_to' => 'login' // ✅ Tell app to redirect
                    ]
                ], 409);
            }

            // ✅ CASE 2: Verified but NOT Completed - Allow Re-registration
            if ($vendor && $vendor->is_verified && !$vendor->is_completed) {
                Log::info('Incomplete registration found - Allowing OTP resend', [
                    'vendor_id' => $vendor->vendor_id,
                    'is_verified' => $vendor->is_verified,
                    'is_completed' => $vendor->is_completed
                ]);

                // Update user type if different
                if ($vendor->user_type_id !== $userType->id) {
                    $vendor->update(['user_type_id' => $userType->id]);
                }

                // Generate new OTP for incomplete registration
                $otp = $vendor->generateOtp();

                try {
                    $this->smsService->sendOtp($contactNumber, $otp, 'registration_continue');
                } catch (\Exception $smsError) {
                    Log::error('SMS failed', [
                        'vendor_id' => $vendor->vendor_id,
                        'error' => $smsError->getMessage()
                    ]);
                }

                return response()->json([
                    'success' => true,
                    'message' => 'OTP sent successfully. Please complete your registration.',
                    'data' => [
                        'vendor_id' => $vendor->vendor_id,
                        'id_prefix' => substr($vendor->vendor_id, 0, 3),
                        'contact_number' => $contactNumber,
                        'contact_number_masked' => substr($contactNumber, 0, 2) . 'XXXXXX' . substr($contactNumber, -2),
                        'user_type' => [
                            'id' => $userType->id,
                            'type_key' => $userType->type_key,
                            'title' => $userType->title,
                            'subtitle' => $userType->subtitle,
                            'icon' => $userType->icon
                        ],
                        'otp_sent' => true,
                        'otp' => env('APP_DEBUG') ? $otp : null,
                        'expires_in_minutes' => 10,
                        'expires_at' => $vendor->otp_expires_at->format('Y-m-d H:i:s'),
                        'is_verified' => true,
                        'is_completed' => false,
                        'status' => 'incomplete_registration',
                        'message_detail' => 'Your mobile is verified but registration incomplete. Please complete your profile.',
                        'next_step' => 'verify_otp_and_complete_registration'
                    ]
                ]);
            }

            // ✅ CASE 3: New Vendor OR Not Verified Yet
            if (!$vendor) {
                $vendor = Vendor::create([
                    'contact_number' => $contactNumber,
                    'user_type_id' => $userType->id
                ]);

                Log::info('New vendor created', [
                    'vendor_id' => $vendor->vendor_id,
                    'user_type' => $userTypeKey
                ]);
            } else {
                // Update user type if different
                if ($vendor->user_type_id !== $userType->id) {
                    $vendor->update(['user_type_id' => $userType->id]);
                }
            }

            // Rate limiting check
            if (!$vendor->canSendOtp()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Please wait before requesting another OTP',
                    'error_code' => 'OTP_RATE_LIMIT',
                    'data' => [
                        'vendor_id' => $vendor->vendor_id,
                        'wait_seconds' => $vendor->getResendWaitTime()
                    ]
                ], 429);
            }

            // Generate and send OTP
            $otp = $vendor->generateOtp();

            try {
                $this->smsService->sendOtp($contactNumber, $otp, 'registration');
            } catch (\Exception $smsError) {
                Log::error('SMS failed', [
                    'vendor_id' => $vendor->vendor_id,
                    'error' => $smsError->getMessage()
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'OTP sent successfully to ' . $contactNumber,
                'data' => [
                    'vendor_id' => $vendor->vendor_id,
                    'id_prefix' => substr($vendor->vendor_id, 0, 3),
                    'contact_number' => $contactNumber,
                    'contact_number_masked' => substr($contactNumber, 0, 2) . 'XXXXXX' . substr($contactNumber, -2),
                    'user_type' => [
                        'id' => $userType->id,
                        'type_key' => $userType->type_key,
                        'title' => $userType->title,
                        'subtitle' => $userType->subtitle,
                        'icon' => $userType->icon
                    ],
                    'otp_sent' => true,
                    'otp' => env('APP_DEBUG') ? $otp : null,
                    'expires_in_minutes' => 10,
                    'expires_at' => $vendor->otp_expires_at->format('Y-m-d H:i:s'),
                    'resend_available_after_seconds' => 60,
                    'is_new_registration' => $vendor->wasRecentlyCreated,
                    'status' => 'otp_sent',
                    'next_step' => 'verify_otp'
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Registration Send OTP Error', [
                'user_type_key' => $request->user_type_key ?? null,
                'contact_number' => $request->contact_number ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to send OTP: ' . $e->getMessage(),
                'error_code' => 'OTP_SEND_FAILED'
            ], 500);
        }
    }


    // ✅ 3. REGISTRATION VERIFY OTP - USING CONTACT NUMBER
    public function verifyOtp(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'contact_number' => 'required|string|digits:10|regex:/^[6-9]\d{9}$/',
                'otp' => 'required|string|digits:4'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid input format',
                    'errors' => $validator->errors()
                ], 400);
            }

            $contactNumber = $request->contact_number;
            $otpInput = $request->otp;

            Log::info('Registration OTP verification attempt', [
                'contact_number' => $contactNumber
            ]);

            $vendor = Vendor::with('userType')
                ->where('contact_number', $contactNumber)
                ->first();

            if (!$vendor) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vendor not found. Please send OTP first.',
                    'status' => 'vendor_not_found'
                ], 404);
            }

            if ($vendor->is_verified) {
                if ($vendor->is_completed) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Mobile number already verified and registered. Please proceed to login.',
                        'status' => 'already_verified_completed',
                        'action_required' => 'login'
                    ], 409);
                } else {
                    $token = base64_encode($vendor->id . ':' . time() . ':' . $contactNumber);
                    return response()->json([
                        'success' => true,
                        'message' => 'Mobile already verified. Please complete registration.',
                        'status' => 'verified_incomplete',
                        'data' => [
                            'access_token' => $token,
                            'token_type' => 'bearer',
                            'contact_number' => $contactNumber,
                            'user_type' => [
                                'type_key' => $vendor->userType->type_key,
                                'title' => $vendor->userType->title
                            ],
                            'action_required' => 'complete_registration'
                        ]
                    ]);
                }
            }

            if (!$vendor->isOtpValid($otpInput)) {
                $vendor->incrementOtpAttempts();
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid or expired OTP. Please check and try again.',
                    'status' => 'invalid_otp',
                    'data' => [
                        'attempts_left' => max(0, 3 - $vendor->otp_attempts),
                        'can_resend' => true
                    ]
                ], 400);
            }

            $vendor->clearRegistrationOtp();
            $token = base64_encode($vendor->id . ':' . time() . ':' . $contactNumber);

            Log::info('Registration OTP verified successfully', [
                'vendor_id' => $vendor->id,
                'contact_number' => $contactNumber,
                'user_type' => $vendor->userType->type_key
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Mobile number verified successfully. Please complete registration.',
                'status' => 'otp_verified',
                'data' => [
                    'vendor' => [
                        'id' => $vendor->id,
                        'contact_number' => $vendor->contact_number,
                        'is_verified' => $vendor->is_verified,
                        'is_completed' => $vendor->is_completed
                    ],
                    'user_type' => [
                        'id' => $vendor->userType->id,
                        'type_key' => $vendor->userType->type_key,
                        'title' => $vendor->userType->title,
                        'subtitle' => $vendor->userType->subtitle,
                        'icon' => $vendor->userType->icon
                    ],
                    'access_token' => $token,
                    'token_type' => 'bearer',
                    'expires_in' => 3600,
                    'action_required' => 'complete_registration'
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Registration Verify OTP Error', [
                'contact_number' => $request->contact_number ?? null,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'OTP verification failed: ' . $e->getMessage(),
                'error_code' => 'OTP_VERIFICATION_FAILED'
            ], 500);
        }
    }

    // ✅ 4. REGISTRATION RESEND OTP
    public function resendOtp(Request $request): JsonResponse
    {
        try {
            $contactNumber = $request->input('contact_number')
                ?? $request->json('contact_number')
                ?? $request->get('contact_number');

            $validator = Validator::make(['contact_number' => $contactNumber], [
                'contact_number' => [
                    'required',
                    'string',
                    'digits:10',
                    'regex:/^[6-9]\d{9}$/'
                ]
            ], [
                'contact_number.required' => 'Mobile number is required',
                'contact_number.digits' => 'Mobile number must be exactly 10 digits',
                'contact_number.regex' => 'Mobile number must start with 6, 7, 8, or 9',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Please provide a valid 10-digit mobile number',
                    'errors' => $validator->errors()
                ], 400);
            }

            $vendor = Vendor::with('userType')
                ->where('contact_number', $contactNumber)
                ->first();

            if (!$vendor) {
                return response()->json([
                    'success' => false,
                    'message' => 'Mobile number not registered. Please register first.',
                    'status' => 'vendor_not_found',
                    'action_required' => 'registration'
                ], 404);
            }

            if ($vendor->is_verified && $vendor->is_completed) {
                return response()->json([
                    'success' => false,
                    'message' => 'Mobile number already verified and registered. Please proceed to login.',
                    'status' => 'already_completed',
                    'action_required' => 'login'
                ], 409);
            }

            if (!$vendor->canSendOtp()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Please wait before requesting another OTP',
                    'error_code' => 'OTP_RATE_LIMIT',
                    'data' => [
                        'wait_seconds' => $vendor->getResendWaitTime()
                    ]
                ], 429);
            }

            $otp = $vendor->generateOtp();
            $vendor->incrementResendCount();

            try {
                $smsResult = $this->smsService->sendOtp($contactNumber, $otp, 'registration_resend');
                Log::info('Resend OTP SMS sent', [
                    'vendor_id' => $vendor->id,
                    'resend_count' => $vendor->otp_resend_count
                ]);
            } catch (\Exception $smsError) {
                Log::error('Resend SMS failed', [
                    'vendor_id' => $vendor->id,
                    'error' => $smsError->getMessage()
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'OTP resent successfully to ' . $contactNumber,
                'data' => [
                    'contact_number' => $contactNumber,
                    'contact_number_masked' => substr($contactNumber, 0, 2) . 'XXXXXX' . substr($contactNumber, -2),
                    'user_type' => $vendor->userType ? [
                        'type_key' => $vendor->userType->type_key,
                        'title' => $vendor->userType->title
                    ] : null,
                    'otp' => env('APP_DEBUG') ? $otp : null,
                    'expires_in_minutes' => 10,
                    'expires_at' => $vendor->otp_expires_at->format('Y-m-d H:i:s'),
                    'resend_count' => $vendor->otp_resend_count,
                    'resend_available_after_seconds' => 60,
                    'status' => 'otp_resent'
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Resend OTP Error', [
                'error' => $e->getMessage(),
                'request_data' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to resend OTP: ' . $e->getMessage(),
                'error_code' => 'RESEND_OTP_ERROR'
            ], 500);
        }
    }

/**
 * ✅ 5. COMPLETE REGISTRATION (RC & DL: API OR MANUAL - NO ERRORS)
 */
public function completeRegistration(Request $request)
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
        $vendor = Vendor::with(['userType', 'vehicleCategory', 'vehicleModel'])->find($vendorId);

        if (!$vendor || !$vendor->is_verified) {
            return response()->json([
                'success' => false,
                'message' => 'Vendor not verified. Please verify OTP first.'
            ], 403);
        }

        if ($vendor->is_completed) {
            return response()->json([
                'success' => false,
                'message' => 'Vendor registration already completed',
                'status' => 'already_completed',
                'data' => [
                    'vendor' => $this->formatVendorData($vendor)
                ]
            ], 409);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|unique:vendors,email,' . $vendorId,
            'dob' => 'nullable|date|before:today',
            'gender' => 'nullable|in:male,female,other',
            'emergency_contact' => 'nullable|string|digits:10',

            'vehicle_category_id' => 'nullable|integer|exists:vehicle_categories,id',
            'vehicle_model_id' => 'nullable|integer|exists:vehicle_models,id',

            'aadhar_number' => 'nullable|string|digits:12',
            'aadhar_manual' => 'nullable|string',

            'pan_number' => 'nullable|string|max:10',

            'rc_number' => 'nullable|string|max:20',
            'rc_manual' => 'nullable|string',

            'dl_number' => 'nullable|string|max:20',
            'dl_manual' => 'nullable|string',

            'full_address' => 'nullable|string|max:500',
            'state' => 'nullable|string|max:100',
            'city' => 'nullable|string|max:100',
            'pincode' => 'nullable|string|digits:6',
            'country' => 'nullable|string|max:100',

            'bank_name' => 'nullable|string|max:100',
            'account_number' => 'nullable|string|max:20',
            'ifsc' => 'nullable|string|max:11',

            'password' => 'nullable|string|min:8',

            'aadhar_front' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'aadhar_back' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'pan_image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'rc_image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'dl_image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',

            'declaration' => 'nullable|boolean',
            'same_address' => 'nullable|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 400);
        }

        if ($request->vehicle_model_id && $request->vehicle_category_id) {
            $vehicleModel = VehicleModel::where('id', $request->vehicle_model_id)
                ->where('category_id', $request->vehicle_category_id)
                ->first();

            if (!$vehicleModel) {
                return response()->json([
                    'success' => false,
                    'message' => 'Selected vehicle model does not belong to the chosen category'
                ], 400);
            }
        }

        DB::beginTransaction();

        try {
            $verificationResults = [];

            // ✅ 1. AADHAAR VERIFICATION
            if ($request->filled('aadhar_number')) {
                $isManual = $request->filled('aadhar_manual') && $request->aadhar_manual == 'true';

                if ($isManual) {
                    $verificationResults['aadhar_manual'] = json_encode([
                        'manual' => true,
                        'aadhar_number' => $request->aadhar_number,
                        'marked_at' => now()
                    ]);
                } else {
                    try {
                        $aadhaarVerification = $this->docVerificationService->verifyAadhaar(
                            $request->aadhar_number,
                            $request->name
                        );
                        $verificationResults['aadhaar'] = $aadhaarVerification;
                        $verificationResults['aadhar_manual'] = json_encode(['manual' => false]);
                    } catch (\Exception $e) {
                        $verificationResults['aadhar_manual'] = json_encode([
                            'manual' => true,
                            'aadhar_number' => $request->aadhar_number,
                            'reason' => 'API failed',
                            'marked_at' => now()
                        ]);
                    }
                }
            }

            // ✅ 2. PAN VERIFICATION
            if ($request->filled('pan_number')) {
                try {
                    $panVerification = $this->docVerificationService->verifyPan(
                        $request->pan_number,
                        $request->name
                    );
                    $verificationResults['pan'] = $panVerification;
                } catch (\Exception $e) {
                    Log::error('PAN verification failed', ['error' => $e->getMessage()]);
                }
            }

            // ✅ 3. RC VERIFICATION (COMPLETELY SAFE - NO ERRORS)
            if ($request->filled('rc_number')) {
                $isManual = $request->filled('rc_manual') && $request->rc_manual == 'true';

                if ($isManual) {
                    // MANUAL MODE - DIRECT SAVE
                    Log::info('RC: Manual mode selected');
                    $verificationResults['rc_manual'] = json_encode([
                        'manual' => true,
                        'rc_number' => $request->rc_number,
                        'marked_at' => now()
                    ]);
                    $verificationResults['rc_verified'] = false;
                } else {
                    // TRY API - IF FAILS, AUTO MANUAL
                    $rcVerified = false;
                    try {
                        // Check if verification service and method exist
                        if (isset($this->docVerificationService) &&
                            (method_exists($this->docVerificationService, 'verifyRcV2') ||
                             method_exists($this->docVerificationService, 'verifyRc'))) {

                            if (method_exists($this->docVerificationService, 'verifyRcV2')) {
                                $rcVerification = $this->docVerificationService->verifyRcV2(
                                    $request->rc_number,
                                    $request->name
                                );
                            } else {
                                $rcVerification = $this->docVerificationService->verifyRc(
                                    $request->rc_number,
                                    $request->name
                                );
                            }

                            if ($rcVerification['success'] && ($rcVerification['verified'] ?? false)) {
                                $verificationResults['rc'] = $rcVerification;
                                if (isset($rcVerification['data'])) {
                                    $verificationResults['rc_verified_data'] = json_encode($rcVerification['data']);
                                    $verificationResults['rc_verification_date'] = now();
                                    $verificationResults['rc_verified'] = true;
                                    $verificationResults['rc_manual'] = json_encode(['manual' => false]);
                                }
                                $rcVerified = true;
                                Log::info('RC: API verification successful');
                            }
                        }
                    } catch (\Exception $e) {
                        Log::info('RC: API not available or failed, using manual mode');
                    }

                    // If API didn't verify, store manually
                    if (!$rcVerified) {
                        $verificationResults['rc_manual'] = json_encode([
                            'manual' => true,
                            'rc_number' => $request->rc_number,
                            'reason' => 'API verification not available',
                            'marked_at' => now()
                        ]);
                        $verificationResults['rc_verified'] = false;
                        Log::info('RC: Stored manually');
                    }
                }
            }

            // ✅ 4. DL VERIFICATION (COMPLETELY SAFE - NO ERRORS)
            if ($request->filled('dl_number') && $request->filled('dob')) {
                $isManual = $request->filled('dl_manual') && $request->dl_manual == 'true';

                if ($isManual) {
                    // MANUAL MODE - DIRECT SAVE
                    Log::info('DL: Manual mode selected');
                    $verificationResults['dl_manual'] = json_encode([
                        'manual' => true,
                        'dl_number' => $request->dl_number,
                        'dob' => $request->dob,
                        'marked_at' => now()
                    ]);
                    $verificationResults['dl_verified'] = false;
                } else {
                    // TRY API - IF FAILS, AUTO MANUAL
                    $dlVerified = false;
                    try {
                        if (isset($this->docVerificationService) &&
                            method_exists($this->docVerificationService, 'verifyDl')) {

                            $dlVerification = $this->docVerificationService->verifyDl(
                                $request->dl_number,
                                $request->dob,
                                $request->name
                            );

                            if ($dlVerification['success'] && ($dlVerification['verified'] ?? false)) {
                                $verificationResults['dl'] = $dlVerification;
                                $verificationResults['dl_verified_data'] = json_encode($dlVerification['data']);
                                $verificationResults['dl_verification_date'] = now();
                                $verificationResults['dl_verified'] = true;
                                $verificationResults['dl_manual'] = json_encode(['manual' => false]);
                                $dlVerified = true;
                                Log::info('DL: API verification successful');
                            }
                        }
                    } catch (\Exception $e) {
                        Log::info('DL: API not available or failed, using manual mode');
                    }

                    // If API didn't verify, store manually
                    if (!$dlVerified) {
                        $verificationResults['dl_manual'] = json_encode([
                            'manual' => true,
                            'dl_number' => $request->dl_number,
                            'dob' => $request->dob,
                            'reason' => 'API verification not available',
                            'marked_at' => now()
                        ]);
                        $verificationResults['dl_verified'] = false;
                        Log::info('DL: Stored manually');
                    }
                }
            }

            $documentPaths = $this->handleDocumentUploads($request, $vendor->id);
            $validatedData = $validator->validated();

            if (!empty($validatedData['password'])) {
                $validatedData['password'] = Hash::make($validatedData['password']);
            } else {
                unset($validatedData['password']);
            }

            if (isset($validatedData['declaration'])) {
                $validatedData['declaration'] = filter_var($validatedData['declaration'], FILTER_VALIDATE_BOOLEAN);
            }
            if (isset($validatedData['same_address'])) {
                $validatedData['same_address'] = filter_var($validatedData['same_address'], FILTER_VALIDATE_BOOLEAN);
            }

            $updateData = array_merge(
                $validatedData,
                $documentPaths ?? [],
                $verificationResults
            );
            $updateData['is_completed'] = true;

            $vendor->update($updateData);
            $vendor->refresh();

            DB::commit();

            Log::info('Vendor Registration Completed Successfully');

            return response()->json([
                'success' => true,
                'message' => 'Registration completed successfully',
                'status' => 'registration_completed',
                'data' => [
                    'vendor' => $this->formatVendorData($vendor),
                    'verification_summary' => [
                        'rc_verified' => $vendor->rc_verified ?? false,
                        'rc_manual' => $vendor->rc_manual ? json_decode($vendor->rc_manual) : null,
                        'dl_verified' => $vendor->dl_verified ?? false,
                        'dl_manual' => $vendor->dl_manual ? json_decode($vendor->dl_manual) : null,
                        'aadhar_manual' => $vendor->aadhar_manual ? json_decode($vendor->aadhar_manual) : null
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    } catch (\Exception $e) {
        Log::error('Complete Registration Error', [
            'vendor_id' => $vendorId ?? null,
            'error' => $e->getMessage(),
            'line' => $e->getLine()
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Registration failed: ' . $e->getMessage(),
            'error_code' => 'REGISTRATION_ERROR'
        ], 500);
    }
}














    // ✅ 6. LOGIN SEND OTP
    public function loginSendOtp(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'contact_number' => 'required|string|digits:10|regex:/^[6-9]\d{9}$/'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid mobile number format',
                    'errors' => $validator->errors()
                ], 400);
            }

            $contactNumber = $request->contact_number;

            Log::info('Vendor login OTP request', [
                'contact_number' => $contactNumber
            ]);

            $vendor = Vendor::with('userType')->where('contact_number', $contactNumber)->first();

            if (!$vendor) {
                return response()->json([
                    'success' => false,
                    'message' => 'Mobile number not registered. Please register first.',
                    'error_code' => 'VENDOR_NOT_FOUND',
                    'action_required' => 'registration'
                ], 404);
            }

            if (!$vendor->isRegistrationComplete()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Please complete registration and verification first.',
                    'error_code' => 'REGISTRATION_INCOMPLETE',
                    'data' => [
                        'vendor_status' => [
                            'is_verified' => $vendor->is_verified,
                            'is_completed' => $vendor->is_completed
                        ],
                        'action_required' => !$vendor->is_verified ? 'verify_otp' : 'complete_registration'
                    ]
                ], 403);
            }

            try {
                $otp = $vendor->generateLoginOtp();
                $smsResponse = $this->smsService->sendOtp($contactNumber, $otp, 'login');

                Log::info('Vendor login OTP sent successfully', [
                    'vendor_id' => $vendor->id,
                    'otp' => $otp
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Login OTP sent successfully',
                    'data' => [
                        'contact_number' => $contactNumber,
                        'contact_number_masked' => substr($contactNumber, 0, 2) . 'XXXXXX' . substr($contactNumber, -2),
                        'user_type' => [
                            'type_key' => $vendor->userType->type_key,
                            'title' => $vendor->userType->title
                        ],
                        'otp_expires_in' => 600,
                        'otp_expires_at' => $vendor->login_otp_expires_at->format('Y-m-d H:i:s'),
                        'otp' => env('APP_DEBUG') ? $otp : null
                    ]
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to generate login OTP', [
                    'vendor_id' => $vendor->id,
                    'error' => $e->getMessage()
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Failed to generate login OTP: ' . $e->getMessage(),
                    'error_code' => 'OTP_GENERATION_FAILED'
                ], 500);
            }
        } catch (\Exception $e) {
            Log::error('Vendor Login Send OTP Error', [
                'error' => $e->getMessage(),
                'contact_number' => $request->contact_number ?? 'unknown'
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to send login OTP: ' . $e->getMessage(),
                'error_code' => 'LOGIN_OTP_ERROR'
            ], 500);
        }
    }

    // ✅ 7. LOGIN VERIFY OTP
    public function loginVerifyOtp(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'contact_number' => 'required|string|digits:10|regex:/^[6-9]\d{9}$/',
                'otp' => 'required|string|digits:4'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid input data',
                    'errors' => $validator->errors()
                ], 400);
            }

            $contactNumber = $request->contact_number;
            $inputOtp = $request->otp;

            Log::info('Vendor login OTP verification attempt', [
                'contact_number' => $contactNumber
            ]);

            $vendor = Vendor::with(['userType', 'vehicleCategory', 'vehicleModel'])
                ->where('contact_number', $contactNumber)->first();

            if (!$vendor) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vendor not found',
                    'error_code' => 'VENDOR_NOT_FOUND'
                ], 404);
            }

            if ($vendor->verifyLoginOtp($inputOtp)) {
                $token = base64_encode($vendor->id . ':' . time() . ':' . $contactNumber . ':login');

                Log::info('Vendor login successful', [
                    'vendor_id' => $vendor->id,
                    'login_count' => $vendor->login_count,
                    'user_type' => $vendor->userType->type_key
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Login successful',
                    'status' => 'login_successful',
                    'data' => [
                        'access_token' => $token,
                        'token_type' => 'Bearer',
                        'expires_in' => 3600,
                        'vendor' => $this->formatVendorData($vendor)
                    ]
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired OTP. Please try again.',
                'error_code' => 'INVALID_OTP'
            ], 400);
        } catch (\Exception $e) {
            Log::error('Vendor Login Verify OTP Error', [
                'error' => $e->getMessage(),
                'contact_number' => $request->contact_number ?? 'unknown'
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Login verification failed: ' . $e->getMessage(),
                'error_code' => 'LOGIN_VERIFICATION_ERROR'
            ], 500);
        }
    }

    // ✅ 8. LOGIN RESEND OTP
    public function loginResendOtp(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'contact_number' => 'required|string|digits:10|regex:/^[6-9]\d{9}$/'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid mobile number format',
                    'errors' => $validator->errors()
                ], 400);
            }

            $contactNumber = $request->contact_number;
            $vendor = Vendor::with('userType')->where('contact_number', $contactNumber)->first();

            if (!$vendor) {
                return response()->json([
                    'success' => false,
                    'message' => 'Mobile number not registered. Please register first.',
                    'error_code' => 'VENDOR_NOT_FOUND',
                    'action_required' => 'registration'
                ], 404);
            }

            if (!$vendor->isRegistrationComplete()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Please complete registration and verification first.',
                    'error_code' => 'REGISTRATION_INCOMPLETE',
                    'action_required' => !$vendor->is_verified ? 'verify_otp' : 'complete_registration'
                ], 403);
            }

            try {
                $otp = $vendor->generateLoginOtp();
                $smsResponse = $this->smsService->sendOtp($contactNumber, $otp, 'login_resend');

                Log::info('Vendor login OTP resent successfully', [
                    'vendor_id' => $vendor->id,
                    'otp' => $otp
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Login OTP resent successfully',
                    'data' => [
                        'contact_number' => $contactNumber,
                        'contact_number_masked' => substr($contactNumber, 0, 2) . 'XXXXXX' . substr($contactNumber, -2),
                        'user_type' => [
                            'type_key' => $vendor->userType->type_key,
                            'title' => $vendor->userType->title
                        ],
                        'otp_expires_in' => 600,
                        'otp_expires_at' => $vendor->login_otp_expires_at->format('Y-m-d H:i:s'),
                        'otp' => env('APP_DEBUG') ? $otp : null
                    ]
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to resend login OTP', [
                    'vendor_id' => $vendor->id,
                    'error' => $e->getMessage()
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Failed to resend login OTP: ' . $e->getMessage(),
                    'error_code' => 'OTP_RESEND_FAILED'
                ], 500);
            }
        } catch (\Exception $e) {
            Log::error('Vendor Login Resend OTP Error', [
                'error' => $e->getMessage(),
                'contact_number' => $request->contact_number ?? 'unknown'
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to resend login OTP: ' . $e->getMessage(),
                'error_code' => 'LOGIN_RESEND_OTP_ERROR'
            ], 500);
        }
    }

    // ✅ 9. GET VENDOR PROFILE
    public function vendorProfile(Request $request): JsonResponse
    {
        try {
            $vendor = $this->getVendorFromToken($request);
            if (!$vendor) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid or expired token'
                ], 401);
            }

            $vendor->load(['userType', 'vehicleCategory', 'vehicleModel']);

            return response()->json([
                'success' => true,
                'message' => 'Profile retrieved successfully',
                'data' => [
                    'vendor' => $this->formatVendorData($vendor)
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve profile: ' . $e->getMessage()
            ], 500);
        }
    }

    // ✅ 10. VENDOR LOGOUT
    public function vendorLogout(Request $request): JsonResponse
    {
        try {
            $vendor = $this->getVendorFromToken($request);
            if ($vendor) {
                $vendor->update(['last_logout_at' => now()]);
                Log::info('Vendor logout', ['vendor_id' => $vendor->id]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Logout successful'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => true,
                'message' => 'Logout successful'
            ]);
        }
    }

    // ✅ HELPER METHODS

    private function getVendorFromToken(Request $request): ?Vendor
    {
        $authHeader = $request->header('Authorization');
        if (!$authHeader || !str_contains($authHeader, 'Bearer ')) {
            return null;
        }

        $token = str_replace('Bearer ', '', $authHeader);
        $decodedToken = base64_decode($token);
        $tokenParts = explode(':', $decodedToken);

        if (count($tokenParts) < 3) {
            return null;
        }

        return Vendor::with(['userType', 'vehicleCategory', 'vehicleModel'])->find($tokenParts[0]);
    }

    private function formatVendorData($vendor): array
    {
        return [
            'id' => $vendor->id,
            'name' => $vendor->name,
            'email' => $vendor->email,
            'contact_number' => $vendor->contact_number,
            'dob' => $vendor->dob,
            'gender' => $vendor->gender,
            'emergency_contact' => $vendor->emergency_contact,

            'user_type' => $vendor->userType ? [
                'id' => $vendor->userType->id,
                'type_key' => $vendor->userType->type_key,
                'title' => $vendor->userType->title,
                'subtitle' => $vendor->userType->subtitle,
                'icon' => $vendor->userType->icon
            ] : null,

            // ✅ VEHICLE INFORMATION
            'vehicle_category' => $vendor->vehicleCategory ? [
                'id' => $vendor->vehicleCategory->id,
                'category_key' => $vendor->vehicleCategory->category_key,
                'category_name' => $vendor->vehicleCategory->category_name,
                'icon' => $vendor->vehicleCategory->icon
            ] : null,

            'vehicle_model' => $vendor->vehicleModel ? [
                'id' => $vendor->vehicleModel->id,
                'model_name' => $vendor->vehicleModel->model_name,
                'vehicle_type_desc' => $vendor->vehicleModel->vehicle_type_desc,
                'body_length' => $vendor->vehicleModel->body_length,
                'body_width' => $vendor->vehicleModel->body_width,
                'body_height' => $vendor->vehicleModel->body_height,
                'carry_capacity_kgs' => $vendor->vehicleModel->carry_capacity_kgs,
                'carry_capacity_tons' => $vendor->vehicleModel->carry_capacity_tons
            ] : null,

            'is_verified' => $vendor->is_verified,
            'is_completed' => $vendor->is_completed,
            'password_set' => !empty($vendor->password),

            'aadhar_number' => $vendor->aadhar_number ?
                substr($vendor->aadhar_number, 0, 4) . '****' . substr($vendor->aadhar_number, -4) : null,
            'pan_number' => $vendor->pan_number,
            'rc_number' => $vendor->rc_number,
            'rc_verified' => $vendor->rc_verified,
            'dl_number' => $vendor->dl_number,
            'dl_verified' => $vendor->dl_verified,

            'full_address' => $vendor->full_address,
            'state' => $vendor->state,
            'city' => $vendor->city,
            'pincode' => $vendor->pincode,
            'country' => $vendor->country,

            'bank_name' => $vendor->bank_name,
            'account_number' => $vendor->account_number ?
                '****' . substr($vendor->account_number, -4) : null,
            'ifsc' => $vendor->ifsc,

            'declaration' => $vendor->declaration,
            'last_login_at' => $vendor->last_login_at,
            'created_at' => $vendor->created_at,
            'updated_at' => $vendor->updated_at,

            'documents' => [
                'aadhar_front' => $vendor->aadhar_front ? url('storage/' . $vendor->aadhar_front) : null,
                'aadhar_back' => $vendor->aadhar_back ? url('storage/' . $vendor->aadhar_back) : null,
                'pan_image' => $vendor->pan_image ? url('storage/' . $vendor->pan_image) : null,
                'rc_image' => $vendor->rc_image ? url('storage/' . $vendor->rc_image) : null,
                'dl_image' => $vendor->dl_image ? url('storage/' . $vendor->dl_image) : null
            ]
        ];
    }

    private function handleDocumentUploads($request, $vendorId): array
    {
        $documentPaths = [];
        $uploadPath = 'vendors/' . $vendorId . '/documents';

        try {
            $documents = [
                'aadhar_front' => 'aadhaar_front_',
                'aadhar_back' => 'aadhaar_back_',
                'pan_image' => 'pan_',
                'rc_image' => 'rc_',
                'dl_image' => 'dl_'
            ];

            foreach ($documents as $fieldName => $prefix) {
                if ($request->hasFile($fieldName)) {
                    $file = $request->file($fieldName);
                    if ($file->isValid()) {
                        $fileName = $prefix . time() . '.' . $file->getClientOriginalExtension();
                        $path = $file->storeAs($uploadPath, $fileName, 'public');
                        $documentPaths[$fieldName] = $path;
                        Log::info("Document uploaded: {$fieldName}", ['path' => $path]);
                    }
                }
            }

            return $documentPaths;
        } catch (\Exception $e) {
            Log::error('Document upload error', [
                'vendor_id' => $vendorId,
                'error' => $e->getMessage()
            ]);
            throw new \Exception('Document upload failed: ' . $e->getMessage());
        }
    }



    /**
     * Initialize DigiLocker for Aadhaar Verification
     */
    public function initializeAadhaarDigilocker(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'redirect_url' => 'nullable|url'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 400);
            }

            $redirectUrl = $request->redirect_url ?? config('app.url');

            $result = $this->docVerificationService->initializeDigilocker($redirectUrl);

            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $result['message'] ?? 'Failed to initialize DigiLocker'
                ], 400);
            }

            return response()->json([
                'success' => true,
                'message' => 'DigiLocker initialized. Redirect user to verify Aadhaar.',
                'data' => $result['data']
            ]);
        } catch (\Exception $e) {
            Log::error('Initialize DigiLocker Error', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to initialize DigiLocker: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * ✅ CORRECTED: Download & Verify Aadhaar from DigiLocker
     * HANDLES CALLBACK/REDIRECT PROPERLY
     */
    public function verifyAadhaarDigilocker(Request $request)
    {
        try {
            Log::info('🔐 Aadhaar DigiLocker Verification Started', [
                'request_data' => $request->all(),
                'headers' => $request->headers->all()
            ]);

            // ✅ STEP 1: Get vendor from token (OR from query params if callback)
            $vendor = null;

            // Try Authorization header first
            $authHeader = $request->header('Authorization');
            if ($authHeader && str_contains($authHeader, 'Bearer ')) {
                $token = str_replace('Bearer ', '', $authHeader);
                $decodedToken = base64_decode($token);
                $tokenParts = explode(':', $decodedToken);
                $vendorId = $tokenParts[0] ?? null;

                if ($vendorId) {
                    $vendor = Vendor::find($vendorId);
                }
            }

            // ✅ If no token, try vendor_id from request
            if (!$vendor && $request->filled('vendor_id')) {
                $vendor = Vendor::find($request->vendor_id);
            }

            // ✅ If still no vendor, try contact_number
            if (!$vendor && $request->filled('contact_number')) {
                $vendor = Vendor::where('contact_number', $request->contact_number)->first();
            }

            if (!$vendor) {
                Log::error('❌ Vendor not found for Aadhaar verification');
                return response()->json([
                    'success' => false,
                    'message' => 'Vendor identification failed. Please login again.'
                ], 404);
            }

            Log::info('✅ Vendor identified', [
                'vendor_id' => $vendor->id,
                'name' => $vendor->name
            ]);

            // ✅ STEP 2: FLEXIBLE VALIDATION - client_id from request OR database
            $validator = Validator::make($request->all(), [
                'client_id' => 'nullable|string',
                'request_id' => 'nullable|string',  // DigiLocker callback parameter
                'code' => 'nullable|string',        // DigiLocker callback parameter
                'state' => 'nullable|string',       // DigiLocker callback parameter
                'name' => 'nullable|string|max:255'
            ]);

            if ($validator->fails()) {
                Log::error('❌ Validation failed', [
                    'errors' => $validator->errors()->all()
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Invalid callback data',
                    'errors' => $validator->errors()
                ], 400);
            }

            // ✅ STEP 3: Get client_id from multiple sources
            $clientId = $request->client_id
                ?? $request->request_id
                ?? $vendor->aadhaar_digilocker_client_id
                ?? null;

            if (!$clientId) {
                Log::error('❌ Client ID not found');
                return response()->json([
                    'success' => false,
                    'message' => 'Verification session expired. Please start again.'
                ], 400);
            }

            Log::info('📝 Using client_id', ['client_id' => $clientId]);

            // ✅ STEP 4: Use name from request, vendor, or null
            $nameToMatch = $request->name ?? $vendor->name ?? null;

            Log::info('🔍 Starting DigiLocker document download', [
                'client_id' => $clientId,
                'name_to_match' => $nameToMatch
            ]);

            // ✅ STEP 5: Download & verify with name
            $result = $this->docVerificationService->downloadAadhaarDigilocker($clientId, $nameToMatch);

            Log::info('📡 DigiLocker Response', [
                'success' => $result['success'] ?? false,
                'verified' => $result['verified'] ?? false
            ]);

            if (!$result['success'] || !($result['verified'] ?? false)) {
                Log::warning('⚠️ Aadhaar verification failed', [
                    'message' => $result['message'] ?? 'Unknown error'
                ]);

                return response()->json([
                    'success' => false,
                    'message' => $result['message'] ?? 'Verification failed',
                    'details' => $result['details'] ?? null
                ], 400);
            }

            // ✅ STEP 6: Save verified data
            $updateData = [
                'aadhaar_verified' => true,
                'aadhaar_verification_date' => now(),
                'aadhaar_verified_data' => json_encode($result['data']),
                'aadhar_number' => $result['data']['masked_aadhaar'] ?? $result['data']['aadhaar_number'] ?? null,
            ];

            // Auto-fill name if empty
            if (!$vendor->name && isset($result['data']['full_name'])) {
                $updateData['name'] = $result['data']['full_name'];
            }

            // Auto-fill DOB if empty
            if (!$vendor->dob && isset($result['data']['dob'])) {
                $updateData['dob'] = $result['data']['dob'];
            }

            // Auto-fill address if empty
            if (!$vendor->full_address && isset($result['data']['full_address'])) {
                $updateData['full_address'] = $result['data']['full_address'];
            }

            $vendor->update($updateData);

            Log::info('✅ Aadhaar verified and saved successfully', [
                'vendor_id' => $vendor->id,
                'aadhaar_number' => $updateData['aadhar_number']
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Aadhaar verified successfully',
                'data' => [
                    'vendor_id' => $vendor->id,
                    'full_name' => $result['data']['full_name'] ?? null,
                    'dob' => $result['data']['dob'] ?? null,
                    'gender' => $result['data']['gender'] ?? null,
                    'masked_aadhaar' => $result['data']['masked_aadhaar'] ?? null,
                    'address' => $result['data']['full_address'] ?? null,
                    'verified_at' => now()->toDateTimeString()
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('💥 Aadhaar DigiLocker Verification Exception', [
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Verification failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * ✅ UPDATE VENDOR REFERRAL CODE (App Install Time)
     * Vendor only provides employee referral code during app install
     */
    public function updateReferralCode(Request $request): JsonResponse
    {
        try {
            // Get employee code from request
            $referralEmpId = $request->input('referral_emp_id');

            if (!$referralEmpId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Employee referral code is required'
                ], 400);
            }

            // Get authenticated vendor from token
            $vendor = $this->getVendorFromToken($request);

            if (!$vendor) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid or expired token'
                ], 401);
            }

            // Optional: Verify if employee code exists in employees table
            // Uncomment if you want to validate employee code
            /*
        $employee = \App\Models\Employee::where('emp_code', $referralEmpId)->first();
        if (!$employee) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid employee referral code'
            ], 404);
        }
        */

            // Update vendor with referral code and app install timestamp
            $vendor->referral_emp_id = $referralEmpId;
            $vendor->app_installed_at = now();

            // If you want to link with employee ID, uncomment:
            // $vendor->referred_by_employee_id = $employee->id ?? null;

            $vendor->save();

            Log::info('Vendor referral code updated', [
                'vendor_id' => $vendor->id,
                'referral_emp_id' => $referralEmpId
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Employee referral code updated successfully',
                'data' => [
                    'vendor_id' => $vendor->id,
                    'referral_emp_id' => $vendor->referral_emp_id,
                    'app_installed_at' => $vendor->app_installed_at->toDateTimeString()
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Update vendor referral code error', [
                'error' => $e->getMessage(),
                'line' => $e->getLine()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update referral code: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * ✅ VERIFY BANK ACCOUNT (WITHOUT NAME - OPTIONAL)
     */
    public function verifyBankAccount(Request $request)
    {
        try {
            // ✅ Validate request - NAME IS OPTIONAL NOW
            $validator = Validator::make($request->all(), [
                'account_number' => 'required|string|max:20',
                'ifsc' => 'required|string|size:11'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 400);
            }

            Log::info('🏦 Bank Account Verification Request', [
                'account_number' => $request->account_number,
                'ifsc' => $request->ifsc
            ]);

            // ✅ Verify bank account WITHOUT name matching
            $bankVerification = $this->docVerificationService->verifyBankAccount(
                $request->account_number,
                $request->ifsc
            );

            Log::info('📡 Bank Verification Response', [
                'success' => $bankVerification['success'] ?? false,
                'verified' => $bankVerification['verified'] ?? false
            ]);

            // ❌ Verification Failed
            if (!$bankVerification['success'] || !($bankVerification['verified'] ?? false)) {
                return response()->json([
                    'success' => false,
                    'verified' => false,
                    'message' => $bankVerification['message'] ?? 'Bank verification failed',
                    'error_code' => 'BANK_VERIFICATION_FAILED',
                    'details' => $bankVerification['details'] ?? null
                ], 400);
            }

            // ✅ Verification Successful
            return response()->json([
                'success' => true,
                'verified' => true,
                'message' => 'Bank account verified successfully',
                'data' => [
                    'account_number' => $bankVerification['data']['account_number'] ?? null,
                    'ifsc' => $bankVerification['data']['ifsc'] ?? null,
                    'account_holder_name' => $bankVerification['data']['account_holder_name'] ?? null,
                    'account_exists' => $bankVerification['data']['account_exists'] ?? false,
                    'bank_details' => [
                        'bank_name' => $bankVerification['data']['ifsc_details']['bank_name'] ?? null,
                        'branch' => $bankVerification['data']['ifsc_details']['branch'] ?? null,
                        'city' => $bankVerification['data']['ifsc_details']['city'] ?? null,
                        'state' => $bankVerification['data']['ifsc_details']['state'] ?? null,
                        'address' => $bankVerification['data']['ifsc_details']['address'] ?? null,
                        'contact' => $bankVerification['data']['ifsc_details']['contact'] ?? null
                    ],
                    'verified_at' => $bankVerification['data']['verified_at'] ?? null
                ]
            ], 200);
        } catch (\Exception $e) {
            Log::error('💥 Bank Verification Exception', [
                'error' => $e->getMessage(),
                'line' => $e->getLine()
            ]);

            return response()->json([
                'success' => false,
                'verified' => false,
                'message' => 'Bank verification failed: ' . $e->getMessage(),
                'error_code' => 'BANK_VERIFICATION_ERROR'
            ], 500);
        }
    }
}
