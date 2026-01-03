<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\PlanController;
use App\Http\Controllers\Api\GoogleMapsController;
use App\Http\Controllers\Api\VendorAuthController;
use App\Http\Controllers\Api\VendorPlanController;
use App\Http\Controllers\Api\TruckBookingController;
use App\Http\Controllers\Api\VendorVehicleController;
use App\Http\Controllers\Api\PlanSubscriptionController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// ✅ USER AUTH ROUTES (No JWT Middleware)
Route::prefix('auth')->group(function () {
    // Public routes (no authentication required)
    Route::post('/send-otp', [AuthController::class, 'sendOtp']);
    Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);
    Route::post('/resend-otp', [AuthController::class, 'resendOtp']);
    Route::post('/login', [AuthController::class, 'login']);

    // Simple token routes (custom token validation)
    Route::post('/complete-registration', [AuthController::class, 'completeRegistration']);
    Route::post('/validate-token', [AuthController::class, 'validateToken']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/refresh', [AuthController::class, 'refresh']);

    // JWT routes (if JWT is configured properly)
    Route::post('/jwt-refresh', [AuthController::class, 'jwtRefresh']);
    Route::post('/jwt-logout', [AuthController::class, 'jwtLogout']);
    Route::get('/jwt-me', [AuthController::class, 'jwtMe']);
});

Route::prefix('vendor/auth')->group(function () {
    // ✅ REGISTRATION ROUTES
    Route::post('/send-otp', [VendorAuthController::class, 'sendOtp']);
    Route::post('/verify-otp', [VendorAuthController::class, 'verifyOtp']);
    Route::post('/resend-otp', [VendorAuthController::class, 'resendOtp']); // Registration resend

    // ✅ LOGIN ROUTES
    Route::post('/login-send-otp', [VendorAuthController::class, 'loginSendOtp']);
    Route::post('/login-verify-otp', [VendorAuthController::class, 'loginVerifyOtp']);
    Route::post('/login-resend-otp', [VendorAuthController::class, 'loginResendOtp']); // ✅ NEW LOGIN RESEND

    // ✅ PROTECTED ROUTES
    Route::post('/complete-registration', [VendorAuthController::class, 'completeRegistration']);
    Route::get('/profile', [VendorAuthController::class, 'vendorProfile']);
    Route::post('/logout', [VendorAuthController::class, 'vendorLogout']);
});

Route::prefix('plans')->group(function () {
    // Get all dynamic plans from database
    Route::get('/', [PlanController::class, 'getPlans']);

    // Subscribe to a plan (requires token)
    Route::post('/subscribe', [PlanController::class, 'subscribePlan']);

    // Get user's subscriptions (requires token)
    Route::get('/my-subscriptions', [PlanController::class, 'getUserSubscriptions']);

    // Contact sales for custom plan (requires token)
    Route::post('/contact-sales', [PlanController::class, 'contactSales']);

    // Cancel subscription (requires token)
    Route::post('/cancel', [PlanController::class, 'cancelSubscription']);
});

// Truck Booking Routes
Route::prefix('truck-booking')->group(function () {
    // Get form data (materials, truck types, specifications)
    Route::get('/form-data', [TruckBookingController::class, 'getFormData']);

    // Calculate distance and price
    Route::post('/calculate-price', [TruckBookingController::class, 'calculatePrice']);

    // Create booking (requires token)
    Route::post('/create', [TruckBookingController::class, 'createBooking']);

    // Get user's bookings (requires token)
    Route::get('/my-bookings', [TruckBookingController::class, 'getUserBookings']);
});


Route::prefix('maps')->group(function () {
    // Basic APIs
    Route::post('/distance', [GoogleMapsController::class, 'calculateDistance']);
    Route::post('/reverse-geocode', [GoogleMapsController::class, 'reverseGeocode']);
    Route::post('/geocode', [GoogleMapsController::class, 'geocode']);

    // Advanced APIs
    Route::post('/batch-distance', [GoogleMapsController::class, 'batchDistance']);
    Route::post('/search-places', [GoogleMapsController::class, 'searchPlaces']);

    // Utility APIs
    Route::get('/status', [GoogleMapsController::class, 'getApiStatus']);
});

// Vendor Vehicle Routes
Route::prefix('vendor-vehicle')->group(function () {
    // Get form data
    Route::get('/form-data', [VendorVehicleController::class, 'getVehicleFormData']);

    // List vehicle (requires token)
    Route::post('/list', [VendorVehicleController::class, 'listVehicle']);

    // Get vehicle status (requires token)
    Route::get('/status', [VendorVehicleController::class, 'getVehicleStatus']);
});

// Vendor Plan Routes
Route::prefix('vendor-plans')->group(function () {
    // Get all vendor plans
    Route::get('/', [VendorPlanController::class, 'getVendorPlans']);

    // Subscribe to plan (requires token)
    Route::post('/subscribe', [VendorPlanController::class, 'subscribeVendorPlan']);

    // Get my subscriptions (requires token)
    Route::get('/my-subscriptions', [VendorPlanController::class, 'getVendorSubscriptions']);
});


// ✅ DEBUG ROUTES
Route::get('/debug-env', function () {
    return response()->json([
        'surepass_token_present' => !empty(env('SUREPASS_TOKEN')),
        'surepass_token_length' => strlen(env('SUREPASS_TOKEN') ?? ''),
        'surepass_base_url' => env('SUREPASS_BASE_URL'),
        'bypass_real_api' => env('BYPASS_REAL_API'),
        'timestamp' => now()
    ]);
});

