<?php
// routes/api.php

use Illuminate\Support\Facades\Route;
// use App\Services\GoogleMapsController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\PlanController;
use App\Http\Controllers\Api\PincodeController;
use App\Http\Controllers\Api\VehicleController;
use App\Http\Controllers\Api\UserTypeController;
use App\Http\Controllers\Api\GoogleMapsController;
use App\Http\Controllers\Api\VendorAuthController;
use App\Http\Controllers\Api\VendorPlanController;
use App\Http\Controllers\Api\VendorFleetController;
use App\Http\Controllers\Api\TruckBookingController;
use App\Http\Controllers\Api\VendorBookingController;
use App\Http\Controllers\Api\VendorVehicleController;
use App\Http\Controllers\Api\RazorpayWebhookController;
use App\Http\Controllers\Api\VendorAvailabilityController;
use App\Http\Controllers\Api\ReviewController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// ✅ USER AUTH ROUTES
Route::prefix('auth')->group(function () {
    Route::post('/send-otp', [AuthController::class, 'sendOtp']);
    Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);
    Route::post('/resend-otp', [AuthController::class, 'resendOtp']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/complete-registration', [AuthController::class, 'completeRegistration']);
    Route::post('/validate-token', [AuthController::class, 'validateToken']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/refresh', [AuthController::class, 'refresh']);
    Route::post('/check-user-status', [AuthController::class, 'checkUserStatus']);

    // ✅ NEW: Aadhaar DigiLocker
    Route::post('/aadhaar/initialize', [AuthController::class, 'initializeAadhaarVerification']);
    Route::post('/aadhaar/verify', [AuthController::class, 'verifyAadhaarDigilocker']);
});

// Referral code update (requires auth token)
Route::post('/auth/update-referral', [AuthController::class, 'updateReferralCode']);

Route::post('/vendor/update-referral', [VendorAuthController::class, 'updateReferralCode']);
// ✅ VENDOR AUTH ROUTES
Route::prefix('vendor/auth')->group(function () {
    Route::post('/check-vendor-status', [VendorAuthController::class, 'checkVendorStatus']);
    Route::post('/send-otp', [VendorAuthController::class, 'sendOtp']);
    Route::post('/verify-otp', [VendorAuthController::class, 'verifyOtp']);
    Route::post('/resend-otp', [VendorAuthController::class, 'resendOtp']);
    Route::post('/login-send-otp', [VendorAuthController::class, 'loginSendOtp']);
    Route::post('/login-verify-otp', [VendorAuthController::class, 'loginVerifyOtp']);
    Route::post('/login-resend-otp', [VendorAuthController::class, 'loginResendOtp']);
    Route::post('/complete-registration', [VendorAuthController::class, 'completeRegistration']);
    Route::get('/profile', [VendorAuthController::class, 'vendorProfile']);
    Route::post('/logout', [VendorAuthController::class, 'vendorLogout']);
    Route::post('/debug', [VendorAuthController::class, 'debugRequest']);
    // ✅ Aadhaar DigiLocker Verification
    Route::post('/aadhaar/initialize', [VendorAuthController::class, 'initializeAadhaarDigilocker']);
    Route::post('/aadhaar/verify', [VendorAuthController::class, 'verifyAadhaarDigilocker']);
    Route::post('/verify-bank-account', [VendorAuthController::class, 'verifyBankAccount']);
});


Route::post('/plans/webhook', [PlanController::class, 'handleWebhook']);
Route::post('/plans/webhook/razorpay', [PlanController::class, 'handleWebhook']);

Route::prefix('plans')->group(function () {

    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    // ✅ GET PLANS (Public - No Auth Required)
    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    Route::get('/', [PlanController::class, 'getPlans']);

    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    // ✅ SUBSCRIPTION CREATION (Protected - Requires Auth Token)
    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    Route::post('/subscribe', [PlanController::class, 'subscribePlan']);

    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    // ✅ GET USER SUBSCRIPTIONS (Protected - Requires Auth Token)
    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    Route::get('/my-subscriptions', [PlanController::class, 'getUserSubscriptions']);
    Route::get('/subscriptions', [PlanController::class, 'getUserSubscriptions']); // Alias

    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    // ✅ CANCEL SUBSCRIPTION (Protected - Requires Auth Token)
    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    Route::post('/cancel', [PlanController::class, 'cancelSubscription']);
    Route::post('/cancel-subscription', [PlanController::class, 'cancelSubscription']); // Alias

    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    // ✅ PAYMENT HISTORY (Protected - Requires Auth Token)
    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    Route::get('/payment-history', [PlanController::class, 'getPaymentHistory']);

    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    // ✅ PAYMENT CALLBACKS & VERIFICATION (Public - No Auth Required)
    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    Route::get('/payment-callback', [PlanController::class, 'paymentCallback']);
    Route::post('/subscriptions/verify-payment', [PlanController::class, 'verifyPayment']);

    Route::post('/payment-failed', [PlanController::class, 'paymentFailed']);

    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    // ✅ CONTACT SALES (Public - No Auth Required)
    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    Route::post('/contact-sales', [PlanController::class, 'contactSales']);
});


Route::prefix('plans')->group(function () {
    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    // ✅ PUBLIC ROUTES (No Auth Required)
    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    Route::get('/', [PlanController::class, 'getPlans']);
    Route::post('/webhook/razorpay', [PlanController::class, 'handleRazorpayWebhook']);
    Route::get('/payment-callback', [PlanController::class, 'paymentCallback']);
    Route::post('/verify-payment', [PlanController::class, 'verifyPayment']);
    Route::post('/payment-failed', [PlanController::class, 'paymentFailed']);
    Route::post('/contact-sales', [PlanController::class, 'contactSales']);

    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    // ✅ PROTECTED ROUTES (Auth Required)
    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    Route::post('/subscribe', [PlanController::class, 'subscribePlan']);
    Route::get('/my-subscriptions', [PlanController::class, 'getUserSubscriptions']);
    Route::get('/subscriptions', [PlanController::class, 'getUserSubscriptions']);
    Route::post('/cancel', [PlanController::class, 'cancelSubscription']);
    Route::post('/cancel-subscription', [PlanController::class, 'cancelSubscription']);
    Route::get('/payment-history', [PlanController::class, 'getPaymentHistory']);
});

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
// ✅ RAZORPAY WEBHOOK (PUBLIC - NO AUTH - SIGNATURE VERIFIED IN CONTROLLER)
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

Route::post('/razorpay/webhook', [RazorpayWebhookController::class, 'handleWebhook']);

// routes/api.php - ADD THIS ROUTE
Route::post('/webhook/razorpay', [PlanController::class, 'handleRazorpayWebhook']);


Route::get('/pincode/location', [PincodeController::class, 'getLocationByPincode']);
Route::get('/pincode/search', [PincodeController::class, 'searchPincodeByCity']);

// // Vendor Plan Routes
// Route::prefix('vendor/plans')->group(function () {
//     Route::get('/', [VendorPlanController::class, 'getVendorPlans']);
//     Route::post('/create-order', [VendorPlanController::class, 'createPlanOrder']);
//     Route::post('/verify-payment', [VendorPlanController::class, 'verifyPayment']);
//     Route::post('/payment-failed', [VendorPlanController::class, 'paymentFailed']);
//     Route::get('/payment-history', [VendorPlanController::class, 'getPaymentHistory']);
//     Route::get('/subscriptions', [VendorPlanController::class, 'getVendorSubscriptions']);
// });

Route::prefix('vendor-plans')->group(function () {
    // Get all plans
    Route::get('/', [VendorPlanController::class, 'getVendorPlans']);

    // Subscribe to plan (Creates Razorpay order)
    Route::post('/subscribe', [VendorPlanController::class, 'subscribeVendorPlan']);

    // Verify payment after Razorpay checkout
    Route::post('/verify-payment', [VendorPlanController::class, 'verifyPayment']);

    // Handle payment failure
    Route::post('/payment-failed', [VendorPlanController::class, 'paymentFailed']);

    // Get vendor's subscriptions
    Route::get('/subscriptions', [VendorPlanController::class, 'getVendorSubscriptions']);

    // Get payment history
    Route::get('/payment-history', [VendorPlanController::class, 'getPaymentHistory']);
});

// Vendor Fleet Management Routes (For Fleet Owners)
Route::prefix('vendor/fleet')->group(function () {
    Route::post('/add-vehicle', [VendorFleetController::class, 'addVehicle']);
    Route::get('/vehicles', [VendorFleetController::class, 'getFleetVehicles']);
    Route::get('/vehicles/{vehicleId}', [VendorFleetController::class, 'getVehicleDetails']);
    Route::put('/vehicles/{vehicleId}', [VendorFleetController::class, 'updateVehicle']);
    Route::delete('/vehicles/{vehicleId}', [VendorFleetController::class, 'deleteVehicle']);
    Route::post('/vehicles/{vehicleId}/toggle-availability', [VendorFleetController::class, 'toggleAvailability']);
});


// ✅ WEBHOOK (Public - No Auth)
Route::post('/vendor-plans/webhook', [VendorPlanController::class, 'handleWebhook']);

// ✅ VENDOR PLAN ROUTES
Route::prefix('vendor-plans')->group(function () {
    Route::get('/', [VendorPlanController::class, 'getVendorPlans']);
    Route::post('/subscribe', [VendorPlanController::class, 'subscribeVendorPlan']);
    Route::get('/my-subscriptions', [VendorPlanController::class, 'getVendorSubscriptions']);
    // ✅ ADD THIS
    Route::get('/payment-callback', [VendorPlanController::class, 'paymentCallback']);

    Route::post('/verify-payment', [VendorPlanController::class, 'verifyPayment']);
    Route::post('/payment-failed', [VendorPlanController::class, 'paymentFailed']);
    Route::get('/subscriptions', [VendorPlanController::class, 'getVendorSubscriptions']);
    Route::get('/payment-history', [VendorPlanController::class, 'getPaymentHistory']);
    // ✅ Razorpay Webhook
    Route::post('/razorpay/webhook', [App\Http\Controllers\Api\VendorPlanController::class, 'handleRazorpayWebhook']);
});

// ✅ VENDOR VEHICLE ROUTES
Route::prefix('vendor-vehicle')->group(function () {
    Route::get('/form-data', [VendorVehicleController::class, 'getVehicleFormData']);
    Route::post('/list', [VendorVehicleController::class, 'listVehicle']);
    Route::get('/status', [VendorVehicleController::class, 'getVehicleStatus']);
});

// ✅ VENDOR AVAILABILITY ROUTES
Route::prefix('vendor/availability')->group(function () {
    Route::post('/go-online', [VendorAvailabilityController::class, 'goOnline']);
    Route::post('/go-offline', [VendorAvailabilityController::class, 'goOffline']);
    Route::post('/update-location', [VendorAvailabilityController::class, 'updateLocation']);
    Route::get('/status', [VendorAvailabilityController::class, 'getStatus']);
    Route::post('/toggle', [VendorAvailabilityController::class, 'toggleAvailability']);
    Route::post('/nearby-vendors', [VendorAvailabilityController::class, 'getNearbyVendors']);
});

// ✅ TRUCK BOOKING ROUTES
// Route::prefix('truck-booking')->group(function () {
//     Route::get('/form-data', [TruckBookingController::class, 'getFormData']);
//     Route::post('/calculate-price', [TruckBookingController::class, 'calculatePrice']);
//     Route::post('/create', [TruckBookingController::class, 'createBooking']);
//     Route::get('/my-bookings', [TruckBookingController::class, 'getUserBookings']);
//     // Step 1: Get vehicle categories/models
//     Route::get('/vehicle-categories', [TruckBookingController::class, 'getVehicleCategories']);

//     // Step 2: Get available vendors (30km radius with filters)
//     Route::post('/available-vendors', [TruckBookingController::class, 'getAvailableVendors']);

//     // Step 3: Calculate price for selected vendor
//     Route::post('/calculate-vendor-price', [TruckBookingController::class, 'calculateVendorPrice']);

//     // Step 4: Create booking with payment method
//     Route::post('/create-with-vendor', [TruckBookingController::class, 'createBookingWithVendor']);
// });
// ========================================
// USER BOOKING ROUTES
// ========================================




Route::prefix('truck-booking')->group(function () {

    // ========== OLD FLOW (Keep for backward compatibility) ==========
    Route::get('/form-data', [TruckBookingController::class, 'getFormData']);
    Route::post('/calculate-price', [TruckBookingController::class, 'calculatePrice']);
    Route::post('/create', [TruckBookingController::class, 'createBooking']);

    // ========== NEW FLOW (Manual Vendor Selection) ==========

    // Step 1: Get vehicle categories/models
    Route::get('/vehicle-categories', [TruckBookingController::class, 'getVehicleCategories']);

    // Step 2: Get available vendors (30km radius with filters)
    Route::post('/available-vendors', [TruckBookingController::class, 'getAvailableVendors']);
    Route::post('/calculate-price', [TruckBookingController::class, 'calculateVendorPrice']);

    // Step 3: Calculate price for selected vendor
    Route::post('/calculate-vendor-price', [TruckBookingController::class, 'calculateVendorPrice']);

    // Step 4: Create booking with payment method (Manual vendor selection)
    Route::post('/create-with-vendor', [TruckBookingController::class, 'createBookingWithVendor']);

    // ========== AUTO VENDOR SEARCH (Ola/Uber Style) ==========

    // ✅ NEW: Create booking with auto vendor search
    Route::post('/create-auto-search', [TruckBookingController::class, 'createBookingWithAutoVendorSearch']);

    // ========== BOOKING MANAGEMENT ==========

    // Get user's bookings
    Route::get('/my-bookings', [TruckBookingController::class, 'getUserBookings']); // All
    Route::get('/my-bookings/active', [TruckBookingController::class, 'getActiveBookings']);
    Route::get('/my-bookings/completed', [TruckBookingController::class, 'getCompletedBookings']);
    Route::get('/my-bookings/cancelled', [TruckBookingController::class, 'getCancelledBookings']);
    Route::get('/my-bookings/pending', [TruckBookingController::class, 'getPendingBookings']);


    // Single booking details
    Route::get('/booking/{bookingId}', [TruckBookingController::class, 'getBookingDetails']);

    // Update/Adjust Price
    Route::post('/update-price/{bookingId}', [TruckBookingController::class, 'updatePrice']);

    // Cancel Booking
    Route::post('/cancel/{bookingId}', [TruckBookingController::class, 'cancelBooking']);

    // ========== TRACKING APIs ==========

    Route::get('/track/{bookingId}', [TruckBookingController::class, 'trackBooking']);
    Route::post('/update-location/{bookingId}', [TruckBookingController::class, 'updateVendorLocation']); // Vendor app
    Route::get('/location-history/{bookingId}', [TruckBookingController::class, 'getLocationHistory']);
});

// ========================================
// VENDOR BOOKING ROUTES (Ola/Uber Style)
// ========================================
Route::prefix('vendor')->group(function () {

    // ========== BOOKING REQUESTS ==========

    // Get pending booking requests
    Route::get('/booking-requests', [VendorBookingController::class, 'getPendingRequests']);

    // Accept booking request
    Route::post('/booking-requests/{requestId}/accept', [VendorBookingController::class, 'acceptBookingRequest']);

    // Reject booking request
    Route::post('/booking-requests/{requestId}/reject', [VendorBookingController::class, 'rejectBookingRequest']);

    // ========== VENDOR HISTORY ==========

    // Get all booking history
    Route::get('/booking-history', [VendorBookingController::class, 'getVendorBookingHistory']);

    // Get active bookings
    Route::get('/booking-history/active', [VendorBookingController::class, 'getVendorActiveBookings']);

    // Get completed bookings
    Route::get('/booking-history/completed', [VendorBookingController::class, 'getVendorCompletedBookings']);

    Route::get('/booking-track/{bookingId}', [VendorBookingController::class, 'trackBooking']);
Route::get('/truck-booking/track/{bookingId}', [TruckBookingController::class, 'trackBooking']);
});

Route::get('/booking-location/{bookingId}/{type}', [VendorBookingController::class, 'getBookingLocation']);


// ✅ GOOGLE MAPS ROUTES
Route::prefix('maps')->group(function () {
    Route::post('/distance', [GoogleMapsController::class, 'calculateDistance']);
    Route::post('/reverse-geocode', [GoogleMapsController::class, 'reverseGeocode']);
    Route::post('/geocode', [GoogleMapsController::class, 'geocode']);
    Route::post('/batch-distance', [GoogleMapsController::class, 'batchDistance']);
    Route::post('/search-places', [GoogleMapsController::class, 'searchPlaces']);
    Route::post('/route-details', [GoogleMapsController::class, 'getRouteDetails']);
    Route::get('/status', [GoogleMapsController::class, 'getApiStatus']);
});

// ✅ User Type Routes (Public - No Authentication Required)
Route::prefix('user-types')->group(function () {
    Route::get('/', [UserTypeController::class, 'index']); // Get all user types
    Route::get('/{id}', [UserTypeController::class, 'show']); // Get by ID
    Route::get('/by-key/{type_key}', [UserTypeController::class, 'getByKey']); // Get by type_key
});



// ✅ Vehicle Routes - GET Methods
Route::prefix('vehicle')->group(function () {
    // GET: Get all categories
    Route::get('/categories', [VehicleController::class, 'getCategories']);

    // GET: Get vehicles by category ID
    Route::get('/category/{category_id}', [VehicleController::class, 'getVehiclesByCategory']);

    // GET: Get vehicles by category key
    Route::get('/category-key/{category_key}', [VehicleController::class, 'getVehiclesByCategoryKey']);

    // GET: Get single vehicle details
    Route::get('/{vehicle_id}', [VehicleController::class, 'getVehicleDetails']);

    // ✅ POST: Get all categories
    Route::post('/get-categories', [VehicleController::class, 'getCategoriesPost']);

    // ✅ POST: Get vehicles by category (supports category_id or category_key in body)
    Route::post('/get-by-category', [VehicleController::class, 'getVehiclesByCategoryPost']);

    // ✅ POST: Get vehicle details
    Route::post('/get-details', [VehicleController::class, 'getVehicleDetailsPost']);

    // ✅ POST: Search vehicles with filters
    Route::post('/search', [VehicleController::class, 'searchVehicles']);
});


Route::prefix('vendor')->group(function () {

    // ✅ Booking Requests (Ola/Uber style)
    Route::get('/booking-requests', [VendorBookingController::class, 'getPendingRequests']);
    Route::post('/booking-requests/{requestId}/accept', [VendorBookingController::class, 'acceptBookingRequest']);
    Route::post('/booking-requests/{requestId}/reject', [VendorBookingController::class, 'rejectBookingRequest']);

    // ✅ Vendor History
    Route::get('/booking-history', [VendorBookingController::class, 'getVendorBookingHistory']);
    Route::get('/booking-history/active', [VendorBookingController::class, 'getVendorActiveBookings']);
    Route::get('/booking-history/completed', [VendorBookingController::class, 'getVendorCompletedBookings']);
});
// ✅ HEALTH CHECK
Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'timestamp' => now()->toDateTimeString(),
        'version' => '1.0.0',
        'server_time' => now()->format('Y-m-d H:i:s')
    ]);
});

// ✅ ROOT
Route::get('/', function () {
    return response()->json([
        'success' => true,
        'message' => 'Truck Booking API v1.0',
        'endpoints' => [
            'user_auth' => '/api/auth/*',
            'vendor_auth' => '/api/vendor/auth/*',
            'plans' => '/api/plans/*',
            'vendor_plans' => '/api/vendor-plans/*',
            'truck_booking' => '/api/truck-booking/*',
            'vendor_vehicle' => '/api/vendor-vehicle/*',
            'vendor_availability' => '/api/vendor/availability/*',
            'maps' => '/api/maps/*',
            'health' => '/api/health'
        ],
        'timestamp' => now()->toDateTimeString()
    ]);
});

Route::post('/add-review', [ReviewController::class, 'addReview']);

// ✅ FALLBACK (404)
Route::fallback(function () {
    return response()->json([
        'success' => false,
        'message' => 'API endpoint not found',
        'error' => 'The requested route does not exist',
        'timestamp' => now()->toDateTimeString()
    ], 404);
});

