<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\CompleteVendorRegistrationRequest;
use App\Models\Vendor;
use App\Services\SmsService;
use App\Services\DocumentVerificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class VendorAuthController extends Controller
{
    private $smsService;
    private $docVerificationService;

    public function __construct()
    {
        $this->smsService = new SmsService();
        $this->docVerificationService = new DocumentVerificationService();
    }

    // Send OTP
    public function sendOtp(Request $request): JsonResponse
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

            // Find existing vendor
            $vendor = Vendor::where('contact_number', $contactNumber)->first();

            // Check if vendor already completed
            if ($vendor && $vendor->is_verified && $vendor->is_completed) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vendor already registered and verified',
                    'status' => 'already_completed'
                ], 409);
            }

            // Create vendor if doesn't exist
            if (!$vendor) {
                $vendor = Vendor::create(['contact_number' => $contactNumber]);
            }

            // Check OTP rate limit
            if (!$vendor->canSendOtp()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Please wait 60 seconds before requesting another OTP',
                    'status' => 'rate_limited'
                ], 429);
            }

            // Generate and save OTP
            $otp = $vendor->generateOtp();
            $smsResult = $this->smsService->sendOtp($contactNumber, $otp);

            return response()->json([
                'success' => true,
                'message' => 'OTP sent successfully to ' . $contactNumber,
                'otp' => $otp, // Remove in production
                'expires_in' => 10,
                'vendor_id' => $vendor->id,
                'status' => 'otp_sent'
            ]);
        } catch (\Exception $e) {
            Log::error('Vendor Send OTP Error', [
                'contact_number' => $contactNumber ?? null,
                'message' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to send OTP: ' . $e->getMessage()
            ], 500);
        }
    }

    // Verify OTP
    public function verifyOtp(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'contact_number' => 'required|string|digits:10|regex:/^[6-9]\d{9}$/',
                'otp' => 'required|string|digits:6'
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

            $vendor = Vendor::where('contact_number', $contactNumber)->first();

            if (!$vendor) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vendor not found. Please send OTP first.',
                    'status' => 'vendor_not_found'
                ], 404);
            }

            // Check if already verified
            if ($vendor->is_verified) {
                if ($vendor->is_completed) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Vendor already verified and registered',
                        'status' => 'already_verified_completed'
                    ], 409);
                } else {
                    $token = base64_encode($vendor->id . ':' . time() . ':' . $contactNumber);

                    return response()->json([
                        'success' => true,
                        'message' => 'Vendor already verified. Please complete registration.',
                        'status' => 'verified_incomplete',
                        'access_token' => $token,
                        'vendor_id' => $vendor->id
                    ]);
                }
            }

            // Verify OTP
            if (!$vendor->isOtpValid($otpInput)) {
                $vendor->incrementOtpAttempts();

                return response()->json([
                    'success' => false,
                    'message' => 'Invalid or expired OTP',
                    'status' => 'invalid_otp',
                    'attempts_left' => max(0, 3 - $vendor->otp_attempts)
                ], 400);
            }

            // Mark vendor as verified
            $vendor->update([
                'is_verified' => true,
                'otp' => null,
                'otp_expires_at' => null,
                'otp_attempts' => 0
            ]);

            $token = base64_encode($vendor->id . ':' . time() . ':' . $contactNumber);

            return response()->json([
                'success' => true,
                'message' => 'OTP verified successfully. Please complete registration.',
                'status' => 'otp_verified',
                'vendor' => [
                    'id' => $vendor->id,
                    'contact_number' => $vendor->contact_number,
                    'is_verified' => $vendor->is_verified,
                    'is_completed' => $vendor->is_completed
                ],
                'access_token' => $token,
                'token_type' => 'bearer',
                'expires_in' => 3600
            ]);
        } catch (\Exception $e) {
            Log::error('Vendor Verify OTP Error', [
                'contact_number' => $contactNumber ?? null,
                'message' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'OTP verification failed: ' . $e->getMessage()
            ], 500);
        }
    }

    // Resend OTP
    public function resendOtp(Request $request): JsonResponse
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
            $vendor = Vendor::where('contact_number', $contactNumber)->first();

            if (!$vendor) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vendor not found. Please register first.',
                    'status' => 'vendor_not_found'
                ], 404);
            }

            // Check if already completed
            if ($vendor->is_verified && $vendor->is_completed) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vendor already verified and registered',
                    'status' => 'already_completed'
                ], 409);
            }

            // Check resend rate limit
            $waitTime = $vendor->getResendWaitTime();

            if ($waitTime > 0) {
                return response()->json([
                    'success' => false,
                    'message' => "Please wait {$waitTime} seconds before requesting another OTP",
                    'status' => 'rate_limited',
                    'wait_time' => $waitTime
                ], 429);
            }

            // Generate new OTP and increment resend counter
            $otp = $vendor->generateOtp();
            $vendor->incrementResendCount();

            // Send SMS
            $smsResult = $this->smsService->sendOtp($contactNumber, $otp);

            return response()->json([
                'success' => true,
                'message' => 'OTP resent successfully to ' . $contactNumber,
                'otp' => $otp, // Remove in production
                'expires_in' => 10,
                'vendor_id' => $vendor->id,
                'status' => 'otp_resent'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to resend OTP: ' . $e->getMessage()
            ], 500);
        }
    }

    // ✅ COMPLETE REGISTRATION - ENHANCED WITH FULL VERIFICATION & RESPONSE
    public function completeRegistration(CompleteVendorRegistrationRequest $request): JsonResponse
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

            if (!$vendor || !$vendor->is_verified) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vendor not verified. Please verify OTP first.'
                ], 403);
            }

            // Check if already completed
            if ($vendor->is_completed) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vendor registration already completed',
                    'status' => 'already_completed',
                    'vendor' => $this->formatVendorData($vendor) // ✅ Return full data
                ], 409);
            }

            DB::beginTransaction();

            // ✅ ENHANCED DOCUMENT VERIFICATION
            $verificationResults = [];

            // Aadhaar verification
            if ($request->aadhar_number && $request->name) {
                $aadhaarVerification = $this->docVerificationService->verifyAadhaar(
                    $request->aadhar_number,
                    $request->name
                );

                $verificationResults['aadhaar'] = $aadhaarVerification;

                if (!$aadhaarVerification['success']) {
                    DB::rollback();
                    return response()->json([
                        'success' => false,
                        'message' => 'Aadhaar verification failed: ' . $aadhaarVerification['message'],
                        'verification_step' => 'aadhaar',
                        'error_code' => $aadhaarVerification['error_code'] ?? 'AADHAAR_VERIFICATION_FAILED',
                        'verification_details' => $aadhaarVerification['verification_details'] ?? null,
                        'required_action' => $aadhaarVerification['required_action'] ?? 'Please provide valid Aadhaar details'
                    ], 400);
                }
            }

            // PAN verification
            if ($request->pan_number && $request->name) {
                $panVerification = $this->docVerificationService->verifyPan(
                    $request->pan_number,
                    $request->name
                );

                $verificationResults['pan'] = $panVerification;

                if (!$panVerification['success']) {
                    DB::rollback();
                    return response()->json([
                        'success' => false,
                        'message' => 'PAN verification failed: ' . $panVerification['message'],
                        'verification_step' => 'pan',
                        'error_code' => $panVerification['error_code'] ?? 'PAN_VERIFICATION_FAILED',
                        'verification_details' => $panVerification['verification_details'] ?? null,
                        'required_action' => $panVerification['required_action'] ?? 'Please provide valid PAN details'
                    ], 400);
                }
            }

            // ✅ RC VERIFICATION (Enhanced)
            if ($request->rc_number && $request->name) {
                $rcVerification = $this->docVerificationService->verifyRc(
                    $request->rc_number,
                    $request->name
                );

                $verificationResults['rc'] = $rcVerification;

                if (!$rcVerification['success']) {
                    DB::rollback();
                    return response()->json([
                        'success' => false,
                        'message' => 'RC verification failed: ' . $rcVerification['message'],
                        'verification_step' => 'rc',
                        'error_code' => $rcVerification['error_code'] ?? 'RC_VERIFICATION_FAILED',
                        'verification_details' => $rcVerification['verification_details'] ?? null,
                        'required_action' => $rcVerification['required_action'] ?? 'Please provide valid RC details'
                    ], 400);
                }

                // ✅ Store RC verification data
                if (isset($rcVerification['data'])) {
                    $verificationResults['rc_verified_data'] = json_encode($rcVerification['data']);
                    $verificationResults['rc_verification_date'] = now();
                    $verificationResults['rc_verified'] = true;
                }
            }

            // Handle file uploads
            $documentPaths = $this->handleDocumentUploads($request, $vendor->id);

            // Prepare data
            $validatedData = $request->validated();

            // Hash password
            if (isset($validatedData['password'])) {
                $validatedData['password'] = Hash::make($validatedData['password']);
            }

            // Remove password confirmation if exists
            if (isset($validatedData['password_confirmation'])) {
                unset($validatedData['password_confirmation']);
            }

            // Process boolean fields
            if (isset($validatedData['declaration'])) {
                $validatedData['declaration'] = filter_var($validatedData['declaration'], FILTER_VALIDATE_BOOLEAN);
            }
            if (isset($validatedData['same_address'])) {
                $validatedData['same_address'] = filter_var($validatedData['same_address'], FILTER_VALIDATE_BOOLEAN);
            }

            // ✅ Merge with document paths and verification results
            $updateData = array_merge(
                $validatedData,
                $documentPaths ?? [],
                $verificationResults
            );
            $updateData['is_completed'] = true;

            // Update vendor
            $vendor->update($updateData);

            // ✅ Refresh vendor data to get all updated fields
            $vendor->refresh();

            DB::commit();

            Log::info('Vendor Registration Completed', [
                'vendor_id' => $vendor->id,
                'name' => $vendor->name,
                'verification_results' => array_keys($verificationResults)
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Vendor registration completed successfully',
                'status' => 'registration_completed',
                'verification_summary' => [
                    'aadhaar_verified' => isset($verificationResults['aadhaar']) && $verificationResults['aadhaar']['success'],
                    'pan_verified' => isset($verificationResults['pan']) && $verificationResults['pan']['success'],
                    'rc_verified' => isset($verificationResults['rc']) && $verificationResults['rc']['success']
                ],
                'vendor' => $this->formatVendorData($vendor) // ✅ Return complete vendor data
            ]);

        } catch (\Exception $e) {
            DB::rollback();

            Log::error('Vendor Complete Registration Error', [
                'vendor_id' => $vendorId ?? null,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Registration failed: ' . $e->getMessage(),
                'error_code' => 'REGISTRATION_ERROR'
            ], 500);
        }
    }

    // ✅ FORMAT COMPLETE VENDOR DATA
    private function formatVendorData($vendor)
    {
        return [
            // Basic Information
            'id' => $vendor->id,
            'name' => $vendor->name,
            'email' => $vendor->email,
            'contact_number' => $vendor->contact_number,
            'dob' => $vendor->dob,
            'gender' => $vendor->gender,
            'emergency_contact' => $vendor->emergency_contact,

            // Verification Status
            'is_verified' => $vendor->is_verified,
            'is_completed' => $vendor->is_completed,
            'password_set' => !empty($vendor->password),

            // Document Information
            'aadhar_number' => $vendor->aadhar_number ?
                substr($vendor->aadhar_number, 0, 4) . '****' . substr($vendor->aadhar_number, -4) : null,
            'aadhar_front' => $vendor->aadhar_front,
            'aadhar_back' => $vendor->aadhar_back,

            'pan_number' => $vendor->pan_number,
            'pan_image' => $vendor->pan_image,

            'rc_number' => $vendor->rc_number,
            'rc_image' => $vendor->rc_image,
            'rc_verified' => $vendor->rc_verified,
            'rc_verification_date' => $vendor->rc_verification_date,
            'rc_verified_data' => $vendor->rc_verified_data ? json_decode($vendor->rc_verified_data, true) : null,

            // Address Information
            'full_address' => $vendor->full_address,
            'state' => $vendor->state,
            'city' => $vendor->city,
            'pincode' => $vendor->pincode,
            'postal_code' => $vendor->postal_code,
            'country' => $vendor->country,
            'same_address' => $vendor->same_address,

            // Banking Information
            'bank_name' => $vendor->bank_name,
            'account_number' => $vendor->account_number ?
                '****' . substr($vendor->account_number, -4) : null,
            'ifsc' => $vendor->ifsc,

            // Other Information
            'declaration' => $vendor->declaration,

            // Login Information
            'last_login_at' => $vendor->last_login_at,
            'login_count' => $vendor->login_count ?? 0,
            'last_logout_at' => $vendor->last_logout_at,

            // OTP Information (for debugging)
            'otp_attempts' => $vendor->otp_attempts ?? 0,
            'otp_resend_count' => $vendor->otp_resend_count ?? 0,
            'failed_login_attempts' => $vendor->failed_login_attempts ?? 0,

            // Timestamps
            'created_at' => $vendor->created_at,
            'updated_at' => $vendor->updated_at,

            // Document URLs
            'documents' => [
                'aadhar_front' => $vendor->aadhar_front ? url('storage/' . $vendor->aadhar_front) : null,
                'aadhar_back' => $vendor->aadhar_back ? url('storage/' . $vendor->aadhar_back) : null,
                'pan_image' => $vendor->pan_image ? url('storage/' . $vendor->pan_image) : null,
                'rc_image' => $vendor->rc_image ? url('storage/' . $vendor->rc_image) : null
            ]
        ];
    }

    // ✅ ENHANCED DOCUMENT UPLOAD HANDLER
    private function handleDocumentUploads($request, $vendorId): array
    {
        $documentPaths = [];
        $uploadPath = 'vendors/' . $vendorId . '/documents';

        try {
            // Aadhaar Front
            if ($request->hasFile('aadhar_front')) {
                $file = $request->file('aadhar_front');
                if ($file->isValid()) {
                    $fileName = 'aadhaar_front_' . time() . '.' . $file->getClientOriginalExtension();
                    $path = $file->storeAs($uploadPath, $fileName, 'public');
                    $documentPaths['aadhar_front'] = $path;

                    Log::info('Aadhaar Front uploaded', ['path' => $path]);
                }
            }

            // Aadhaar Back
            if ($request->hasFile('aadhar_back')) {
                $file = $request->file('aadhar_back');
                if ($file->isValid()) {
                    $fileName = 'aadhaar_back_' . time() . '.' . $file->getClientOriginalExtension();
                    $path = $file->storeAs($uploadPath, $fileName, 'public');
                    $documentPaths['aadhar_back'] = $path;

                    Log::info('Aadhaar Back uploaded', ['path' => $path]);
                }
            }

            // PAN Image
            if ($request->hasFile('pan_image')) {
                $file = $request->file('pan_image');
                if ($file->isValid()) {
                    $fileName = 'pan_' . time() . '.' . $file->getClientOriginalExtension();
                    $path = $file->storeAs($uploadPath, $fileName, 'public');
                    $documentPaths['pan_image'] = $path;

                    Log::info('PAN Image uploaded', ['path' => $path]);
                }
            }

            // ✅ RC Image
            if ($request->hasFile('rc_image')) {
                $file = $request->file('rc_image');
                if ($file->isValid()) {
                    $fileName = 'rc_' . time() . '.' . $file->getClientOriginalExtension();
                    $path = $file->storeAs($uploadPath, $fileName, 'public');
                    $documentPaths['rc_image'] = $path;

                    Log::info('RC Image uploaded', ['path' => $path]);
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

    // Login OTP Methods (Keep existing ones)
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

            // Find vendor
            $vendor = Vendor::where('contact_number', $contactNumber)->first();

            if (!$vendor) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vendor not found. Please register first.',
                    'error_code' => 'VENDOR_NOT_FOUND'
                ], 404);
            }

            if (!$vendor->is_verified || !$vendor->is_completed) {
                return response()->json([
                    'success' => false,
                    'message' => 'Please complete vendor registration and verification first.',
                    'error_code' => 'REGISTRATION_INCOMPLETE',
                    'vendor_status' => [
                        'is_verified' => $vendor->is_verified,
                        'is_completed' => $vendor->is_completed
                    ]
                ], 403);
            }

            // Generate OTP
            try {
                $otp = $vendor->generateLoginOtpSimple();

                // Send SMS
                $smsResponse = $this->smsService->sendOtp($contactNumber, $otp, 'login');

                Log::info('Vendor login OTP sent successfully', [
                    'vendor_id' => $vendor->id,
                    'otp' => $otp,
                    'sms_sent' => $smsResponse['success'] ?? false
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Login OTP sent successfully',
                    'data' => [
                        'vendor_id' => $vendor->id,
                        'contact_number' => $contactNumber,
                        'otp_expires_in' => 600,
                        'otp_expires_at' => now()->addMinutes(10)->format('Y-m-d H:i:s'),
                        'otp' => $otp // For testing - remove in production
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
                'line' => $e->getLine(),
                'contact_number' => $request->contact_number ?? 'unknown'
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to send login OTP: ' . $e->getMessage(),
                'error_code' => 'LOGIN_OTP_ERROR'
            ], 500);
        }
    }

    // Login Verify OTP
    public function loginVerifyOtp(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'contact_number' => 'required|string|digits:10|regex:/^[6-9]\d{9}$/',
                'otp' => 'required|string|digits:6'
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
                'contact_number' => $contactNumber,
                'ip' => $request->ip(),
                'timestamp' => now()
            ]);

            // Find vendor
            $vendor = Vendor::where('contact_number', $contactNumber)->first();

            if (!$vendor) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vendor not found',
                    'error_code' => 'VENDOR_NOT_FOUND'
                ], 404);
            }

            // Check if login OTP exists
            if (empty($vendor->login_otp)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No login OTP found. Please request a new OTP.',
                    'error_code' => 'NO_OTP_FOUND'
                ], 400);
            }

            // Check OTP expiration
            if (now()->isAfter($vendor->login_otp_expires_at)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Login OTP has expired. Please request a new OTP.',
                    'error_code' => 'OTP_EXPIRED'
                ], 400);
            }

            // Verify OTP
            if ($vendor->login_otp !== $inputOtp) {
                // Increment failed attempts
                $vendor->increment('failed_login_attempts');

                Log::warning('Invalid vendor login OTP attempt', [
                    'vendor_id' => $vendor->id,
                    'contact_number' => $contactNumber,
                    'failed_attempts' => $vendor->failed_login_attempts + 1
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Invalid OTP. Please check and try again.',
                    'error_code' => 'INVALID_OTP',
                    'remaining_attempts' => max(0, 3 - ($vendor->failed_login_attempts + 1))
                ], 400);
            }

            // ✅ OTP VERIFIED - LOGIN SUCCESSFUL
            DB::beginTransaction();

            try {
                // Clear login OTP data
                $vendor->update([
                    'login_otp' => null,
                    'login_otp_expires_at' => null,
                    'failed_login_attempts' => 0,
                    'last_login_at' => now(),
                    'login_count' => ($vendor->login_count ?? 0) + 1
                ]);

                // Generate custom token
                $token = base64_encode($vendor->id . ':' . time() . ':' . $contactNumber . ':login');

                DB::commit();

                Log::info('Vendor login successful', [
                    'vendor_id' => $vendor->id,
                    'contact_number' => $contactNumber,
                    'login_count' => $vendor->login_count
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Login successful',
                    'status' => 'login_successful',
                    'data' => [
                        'access_token' => $token,
                        'token_type' => 'Bearer',
                        'expires_in' => 3600,
                        'vendor' => $this->formatVendorData($vendor) // ✅ Return complete vendor data
                    ]
                ]);
            } catch (\Exception $e) {
                DB::rollback();
                throw $e;
            }
        } catch (\Exception $e) {
            Log::error('Vendor Login Verify OTP Error', [
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'contact_number' => $request->contact_number ?? 'unknown'
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Login verification failed. Please try again.',
                'error_code' => 'LOGIN_VERIFICATION_ERROR'
            ], 500);
        }
    }

    // Login Resend OTP
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

            Log::info('Vendor login OTP resend request', [
                'contact_number' => $contactNumber
            ]);

            // Find vendor
            $vendor = Vendor::where('contact_number', $contactNumber)->first();

            if (!$vendor) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vendor not found. Please register first.',
                    'error_code' => 'VENDOR_NOT_FOUND'
                ], 404);
            }

            if (!$vendor->is_verified || !$vendor->is_completed) {
                return response()->json([
                    'success' => false,
                    'message' => 'Please complete vendor registration and verification first.',
                    'error_code' => 'REGISTRATION_INCOMPLETE'
                ], 403);
            }

            try {
                $otp = $vendor->generateLoginOtpSimple();

                // Send SMS
                $smsResponse = $this->smsService->sendOtp($contactNumber, $otp, 'login_resend');

                Log::info('Vendor login OTP resent successfully', [
                    'vendor_id' => $vendor->id,
                    'otp' => $otp,
                    'sms_sent' => $smsResponse['success'] ?? false
                ]);

                // Increment resend counter
                try {
                    DB::table('vendors')
                        ->where('id', $vendor->id)
                        ->increment('otp_resend_count');
                } catch (\Exception $e) {
                    // Ignore if increment fails
                }

                return response()->json([
                    'success' => true,
                    'message' => 'Login OTP resent successfully',
                    'data' => [
                        'vendor_id' => $vendor->id,
                        'contact_number' => $contactNumber,
                        'otp_expires_in' => 600,
                        'otp_expires_at' => now()->addMinutes(10)->format('Y-m-d H:i:s'),
                        'otp' => $otp, // For testing
                        'status' => 'login_otp_resent',
                        'sms_sent' => $smsResponse['success'] ?? false
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
                'line' => $e->getLine(),
                'contact_number' => $request->contact_number ?? 'unknown'
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to resend login OTP: ' . $e->getMessage(),
                'error_code' => 'LOGIN_RESEND_ERROR'
            ], 500);
        }
    }

    // Vendor Logout
    public function vendorLogout(Request $request): JsonResponse
    {
        try {
            // Get vendor from token
            $authHeader = $request->header('Authorization');
            if ($authHeader && str_contains($authHeader, 'Bearer ')) {
                $token = str_replace('Bearer ', '', $authHeader);
                $decodedToken = base64_decode($token);
                $tokenParts = explode(':', $decodedToken);

                if (count($tokenParts) >= 3) {
                    $vendorId = $tokenParts[0];
                    $vendor = Vendor::find($vendorId);

                    if ($vendor) {
                        Log::info('Vendor logout', [
                            'vendor_id' => $vendor->id,
                            'contact_number' => $vendor->contact_number
                        ]);

                        // Update last logout time
                        $vendor->update(['last_logout_at' => now()]);
                    }
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Logout successful'
            ]);
        } catch (\Exception $e) {
            Log::error('Vendor Logout Error', [
                'error' => $e->getMessage(),
                'line' => $e->getLine()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Logout successful' // Always return success for logout
            ]);
        }
    }

    // ✅ GET VENDOR PROFILE (Complete data)
    public function vendorProfile(Request $request): JsonResponse
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

            if (!$vendor) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vendor not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Vendor profile retrieved successfully',
                'data' => [
                    'vendor' => $this->formatVendorData($vendor) // ✅ Complete vendor data
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Get Vendor Profile Error', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve profile: ' . $e->getMessage()
            ], 500);
        }
    }
}
