<?php

namespace App\Http\Controllers\Api;

use Carbon\Carbon;
use App\Models\User;
use App\Services\SmsService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use App\Http\Requests\Api\SendOtpRequest;
use App\Http\Requests\Api\VerifyOtpRequest;
use App\Http\Requests\Api\ResendOtpRequest;
use App\Services\DocumentVerificationService;
use App\Http\Requests\Api\CompleteRegistrationRequest;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    private $smsService;
    private $docVerificationService;

    public function __construct()
    {
        $this->smsService = new SmsService();
        $this->docVerificationService = new DocumentVerificationService();
    }

    // ✅ 1. CHECK USER STATUS
    public function checkUserStatus(Request $request): JsonResponse
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
                    'message' => 'User not found. Please register first.',
                    'status' => 'not_registered',
                    'action' => 'send_otp',
                    'redirect' => 'registration_page'
                ]);
            }

            if ($user->is_verified && !$user->is_completed) {
                $token = base64_encode($user->id . ':' . time() . ':' . $contactNumber);

                return response()->json([
                    'success' => true,
                    'message' => 'Please redirect to complete registration page',
                    'status' => 'verified_incomplete',
                    'action' => 'redirect_to_complete_registration',
                    'redirect' => 'complete_registration_page',
                    'access_token' => $token,
                    'token_type' => 'bearer',
                    'expires_in' => 3600,
                    'user' => [
                        'id' => $user->id,
                        'contact_number' => $user->contact_number,
                        'is_verified' => $user->is_verified,
                        'is_completed' => $user->is_completed
                    ]
                ]);
            }

            if ($user->is_verified && $user->is_completed) {
                return response()->json([
                    'success' => true,
                    'message' => 'User already registered. Please login.',
                    'status' => 'already_completed',
                    'action' => 'redirect_to_login',
                    'redirect' => 'login_page',
                    'user' => [
                        'id' => $user->id,
                        'contact_number' => $user->contact_number,
                        'name' => $user->name,
                        'email' => $user->email,
                        'is_verified' => $user->is_verified,
                        'is_completed' => $user->is_completed
                    ]
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'User found but not verified. Please verify OTP.',
                'status' => 'not_verified',
                'action' => 'send_otp',
                'redirect' => 'otp_verification_page',
                'user' => [
                    'id' => $user->id,
                    'contact_number' => $user->contact_number,
                    'is_verified' => $user->is_verified,
                    'is_completed' => $user->is_completed
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Check User Status Error', [
                'contact_number' => $contactNumber ?? null,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to check user status: ' . $e->getMessage()
            ], 500);
        }
    }

    // ✅ 2. SEND OTP (4-DIGIT)
    public function sendOtp(SendOtpRequest $request): JsonResponse
    {
        try {
            $contactNumber = $request->contact_number;

            Log::info('Send OTP request', ['contact_number' => $contactNumber]);

            $user = User::where('contact_number', $contactNumber)->first();

            if ($user) {
                if ($user->is_verified && $user->is_completed) {
                    return response()->json([
                        'success' => false,
                        'message' => 'User already registered and verified',
                        'status' => 'already_completed',
                        'user' => [
                            'id' => $user->id,
                            'contact_number' => $user->contact_number,
                            'name' => $user->name,
                            'email' => $user->email,
                            'is_verified' => $user->is_verified,
                            'is_completed' => $user->is_completed
                        ]
                    ], 409);
                }

                if ($user->is_verified && !$user->is_completed) {
                    return response()->json([
                        'success' => false,
                        'message' => 'User already verified. Please complete registration',
                        'status' => 'verified_incomplete',
                        'user' => [
                            'id' => $user->id,
                            'contact_number' => $user->contact_number,
                            'is_verified' => $user->is_verified,
                            'is_completed' => $user->is_completed
                        ]
                    ], 409);
                }
            }

            if (!$user) {
                $user = User::create(['contact_number' => $contactNumber]);
                Log::info('New user created', ['user_id' => $user->id]);
            }

            // ✅ Generate 4-digit OTP
            $otp = $user->generateOtp();

            try {
                $smsResult = $this->smsService->sendOtp($contactNumber, $otp);
                Log::info('OTP SMS sent successfully', [
                    'contact_number' => $contactNumber,
                    'otp_length' => strlen($otp)
                ]);
            } catch (\Exception $smsError) {
                Log::error('SMS sending failed', [
                    'error' => $smsError->getMessage()
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'OTP sent successfully to ' . $contactNumber,
                'data' => [
                    'contact_number' => $contactNumber,
                    'contact_number_masked' => substr($contactNumber, 0, 2) . 'XXXXXX' . substr($contactNumber, -2),
                    'otp' => env('APP_DEBUG') ? $otp : null, // Only in debug mode
                    'otp_length' => 4,
                    'expires_in_minutes' => 10,
                    'expires_at' => $user->otp_expires_at->format('Y-m-d H:i:s'),
                    'user_id' => $user->id,
                    'status' => 'otp_sent'
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Send OTP Error', [
                'contact_number' => $contactNumber ?? null,
                'error' => $e->getMessage(),
                'line' => $e->getLine()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to send OTP: ' . $e->getMessage()
            ], 500);
        }
    }

    // ✅ 3. VERIFY OTP (4-DIGIT)
    public function verifyOtp(VerifyOtpRequest $request): JsonResponse
    {
        try {
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

            if ($user->is_verified) {
                if ($user->is_completed) {
                    return response()->json([
                        'success' => false,
                        'message' => 'User already verified and registered',
                        'status' => 'already_verified_completed'
                    ], 409);
                } else {
                    $token = base64_encode($user->id . ':' . time() . ':' . $contactNumber);
                    return response()->json([
                        'success' => true,
                        'message' => 'User already verified. Please complete registration.',
                        'status' => 'verified_incomplete',
                        'data' => [
                            'access_token' => $token,
                            'token_type' => 'bearer',
                            'expires_in' => 3600
                        ]
                    ]);
                }
            }

            if (!$user->isOtpValid($otpInput)) {
                $user->incrementOtpAttempts();

                Log::warning('Invalid OTP attempt', [
                    'user_id' => $user->id,
                    'attempts' => $user->otp_attempts,
                    'remaining' => max(0, 3 - $user->otp_attempts)
                ]);

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

            $user->update([
                'is_verified' => true,
                'otp' => null,
                'otp_expires_at' => null,
                'otp_attempts' => 0
            ]);

            $token = base64_encode($user->id . ':' . time() . ':' . $contactNumber);

            Log::info('OTP verified successfully', [
                'user_id' => $user->id,
                'contact_number' => $contactNumber
            ]);

            return response()->json([
                'success' => true,
                'message' => 'OTP verified successfully. Please complete registration.',
                'status' => 'otp_verified',
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'contact_number' => $user->contact_number,
                        'is_verified' => $user->is_verified,
                        'is_completed' => $user->is_completed
                    ],
                    'access_token' => $token,
                    'token_type' => 'bearer',
                    'expires_in' => 3600,
                    'next_step' => 'complete_registration'
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

    // ✅ 4. RESEND OTP (4-DIGIT)
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

            Log::info('Resend OTP request', ['contact_number' => $contactNumber]);

            $user = User::where('contact_number', $contactNumber)->first();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found. Please register first.',
                    'status' => 'user_not_found'
                ], 404);
            }

            if ($user->is_verified && $user->is_completed) {
                return response()->json([
                    'success' => false,
                    'message' => 'User already verified and registered',
                    'status' => 'already_completed'
                ], 409);
            }

            // ✅ Generate new 4-digit OTP
            $otp = $user->generateOtp();
            $user->incrementResendCount();

            try {
                $smsResult = $this->smsService->sendOtp($contactNumber, $otp);
                Log::info('OTP resent successfully', [
                    'contact_number' => $contactNumber,
                    'resend_count' => $user->otp_resend_count
                ]);
            } catch (\Exception $smsError) {
                Log::error('Resend SMS failed', [
                    'error' => $smsError->getMessage()
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'OTP resent successfully to ' . $contactNumber,
                'data' => [
                    'contact_number' => $contactNumber,
                    'contact_number_masked' => substr($contactNumber, 0, 2) . 'XXXXXX' . substr($contactNumber, -2),
                    'otp' => env('APP_DEBUG') ? $otp : null,
                    'otp_length' => 4,
                    'expires_in_minutes' => 10,
                    'expires_at' => $user->otp_expires_at->format('Y-m-d H:i:s'),
                    'resend_count' => $user->otp_resend_count,
                    'status' => 'otp_resent'
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Resend OTP Error', [
                'error' => $e->getMessage(),
                'line' => $e->getLine()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to resend OTP: ' . $e->getMessage()
            ], 500);
        }
    }

    // ✅ 5. COMPLETE REGISTRATION
    public function completeRegistration(Request $request): JsonResponse
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
                    'message' => 'User registration already completed'
                ], 409);
            }

            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'email' => 'nullable|email|unique:users,email,' . $userId,
                'dob' => 'nullable|date|before:today',
                'gender' => 'nullable|in:male,female,other',
                'emergency_contact' => 'nullable|string|digits:10',
                'aadhar_number' => 'nullable|string|digits:12',
                'pan_number' => 'nullable|string|max:10',
                'full_address' => 'nullable|string|max:500',
                'state' => 'nullable|string|max:100',
                'city' => 'nullable|string|max:100',
                'pincode' => 'nullable|string|digits:6',
                'country' => 'nullable|string|max:100',
                'bank_name' => 'nullable|string|max:100',
                'account_number' => 'nullable|string|max:20',
                'ifsc' => 'nullable|string|max:11',
                'password' => 'nullable|string|min:8|confirmed',
                'password_confirmation' => 'nullable|string|min:8',
                'aadhar_front' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
                'aadhar_back' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
                'pan_image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
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

            DB::beginTransaction();

            try {
                $verificationResults = [];

                if ($request->aadhar_number && $request->name) {
                    try {
                        $aadhaarVerification = $this->docVerificationService->verifyAadhaar(
                            $request->aadhar_number,
                            $request->name
                        );
                        $verificationResults['aadhaar'] = $aadhaarVerification;
                        Log::info('Aadhaar verification completed', [
                            'success' => $aadhaarVerification['success'] ?? false
                        ]);
                    } catch (\Exception $e) {
                        Log::error('Aadhaar verification failed', ['error' => $e->getMessage()]);
                    }
                }

                if ($request->pan_number && $request->name) {
                    try {
                        $panVerification = $this->docVerificationService->verifyPan(
                            $request->pan_number,
                            $request->name
                        );
                        $verificationResults['pan'] = $panVerification;
                        Log::info('PAN verification completed', [
                            'success' => $panVerification['success'] ?? false
                        ]);
                    } catch (\Exception $e) {
                        Log::error('PAN verification failed', ['error' => $e->getMessage()]);
                    }
                }

                $documentPaths = $this->handleDocumentUploads($request, $user->id);
                $validatedData = $validator->validated();

                if (!empty($validatedData['password'])) {
                    $validatedData['password'] = Hash::make($validatedData['password']);
                } else {
                    unset($validatedData['password']);
                }

                unset($validatedData['password_confirmation']);

                if (isset($validatedData['declaration'])) {
                    $validatedData['declaration'] = filter_var($validatedData['declaration'], FILTER_VALIDATE_BOOLEAN);
                }

                if (isset($validatedData['same_address'])) {
                    $validatedData['same_address'] = filter_var($validatedData['same_address'], FILTER_VALIDATE_BOOLEAN);
                }

                $updateData = array_merge($validatedData, $documentPaths ?? []);
                $updateData['is_completed'] = true;

                $user->update($updateData);
                $user->refresh();

                DB::commit();

                Log::info('Registration completed successfully', [
                    'user_id' => $user->id,
                    'name' => $user->name
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
                            'is_verified' => $user->is_verified,
                            'is_completed' => $user->is_completed
                        ],
                        'verification_results' => $verificationResults,
                        'uploaded_documents' => array_keys($documentPaths ?? [])
                    ]
                ]);

            } catch (\Exception $e) {
                DB::rollback();
                throw $e;
            }

        } catch (\Exception $e) {
            Log::error('Complete Registration Error', [
                'error' => $e->getMessage(),
                'line' => $e->getLine()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Registration failed: ' . $e->getMessage()
            ], 500);
        }
    }

    // ✅ 6. LOGIN
    public function login(Request $request): JsonResponse
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

    // ✅ 7. GET USER PROFILE (ME)
    public function me(Request $request): JsonResponse
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

    // ✅ 8. LOGOUT
    public function logout(Request $request): JsonResponse
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

    // ✅ 9. REFRESH TOKEN
    public function refresh(Request $request): JsonResponse
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

    // ✅ 10. HELPER: Handle Document Uploads
    private function handleDocumentUploads($request, $userId): array
    {
        $paths = [];

        try {
            Storage::disk('public')->makeDirectory("documents/{$userId}/aadhaar");
            Storage::disk('public')->makeDirectory("documents/{$userId}/pan");

            if ($request->hasFile('aadhar_front')) {
                $file = $request->file('aadhar_front');
                if ($file->isValid()) {
                    $fileName = 'aadhar_front_' . time() . '.' . $file->getClientOriginalExtension();
                    $path = $file->storeAs("documents/{$userId}/aadhaar", $fileName, 'public');
                    $paths['aadhar_front'] = $path;
                    Log::info('Aadhaar front uploaded', ['path' => $path]);
                }
            }

            if ($request->hasFile('aadhar_back')) {
                $file = $request->file('aadhar_back');
                if ($file->isValid()) {
                    $fileName = 'aadhar_back_' . time() . '.' . $file->getClientOriginalExtension();
                    $path = $file->storeAs("documents/{$userId}/aadhaar", $fileName, 'public');
                    $paths['aadhar_back'] = $path;
                    Log::info('Aadhaar back uploaded', ['path' => $path]);
                }
            }

            if ($request->hasFile('pan_image')) {
                $file = $request->file('pan_image');
                if ($file->isValid()) {
                    $fileName = 'pan_image_' . time() . '.' . $file->getClientOriginalExtension();
                    $path = $file->storeAs("documents/{$userId}/pan", $fileName, 'public');
                    $paths['pan_image'] = $path;
                    Log::info('PAN image uploaded', ['path' => $path]);
                }
            }

        } catch (\Exception $e) {
            Log::error('File Upload Error', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
        }

        return $paths;
    }
}