Route::post('/test-surepass-rc', function (Request $request) {
    try {
        $rcNumber = $request->input('rc_number');
        $name = $request->input('name');

        $docService = new App\Services\DocumentVerificationService();
        $result = $docService->verifyRc($rcNumber, $name);

        return response()->json([
            'surepass_test' => true,
            'input' => ['rc_number' => $rcNumber, 'name' => $name],
            'surepass_result' => $result,
            'timestamp' => now()
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'surepass_test' => false,
            'error' => $e->getMessage()
        ], 500);
    }
});

Route::post('/test-rc-verification-debug', function (Request $request) {
    try {
        $rcNumber = $request->input('rc_number');
        $name = $request->input('name');

        if (!$rcNumber || !$name) {
            return response()->json([
                'success' => false,
                'message' => 'RC number and name are required'
            ], 400);
        }

        $docService = new App\Services\DocumentVerificationService();
        $result = $docService->verifyRc($rcNumber, $name);

        return response()->json([
            'success' => true,
            'input' => ['rc_number' => $rcNumber, 'name' => $name],
            'verification_result' => $result,
            'timestamp' => now()
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => $e->getMessage()
        ], 500);
    }
});

// ✅ HEALTH CHECK
Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'timestamp' => now(),
        'version' => '1.0.0'
    ]);
});

// ✅ DEFAULT ROUTE
Route::get('/', function () {
    return response()->json([
        'message' => 'API is working',
        'endpoints' => [
            'auth' => '/api/auth/*',
            'vendor' => '/api/vendor/auth/*',
            'health' => '/api/health'
        ],
        'timestamp' => now()
    ]);
});


Route::get('/test-env-token', function () {
    return response()->json([
        'surepass_token_present' => !empty(env('SUREPASS_TOKEN')),
        'surepass_token_length' => strlen(env('SUREPASS_TOKEN') ?? ''),
        'surepass_token_first_20' => substr(env('SUREPASS_TOKEN') ?? '', 0, 20),
        'all_env_vars' => array_keys($_ENV),
        'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'unknown'
    ]);
});
Route::get('/debug-token-service', function () {
    try {
        $service = new App\Services\DocumentVerificationService();

        return response()->json([
            'constructor_success' => true,
            'env_token_present' => !empty(env('SUREPASS_TOKEN')),
            'env_token_length' => strlen(env('SUREPASS_TOKEN') ?? ''),
            'service_initialized' => true,
            'timestamp' => now()
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'constructor_success' => false,
            'error_message' => $e->getMessage(),
            'error_line' => $e->getLine(),
            'env_token_present' => !empty(env('SUREPASS_TOKEN')),
            'env_token_length' => strlen(env('SUREPASS_TOKEN') ?? ''),
            'timestamp' => now()
        ]);
    }
});

// Test Digilocker Aadhaar
Route::get('/test-digilocker-aadhaar/{aadhaar}/{name}', function ($aadhaar, $name) {
    $service = new App\Services\DocumentVerificationService();
    $result = $service->verifyAadhaar($aadhaar, $name);

    return response()->json([
        'test_type' => 'digilocker_aadhaar_verification',
        'input' => ['aadhaar' => $aadhaar, 'name' => $name],
        'result' => $result,
        'timestamp' => now()
    ]);
});

// Test RC Text
Route::get('/test-rc-text/{rcNumber}/{name}', function ($rcNumber, $name) {
    $service = new App\Services\DocumentVerificationService();
    $result = $service->verifyRc($rcNumber, $name);

    return response()->json([
        'test_type' => 'rc_text_verification',
        'input' => ['rc_number' => $rcNumber, 'name' => $name],
        'result' => $result,
        'timestamp' => now()
    ]);
});


Route::post('/test-registration-bypass', function (\Illuminate\Http\Request $request) {
    try {
        Log::info('Test registration bypass started');

        // Basic validation
        if (!$request->name || !$request->email) {
            return response()->json([
                'success' => false,
                'message' => 'Name and email are required'
            ]);
        }

        // Bypass all API calls - direct registration
        $user = \App\Models\User::create([
            'name' => $request->name,
            'email' => $request->email,
            'contact_number' => $request->contact_number ?? '1234567890',
            'password' => Hash::make($request->password ?? 'test123'),
            'dob' => $request->dob ?? '1990-01-01',
            'gender' => $request->gender ?? 'male',
            'aadhar_number' => $request->aadhar_number,
            'pan_number' => $request->pan_number,
            'full_address' => $request->full_address ?? 'Test Address',
            'state' => $request->state ?? 'Delhi',
            'city' => $request->city ?? 'New Delhi',
            'pincode' => $request->pincode ?? '110001',
            'bank_name' => $request->bank_name ?? 'Test Bank',
            'account_number' => $request->account_number ?? '1234567890',
            'ifsc' => $request->ifsc ?? 'TEST0000123',
            'is_verified' => true,
            'is_completed' => true,
            'declaration' => true
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Test registration completed without API calls',
            'user' => $user,
            'note' => 'This bypasses all verification APIs for testing'
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Test registration failed: ' . $e->getMessage()
        ]);
    }
});

Route::get('/debug-token-loading', function () {
    $service = new App\Services\DocumentVerificationService();

    return response()->json([
        'env_token' => env('SUREPASS_TOKEN') ? 'Present' : 'Missing',
        'env_token_length' => strlen(env('SUREPASS_TOKEN') ?? ''),
        'config_token' => config('services.surepass.token') ? 'Present' : 'Missing',
        'direct_env' => !empty($_ENV['SUREPASS_TOKEN']) ? 'Present' : 'Missing',
        'service_initialized' => true,
        'timestamp' => now()
    ]);
});
