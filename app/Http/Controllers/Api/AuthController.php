<?php

namespace App\Http\Controllers\Api;

use Carbon\Carbon;
use App\Models\User;
use App\Services\SmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use App\Services\DocumentVerificationService;
use Illuminate\Support\Facades\Http;


class AuthController extends Controller
{
    private $smsService;
    private $docVerificationService;

    public function __construct()
    {
        $this->smsService = new SmsService();
        $this->docVerificationService = new DocumentVerificationService();
    }

/**
 * ✅ FIXED: UNIFIED SEND OTP - Allow Incomplete Registrations
 */
public function sendOtp(Request $request)
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

        Log::info('Send OTP request', ['contact_number' => $contactNumber]);

        $user = User::where('contact_number', $contactNumber)->first();

        // ✅ CASE 1: User doesn't exist - NEW REGISTRATION
        if (!$user) {
            $user = User::create(['contact_number' => $contactNumber]);

            Log::info('New user created', [
                'user_id' => $user->user_id,
                'db_id' => $user->id,
                'contact' => $contactNumber
            ]);

            $otp = $user->generateOtp();

            try {
                $this->smsService->sendOtp($contactNumber, $otp, 'registration');
            } catch (\Exception $smsError) {
                Log::error('SMS failed', ['error' => $smsError->getMessage()]);
            }

            return response()->json([
                'success' => true,
                'message' => 'OTP sent successfully for registration',
                'data' => [
                    'user_id' => $user->user_id,
                    'contact_number' => $contactNumber,
                    'contact_number_masked' => substr($contactNumber, 0, 2) . 'XXXXXX' . substr($contactNumber, -2),
                    'otp_sent' => true,
                    'otp' => env('APP_DEBUG') ? $otp : null,
                    'otp_length' => 4,
                    'expires_in_minutes' => 10,
                    'expires_at' => $user->otp_expires_at->format('Y-m-d H:i:s'),
                    'is_new_registration' => true,
                    'status' => 'new_user',
                    'next_step' => 'verify_otp'
                ]
            ]);
        }

        // ✅ CASE 2: User exists but NOT verified - RESEND OTP
        if (!$user->is_verified) {
            Log::info('User not verified - Sending OTP', [
                'user_id' => $user->user_id,
                'is_verified' => $user->is_verified
            ]);

            $otp = $user->generateOtp();

            try {
                $this->smsService->sendOtp($contactNumber, $otp, 'registration');
            } catch (\Exception $smsError) {
                Log::error('SMS failed', ['error' => $smsError->getMessage()]);
            }

            return response()->json([
                'success' => true,
                'message' => 'OTP resent successfully for registration',
                'data' => [
                    'user_id' => $user->user_id,
                    'contact_number' => $contactNumber,
                    'contact_number_masked' => substr($contactNumber, 0, 2) . 'XXXXXX' . substr($contactNumber, -2),
                    'otp_sent' => true,
                    'otp' => env('APP_DEBUG') ? $otp : null,
                    'otp_length' => 4,
                    'expires_in_minutes' => 10,
                    'expires_at' => $user->otp_expires_at->format('Y-m-d H:i:s'),
                    'resend_count' => $user->otp_resend_count ?? 0,
                    'status' => 'not_verified',
                    'next_step' => 'verify_otp'
                ]
            ]);
        }

        // ✅ CASE 3: User verified but registration INCOMPLETE - SEND OTP to Continue
        if ($user->is_verified && !$user->is_completed) {
            Log::info('Incomplete registration found - Sending OTP', [
                'user_id' => $user->user_id,
                'is_verified' => $user->is_verified,
                'is_completed' => $user->is_completed
            ]);

            $otp = $user->generateOtp();

            try {
                $this->smsService->sendOtp($contactNumber, $otp, 'registration_continue');
            } catch (\Exception $smsError) {
                Log::error('SMS failed', ['error' => $smsError->getMessage()]);
            }

            return response()->json([
                'success' => true,
                'message' => 'OTP sent successfully. Please complete your registration.',
                'data' => [
                    'user_id' => $user->user_id,
                    'contact_number' => $contactNumber,
                    'contact_number_masked' => substr($contactNumber, 0, 2) . 'XXXXXX' . substr($contactNumber, -2),
                    'otp_sent' => true,
                    'otp' => env('APP_DEBUG') ? $otp : null,
                    'otp_length' => 4,
                    'expires_in_minutes' => 10,
                    'expires_at' => $user->otp_expires_at->format('Y-m-d H:i:s'),
                    'is_verified' => true,
                    'is_completed' => false,
                    'status' => 'incomplete_registration',
                    'message_detail' => 'Your mobile is verified but registration incomplete. Please complete your profile.',
                    'next_step' => 'verify_otp_and_complete_registration'
                ]
            ]);
        }

        // ✅ CASE 4: User fully registered - LOGIN via OTP
        if ($user->is_verified && $user->is_completed) {
            Log::info('Fully registered user - Sending login OTP', [
                'user_id' => $user->user_id,
                'name' => $user->name
            ]);

            $otp = $user->generateOtp();

            try {
                $this->smsService->sendOtp($contactNumber, $otp, 'login');
            } catch (\Exception $smsError) {
                Log::error('SMS failed', ['error' => $smsError->getMessage()]);
            }

            return response()->json([
                'success' => true,
                'message' => 'OTP sent successfully for login',
                'data' => [
                    'user_id' => $user->user_id,
                    'contact_number' => $contactNumber,
                    'contact_number_masked' => substr($contactNumber, 0, 2) . 'XXXXXX' . substr($contactNumber, -2),
                    'otp_sent' => true,
                    'otp' => env('APP_DEBUG') ? $otp : null,
                    'otp_length' => 4,
                    'expires_in_minutes' => 10,
                    'expires_at' => $user->otp_expires_at->format('Y-m-d H:i:s'),
                    'user' => [
                        'user_id' => $user->user_id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'has_password' => !empty($user->password)
                    ],
                    'status' => 'login_via_otp',
                    'next_step' => 'verify_otp_to_login'
                ]
            ]);
        }

    } catch (\Exception $e) {
        Log::error('Send OTP Error', [
            'contact_number' => $contactNumber ?? null,
            'error' => $e->getMessage(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Failed to send OTP: ' . $e->getMessage(),
            'error_code' => 'OTP_SEND_FAILED'
        ], 500);
    }
}


    /**
     * 2. UNIFIED VERIFY OTP (Registration + Login)
     */
    public function verifyOtp(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'contact_number' => 'required|string|digits:10|regex:/^[6-9]\d{9}$/',
                'otp' => 'required|string|size:4'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 400);
            }

            $contactNumber = $request->contact_number;
            $otpInput = $request->otp;

            Log::info('Verify OTP attempt', [
                'contact_number' => $contactNumber,
                'otp_length' => strlen($otpInput)
            ]);

            $user = User::where('contact_number', $contactNumber)->first();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found. Please send OTP first.',
                    'status' => 'user_not_found'
                ], 404);
            }

            // Verify OTP
            if (!$user->isOtpValid($otpInput)) {
                $user->incrementOtpAttempts();

                return response()->json([
                    'success' => false,
                    'message' => 'Invalid or expired OTP',
                    'status' => 'invalid_otp',
                    'data' => [
                        'attempts_used' => $user->otp_attempts,
                        'attempts_left' => max(0, 3 - $user->otp_attempts),
                        'can_resend' => true
                    ]
                ], 400);
            }

            // Clear OTP
            $user->update([
                'is_verified' => true,
                'otp' => null,
                'otp_expires_at' => null,
                'otp_attempts' => 0
            ]);

            $token = base64_encode($user->id . ':' . time() . ':' . $contactNumber);

            // CASE 1: New user - needs to complete registration
            if (!$user->is_completed) {
                return response()->json([
                    'success' => true,
                    'message' => 'OTP verified successfully. Please complete registration.',
                    'status' => 'otp_verified_registration_pending',
                    'action' => 'complete_registration',
                    'data' => [
                        'user' => [
                            'id' => $user->id,
                            'contact_number' => $user->contact_number,
                            'is_verified' => true,
                            'is_completed' => false
                        ],
                        'access_token' => $token,
                        'token_type' => 'bearer',
                        'expires_in' => 3600,
                        'next_step' => 'complete_registration'
                    ]
                ]);
            }

            // CASE 2: Existing user - login successful
            Log::info('User logged in via OTP', [
                'user_id' => $user->id,
                'contact_number' => $contactNumber
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Login successful',
                'status' => 'login_successful',
                'action' => 'redirect_to_dashboard',
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'contact_number' => $user->contact_number,
                        'email' => $user->email,
                        'is_verified' => true,
                        'is_completed' => true
                    ],
                    'access_token' => $token,
                    'token_type' => 'bearer',
                    'expires_in' => 3600
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Verify OTP Error', [
                'contact_number' => $contactNumber ?? null,
                'error' => $e->getMessage(),
                'line' => $e->getLine()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'OTP verification failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * 3. RESEND OTP
     */
    public function resendOtp(Request $request)
    {
        return $this->sendOtp($request);
    }

    /**
     * 4. PASSWORD LOGIN
     */
    public function login(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'contact_number' => 'required|string|digits:10',
                'password' => 'required|string'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 400);
            }

            $user = User::where('contact_number', $request->contact_number)->first();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found'
                ], 404);
            }

            if (!$user->is_verified || !$user->is_completed) {
                return response()->json([
                    'success' => false,
                    'message' => 'Please complete registration first'
                ], 403);
            }

            if (empty($user->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Password not set. Please use OTP login.',
                    'action' => 'use_otp_login'
                ], 400);
            }

            if (!Hash::check($request->password, $user->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid password'
                ], 401);
            }

            $token = base64_encode($user->id . ':' . time() . ':' . $user->contact_number);

            Log::info('User logged in successfully', [
                'user_id' => $user->id,
                'contact_number' => $user->contact_number
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Login successful',
                'data' => [
                    'access_token' => $token,
                    'token_type' => 'bearer',
                    'expires_in' => 3600,
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'contact_number' => $user->contact_number,
                        'email' => $user->email,
                        'is_verified' => $user->is_verified,
                        'is_completed' => $user->is_completed
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Login error', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Login failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * 5. COMPLETE REGISTRATION - WITHOUT VALIDATION
     */
/**
 * 5. COMPLETE REGISTRATION - WITH GST FIELD
 */
public function completeRegistration(Request $request)
{
    ini_set('max_execution_time', 120);
    set_time_limit(120);

    try {
        Log::info('Complete registration started', [
            'timestamp' => now(),
            'has_authorization' => !empty($request->header('Authorization'))
        ]);

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

        $userId = $tokenParts[0];
        $user = User::find($userId);

        if (!$user || !$user->is_verified) {
            return response()->json([
                'success' => false,
                'message' => 'User not verified. Please verify OTP first.'
            ], 403);
        }

        if ($user->is_completed) {
            return response()->json([
                'success' => false,
                'message' => 'User registration already completed',
                'status' => 'already_completed',
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'contact_number' => $user->contact_number,
                        'email' => $user->email,
                        'is_completed' => $user->is_completed
                    ]
                ]
            ], 409);
        }

        DB::beginTransaction();

        try {
            $verificationResults = [];
            $pincodeData = null;

            // ✅ AUTO-FILL LOCATION FROM PINCODE
            if ($request->pincode) {
                try {
                    $pincodeResponse = Http::timeout(5)
                        ->get("https://api.postalpincode.in/pincode/{$request->pincode}");

                    if ($pincodeResponse->successful()) {
                        $pincodeApiData = $pincodeResponse->json();

                        if (isset($pincodeApiData[0]['Status']) && $pincodeApiData[0]['Status'] === 'Success') {
                            $postOffices = $pincodeApiData[0]['PostOffice'] ?? [];
                            $postOffice = !empty($postOffices) ? $postOffices[0] : null;

                            if ($postOffice) {
                                $pincodeData = [
                                    'city' => $postOffice['District'] ?? null,
                                    'state' => $postOffice['State'] ?? null,
                                    'country' => 'India',
                                    'post_office' => $postOffice['Name'] ?? null,
                                    'division' => $postOffice['Division'] ?? null
                                ];

                                Log::info('Pincode auto-filled', [
                                    'pincode' => $request->pincode,
                                    'data' => $pincodeData
                                ]);
                            }
                        }
                    }
                } catch (\Exception $e) {
                    Log::warning('Pincode auto-fill failed', [
                        'pincode' => $request->pincode,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            // ✅ AADHAAR VERIFICATION
            if ($request->aadhar_number && $request->name) {
                try {
                    $aadhaarVerification = $this->docVerificationService->verifyAadhaar(
                        $request->aadhar_number,
                        $request->name
                    );
                    $verificationResults['aadhaar'] = $aadhaarVerification;
                    Log::info('Aadhaar verification successful');
                } catch (\Exception $e) {
                    Log::error('Aadhaar verification failed', ['error' => $e->getMessage()]);
                }
            }

            // ✅ PAN VERIFICATION
            if ($request->pan_number && $request->name) {
                try {
                    $panVerification = $this->docVerificationService->verifyPan(
                        $request->pan_number,
                        $request->name
                    );
                    $verificationResults['pan'] = $panVerification;
                    Log::info('PAN verification successful');
                } catch (\Exception $e) {
                    Log::error('PAN verification failed', ['error' => $e->getMessage()]);
                }
            }

            // ✅ GST HANDLING (ALWAYS MANUAL - NO API)
            if ($request->gst_number) {
                // Basic format validation (15 characters)
                if (strlen($request->gst_number) === 15) {
                    $verificationResults['gst_number'] = $request->gst_number;
                    $verificationResults['gst_verified'] = false;
                    $verificationResults['gst_manual'] = json_encode([
                        'manual' => true,
                        'gst_number' => $request->gst_number,
                        'reason' => 'No API available for GST verification',
                        'marked_at' => now()->toDateTimeString()
                    ]);
                    Log::info('GST number stored (manual)', [
                        'gst_number' => $request->gst_number
                    ]);
                } else {
                    Log::warning('Invalid GST number format', [
                        'gst_number' => $request->gst_number,
                        'length' => strlen($request->gst_number)
                    ]);
                }
            }

            // Get all request data
            $updateData = $request->except([
                'password_confirmation',
                'aadhar_front',
                'aadhar_back',
                'pan_image',
                'gst_image'  // ✅ GST image ko exclude karo
            ]);

            // ✅ AUTO-FILL CITY/STATE IF EMPTY (from pincode)
            if ($pincodeData) {
                $updateData['city'] = $updateData['city'] ?? $pincodeData['city'];
                $updateData['state'] = $updateData['state'] ?? $pincodeData['state'];
                $updateData['country'] = $updateData['country'] ?? $pincodeData['country'];
            }

            // Password handling
            if (!empty($updateData['password'])) {
                $updateData['password'] = Hash::make($updateData['password']);
            } else {
                unset($updateData['password']);
            }

            // Boolean handling
            if (isset($updateData['declaration'])) {
                $updateData['declaration'] = filter_var($updateData['declaration'], FILTER_VALIDATE_BOOLEAN);
            }

            if (isset($updateData['same_address'])) {
                $updateData['same_address'] = filter_var($updateData['same_address'], FILTER_VALIDATE_BOOLEAN);
            }

            // Handle document uploads (including GST image)
            $documentPaths = $this->handleDocumentUploads($request, $user->id);

            // Merge verification results
            $updateData = array_merge($updateData, $verificationResults, $documentPaths ?? []);
            $updateData['is_completed'] = true;

            $user->update($updateData);
            $user->refresh();

            DB::commit();

            $token = base64_encode($user->id . ':' . time() . ':' . $user->contact_number);

            Log::info('Registration completed successfully', [
                'user_id' => $user->id,
                'name' => $user->name,
                'gst_number' => $user->gst_number ?? null
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Registration completed successfully',
                'status' => 'registration_completed',
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'contact_number' => $user->contact_number,
                        'email' => $user->email,
                        'city' => $user->city,
                        'state' => $user->state,
                        'pincode' => $user->pincode,
                        'gst_number' => $user->gst_number,  // ✅ GST number response mein
                        'gst_verified' => $user->gst_verified ?? false,
                        'is_verified' => $user->is_verified,
                        'is_completed' => $user->is_completed
                    ],
                    'access_token' => $token,
                    'token_type' => 'bearer',
                    'expires_in' => 3600,
                    'verification_results' => $verificationResults,
                    'pincode_auto_filled' => $pincodeData !== null,
                    'location_data' => $pincodeData,
                    'uploaded_documents' => array_keys($documentPaths ?? []),
                    'gst_manual' => $user->gst_manual ? json_decode($user->gst_manual) : null  // ✅ GST manual status
                ]
            ], 200);
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    } catch (\Exception $e) {
        Log::error('Complete Registration Error', [
            'error' => $e->getMessage(),
            'line' => $e->getLine(),
            'file' => $e->getFile()
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Registration failed: ' . $e->getMessage(),
            'error_code' => 'REGISTRATION_ERROR'
        ], 500);
    }
}



    /**
     * 6. CHECK USER STATUS
     */
    public function checkUserStatus(Request $request)
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
            $user = User::where('contact_number', $contactNumber)->first();

            if (!$user) {
                return response()->json([
                    'success' => true,
                    'message' => 'User not found',
                    'status' => 'not_registered'
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'User status retrieved',
                'data' => [
                    'user_id' => $user->id,
                    'contact_number' => $user->contact_number,
                    'is_verified' => $user->is_verified,
                    'is_completed' => $user->is_completed,
                    'has_password' => !empty($user->password),
                    'status' => !$user->is_verified ? 'not_verified' : (!$user->is_completed ? 'verified_incomplete' : 'fully_registered')
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to check status'
            ], 500);
        }
    }

    /**
     * 7. GET USER PROFILE (ME)
     */
    public function me(Request $request)
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

            $userId = $tokenParts[0];
            $timestamp = $tokenParts[1];

            if ((time() - $timestamp) > 3600) {
                return response()->json([
                    'success' => false,
                    'message' => 'Token expired'
                ], 401);
            }

            $user = User::find($userId);

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'contact_number' => $user->contact_number,
                        'name' => $user->name,
                        'email' => $user->email,
                        'dob' => $user->dob,
                        'gender' => $user->gender,
                        'emergency_contact' => $user->emergency_contact,
                        'aadhar_number' => $user->aadhar_number ?
                            substr($user->aadhar_number, 0, 4) . '****' . substr($user->aadhar_number, -4) : null,
                        'pan_number' => $user->pan_number,
                        'full_address' => $user->full_address,
                        'state' => $user->state,
                        'city' => $user->city,
                        'pincode' => $user->pincode,
                        'country' => $user->country,
                        'bank_name' => $user->bank_name,
                        'account_number' => $user->account_number ?
                            '****' . substr($user->account_number, -4) : null,
                        'ifsc' => $user->ifsc,
                        'is_verified' => $user->is_verified,
                        'is_completed' => $user->is_completed,
                        'password_set' => !empty($user->password),
                        'created_at' => $user->created_at ? $user->created_at->format('Y-m-d H:i:s') : null,
                        'updated_at' => $user->updated_at ? $user->updated_at->format('Y-m-d H:i:s') : null
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get user info'
            ], 500);
        }
    }

    /**
     * 8. LOGOUT
     */
    public function logout(Request $request)
    {
        try {
            Log::info('User logged out');

            return response()->json([
                'success' => true,
                'message' => 'Successfully logged out'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Logout failed'
            ], 500);
        }
    }

    /**
     * 9. REFRESH TOKEN
     */
    public function refresh(Request $request)
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

            $userId = $tokenParts[0];
            $contactNumber = $tokenParts[2];

            $newToken = base64_encode($userId . ':' . time() . ':' . $contactNumber);

            return response()->json([
                'success' => true,
                'message' => 'Token refreshed successfully',
                'data' => [
                    'access_token' => $newToken,
                    'token_type' => 'bearer',
                    'expires_in' => 3600
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Token refresh failed'
            ], 401);
        }
    }

    /**
     * 10. HANDLE DOCUMENT UPLOADS
     */
    private function handleDocumentUploads(Request $request, $userId)
    {
        $documentPaths = [];

        // Aadhaar Front
        if ($request->hasFile('aadhar_front')) {
            $file = $request->file('aadhar_front');
            $filename = 'aadhaar_front_' . time() . '.' . $file->extension();
            $path = $file->storeAs("users/{$userId}/documents", $filename, 'public');
            $documentPaths['aadhar_front'] = $path;
        }

        // Aadhaar Back
        if ($request->hasFile('aadhar_back')) {
            $file = $request->file('aadhar_back');
            $filename = 'aadhaar_back_' . time() . '.' . $file->extension();
            $path = $file->storeAs("users/{$userId}/documents", $filename, 'public');
            $documentPaths['aadhar_back'] = $path;
        }

        // PAN Image
        if ($request->hasFile('pan_image')) {
            $file = $request->file('pan_image');
            $filename = 'pan_' . time() . '.' . $file->extension();
            $path = $file->storeAs("users/{$userId}/documents", $filename, 'public');
            $documentPaths['pan_image'] = $path;
        }

        // ✅ GST Image (NEW)
        if ($request->hasFile('gst_image')) {
            $file = $request->file('gst_image');
            $filename = 'gst_' . time() . '.' . $file->extension();
            $path = $file->storeAs("users/{$userId}/documents", $filename, 'public');
            $documentPaths['gst_image'] = $path;
            Log::info('GST image uploaded', ['path' => $path]);
        }

        return $documentPaths;
    }

    /**
     * 11. INITIALIZE AADHAAR VERIFICATION
     */
    public function initializeAadhaarVerification(Request $request)
    {
        try {
            $authHeader = $request->header('Authorization');
            if (!$authHeader || !str_contains($authHeader, 'Bearer ')) {
                return response()->json(['success' => false, 'message' => 'Authorization token required'], 401);
            }

            $token = str_replace('Bearer ', '', $authHeader);
            $decodedToken = base64_decode($token);
            $tokenParts = explode(':', $decodedToken);

            if (count($tokenParts) < 3) {
                return response()->json(['success' => false, 'message' => 'Invalid token format'], 401);
            }

            $userId = $tokenParts[0];
            $user = User::find($userId);

            if (!$user || !$user->is_verified) {
                return response()->json(['success' => false, 'message' => 'User not verified'], 403);
            }

            $redirectUrl = $request->redirect_url ?? config('app.url') . '/aadhaar-callback';
            $result = $this->docVerificationService->initializeDigilocker($redirectUrl);

            if (!$result['success']) {
                return response()->json(['success' => false, 'message' => 'Failed to initialize'], 400);
            }

            $user->update(['aadhaar_digilocker_client_id' => $result['data']['client_id']]);

            return response()->json([
                'success' => true,
                'message' => 'Aadhaar verification initialized',
                'data' => [
                    'verification_url' => $result['data']['url'],
                    'client_id' => $result['data']['client_id'],
                    'expires_in' => $result['data']['expiry_seconds']
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Initialize Aadhaar Error', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * 12. VERIFY AADHAAR FROM DIGILOCKER
     */
    public function verifyAadhaarDigilocker(Request $request)
    {
        try {
            $authHeader = $request->header('Authorization');
            if (!$authHeader || !str_contains($authHeader, 'Bearer ')) {
                return response()->json(['success' => false, 'message' => 'Authorization token required'], 401);
            }

            $token = str_replace('Bearer ', '', $authHeader);
            $decodedToken = base64_decode($token);
            $tokenParts = explode(':', $decodedToken);
            $userId = $tokenParts[0];
            $user = User::find($userId);

            if (!$user) {
                return response()->json(['success' => false, 'message' => 'User not found'], 404);
            }

            $validator = Validator::make($request->all(), [
                'client_id' => 'required|string',
                'name' => 'nullable|string|max:255'
            ]);

            if ($validator->fails()) {
                return response()->json(['success' => false, 'errors' => $validator->errors()], 400);
            }

            $clientId = $request->client_id ?? $user->aadhaar_digilocker_client_id;
            $nameToMatch = $request->name ?? $user->name ?? null;

            $result = $this->docVerificationService->downloadAadhaarDigilocker($clientId, $nameToMatch);

            if (!$result['success'] || !($result['verified'] ?? false)) {
                return response()->json([
                    'success' => false,
                    'message' => $result['message'] ?? 'Verification failed',
                    'details' => $result['details'] ?? null
                ], 400);
            }

            $user->update([
                'aadhaar_verified' => true,
                'aadhaar_verification_date' => now(),
                'aadhaar_verified_data' => json_encode($result['data']),
                'aadhar_number' => $result['data']['masked_aadhaar'] ?? null,
                'name' => $user->name ?: $result['data']['full_name'],
                'dob' => $user->dob ?: $result['data']['dob']
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Aadhaar verified successfully',
                'data' => [
                    'full_name' => $result['data']['full_name'],
                    'dob' => $result['data']['dob'],
                    'gender' => $result['data']['gender'],
                    'masked_aadhaar' => $result['data']['masked_aadhaar'],
                    'address' => $result['data']['full_address']
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Verify Aadhaar Error', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * 13. UPDATE REFERRAL EMPLOYEE CODE (App Install Time)
     * User only provides employee referral code, system finds user and updates
     */
    public function updateReferralCode(Request $request)
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

            // Get authenticated user from token
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

            $userId = $tokenParts[0];
            $user = User::find($userId);

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found'
                ], 404);
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

            // Update user with referral code and app install timestamp
            $user->referral_emp_id = $referralEmpId;
            $user->app_installed_at = now();

            // If you want to link with employee ID, uncomment:
            // $user->referred_by_employee_id = $employee->id ?? null;

            $user->save();

            Log::info('Referral code updated', [
                'user_id' => $user->id,
                'referral_emp_id' => $referralEmpId
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Employee referral code updated successfully',
                'data' => [
                    'user_id' => $user->id,
                    'referral_emp_id' => $user->referral_emp_id,
                    'app_installed_at' => $user->app_installed_at->toDateTimeString()
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Update referral code error', [
                'error' => $e->getMessage(),
                'line' => $e->getLine()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update referral code: ' . $e->getMessage()
            ], 500);
        }
    }
}
