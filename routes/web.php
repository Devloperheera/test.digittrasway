<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Website\PlanController;
use App\Http\Controllers\Website\UserController;
use App\Http\Controllers\Website\VendorController;
use App\Http\Controllers\Website\WebHomeController;
use App\Http\Controllers\TestSubscriptionController;
use App\Http\Controllers\Website\EmployeeController;
use App\Http\Controllers\Website\MaterialController;
use App\Http\Controllers\Website\UserTypeController;
use App\Http\Controllers\Website\AdminAuthController;
use App\Http\Controllers\Website\TruckTypeController;
use App\Http\Controllers\Website\VendorPlanController;
use App\Http\Controllers\Website\AdminProfileController;
use App\Http\Controllers\Website\SubscriptionController;
use App\Http\Controllers\Website\VehicleModelController;
use App\Http\Controllers\Website\VendorPaymentController;
use App\Http\Controllers\Website\VendorVehicleController;
use App\Http\Controllers\Website\BookingRequestController;
use App\Http\Controllers\Website\DistancePricingController;
use App\Http\Controllers\Website\VehicleCategoryController;
use App\Http\Controllers\Website\TruckSpecificationController;
use App\Http\Controllers\Website\DocumentVerificationController;
use App\Http\Controllers\Website\VendorPlanSubscriptionController;

// Authentication routes
Route::get('/admin/login', [AdminAuthController::class, 'showLogin'])->name('admin.login');
Route::post('/admin/login', [AdminAuthController::class, 'login'])->name('admin.login.post');

// Password reset routes
Route::get('/admin/forgot-password', [AdminAuthController::class, 'showForgotPassword'])->name('admin.forgot.password');
Route::post('/admin/forgot-password', [AdminAuthController::class, 'forgotPassword'])->name('admin.forgot.password.post');
Route::get('/admin/verify-otp/{email}', [AdminAuthController::class, 'showVerifyOTP'])->name('admin.verify.otp');
Route::post('/admin/verify-otp', [AdminAuthController::class, 'verifyOTP'])->name('admin.verify.otp.post');
Route::post('/admin/resend-otp', [AdminAuthController::class, 'resendOTP'])->name('admin.resend.otp');
Route::get('/admin/reset-password/{token}/{email}', [AdminAuthController::class, 'showResetPassword'])->name('admin.reset.password');
Route::post('/admin/reset-password', [AdminAuthController::class, 'resetPassword'])->name('admin.reset.password.post');


Route::get('/payment-success', function () {
    $subscriptionId = request('subscription_id');
    return view('payment-success', compact('subscriptionId'));
});

Route::get('/payment-failed', function () {
    $error = request('error', 'unknown');
    return view('payment-failed', compact('error'));
});
// Protected routes
Route::middleware(['admin.auth'])->group(function () {
    Route::get('/', [WebHomeController::class, 'home'])->name('Website.root');
    Route::get('/home', [WebHomeController::class, 'home'])->name('Website.home');
    Route::get('/forms', [WebHomeController::class, 'forms'])->name('Website.forms');
    Route::get('/table', [WebHomeController::class, 'table'])->name('Website.table');

    Route::get('/admin/profile', [AdminProfileController::class, 'profile'])->name('admin.profile');
    Route::post('/admin/profile/update', [AdminProfileController::class, 'updateProfile'])->name('admin.profile.update');
    Route::post('/admin/password/update', [AdminProfileController::class, 'updatePassword'])->name('admin.password.update');
    Route::get('/admin/settings', [AdminProfileController::class, 'settings'])->name('admin.settings');

    Route::post('/admin/logout', [AdminAuthController::class, 'logout'])->name('admin.logout');
});

// User Management Routes
Route::middleware(['admin.auth'])->prefix('users')->name('users.')->group(function () {
    Route::get('/', [UserController::class, 'index'])->name('index');
    Route::get('/{id}', [UserController::class, 'show'])->name('show');
    Route::post('/{id}/toggle-status', [UserController::class, 'toggleStatus'])->name('toggle.status');

    // Export Filtered Users
    Route::get('/export/excel', [UserController::class, 'exportExcel'])->name('export.excel');
    Route::get('/export/csv', [UserController::class, 'exportCsv'])->name('export.csv');
    Route::get('/export/pdf', [UserController::class, 'exportPdf'])->name('export.pdf');

    // Export Single User
    Route::get('/{id}/export/excel', [UserController::class, 'exportUserExcel'])->name('export.user.excel');
    Route::get('/{id}/export/csv', [UserController::class, 'exportUserCsv'])->name('export.user.csv');
    Route::get('/{id}/export/pdf', [UserController::class, 'exportUserPdf'])->name('export.user.pdf');
    Route::get('/{id}/print', [UserController::class, 'printUser'])->name('print');
});
// User Types Management Routes
Route::middleware(['admin.auth'])->prefix('user-types')->name('user-types.')->group(function () {
    Route::get('/', [UserTypeController::class, 'index'])->name('index');
    Route::get('/create', [UserTypeController::class, 'create'])->name('create');
    Route::post('/', [UserTypeController::class, 'store'])->name('store');
    Route::get('/{id}/edit', [UserTypeController::class, 'edit'])->name('edit');
    Route::put('/{id}', [UserTypeController::class, 'update'])->name('update');
    Route::delete('/{id}', [UserTypeController::class, 'destroy'])->name('destroy');
    Route::post('/{id}/toggle-status', [UserTypeController::class, 'toggleStatus'])->name('toggle.status');
});

// Truck Types Management Routes
Route::middleware(['admin.auth'])->prefix('truck-types')->name('truck-types.')->group(function () {
    Route::get('/', [TruckTypeController::class, 'index'])->name('index');
    Route::get('/create', [TruckTypeController::class, 'create'])->name('create');
    Route::post('/', [TruckTypeController::class, 'store'])->name('store');
    Route::get('/{id}/edit', [TruckTypeController::class, 'edit'])->name('edit');
    Route::put('/{id}', [TruckTypeController::class, 'update'])->name('update');
    Route::delete('/{id}', [TruckTypeController::class, 'destroy'])->name('destroy');
    Route::post('/{id}/toggle-status', [TruckTypeController::class, 'toggleStatus'])->name('toggle.status');
});


// Plans Management Routes (User Plans)
Route::middleware(['admin.auth'])->prefix('plans')->name('plans.')->group(function () {
    Route::get('/', [PlanController::class, 'index'])->name('index');
    Route::get('/create', [PlanController::class, 'create'])->name('create');
    Route::post('/', [PlanController::class, 'store'])->name('store');
    Route::get('/{id}/edit', [PlanController::class, 'edit'])->name('edit');
    Route::put('/{id}', [PlanController::class, 'update'])->name('update');
    Route::delete('/{id}', [PlanController::class, 'destroy'])->name('destroy');
    Route::post('/{id}/toggle-status', [PlanController::class, 'toggleStatus'])->name('toggle-status');
    Route::post('/{id}/toggle-popular', [PlanController::class, 'togglePopular'])->name('toggle-popular');
});

// Subscription Management Routes
Route::middleware(['admin.auth'])->prefix('subscriptions')->name('subscriptions.')->group(function () {
    Route::get('/', [SubscriptionController::class, 'index'])->name('index');
    Route::get('/{id}', [SubscriptionController::class, 'show'])->name('show');
    Route::get('/user/{userId}/create', [SubscriptionController::class, 'create'])->name('create');
    Route::post('/', [SubscriptionController::class, 'store'])->name('store');
    Route::post('/{id}/cancel', [SubscriptionController::class, 'cancel'])->name('cancel');
});

// Distance Pricing Management Routes
Route::middleware(['admin.auth'])->prefix('distance-pricings')->name('distance-pricings.')->group(function () {
    Route::get('/', [DistancePricingController::class, 'index'])->name('index');
    Route::get('/create', [DistancePricingController::class, 'create'])->name('create');
    Route::post('/', [DistancePricingController::class, 'store'])->name('store');
    Route::get('/{id}/edit', [DistancePricingController::class, 'edit'])->name('edit');
    Route::put('/{id}', [DistancePricingController::class, 'update'])->name('update');
    Route::delete('/{id}', [DistancePricingController::class, 'destroy'])->name('destroy');
    Route::post('/{id}/toggle-status', [DistancePricingController::class, 'toggleStatus'])->name('toggle.status');
    Route::post('/calculate-price', [DistancePricingController::class, 'calculatePrice'])->name('calculate.price');
});

Route::get('/test-subscription', [TestSubscriptionController::class, 'showTestPage'])
    ->name('test.subscription');

Route::middleware(['admin.auth'])->prefix('materials')->name('materials.')->group(function () {
    Route::get('/', [MaterialController::class, 'index'])->name('index');
    Route::get('/create', [MaterialController::class, 'create'])->name('create');
    Route::post('/', [MaterialController::class, 'store'])->name('store');
    Route::get('/{id}/edit', [MaterialController::class, 'edit'])->name('edit');
    Route::put('/{id}', [MaterialController::class, 'update'])->name('update');
    Route::delete('/{id}', [MaterialController::class, 'destroy'])->name('destroy');
    Route::post('/{id}/toggle-status', [MaterialController::class, 'toggleStatus'])->name('toggle-status');
});

// Booking Requests Routes
Route::middleware(['admin.auth'])->prefix('booking-requests')->name('booking-requests.')->group(function () {
    Route::get('/', [BookingRequestController::class, 'index'])->name('index');
    Route::get('/{id}', [BookingRequestController::class, 'show'])->name('show');

    // API Routes
    Route::get('/api/all', [BookingRequestController::class, 'getAllRequests'])->name('api.all');
    Route::get('/api/booking/{bookingId}', [BookingRequestController::class, 'getByBooking'])->name('api.booking');
    Route::get('/api/vendor/{vendorId}', [BookingRequestController::class, 'getByVendor'])->name('api.vendor');
    Route::get('/api/pending', [BookingRequestController::class, 'getPendingRequests'])->name('api.pending');
});

// Truck Specifications Routes
Route::middleware(['admin.auth'])->prefix('truck-specifications')->name('truck-specifications.')->group(function () {
    Route::get('/', [TruckSpecificationController::class, 'index'])->name('index');
    Route::get('/create', [TruckSpecificationController::class, 'create'])->name('create');
    Route::post('/', [TruckSpecificationController::class, 'store'])->name('store');
    Route::get('/{id}/edit', [TruckSpecificationController::class, 'edit'])->name('edit');
    Route::put('/{id}', [TruckSpecificationController::class, 'update'])->name('update');
    Route::delete('/{id}', [TruckSpecificationController::class, 'destroy'])->name('destroy');
    Route::post('/{id}/toggle-status', [TruckSpecificationController::class, 'toggleStatus'])->name('toggle.status');
});
// Vehicle Categories Routes
Route::middleware(['admin.auth'])->prefix('vehicle-categories')->name('vehicle-categories.')->group(function () {
    Route::get('/', [VehicleCategoryController::class, 'index'])->name('index');
    Route::get('/create', [VehicleCategoryController::class, 'create'])->name('create');
    Route::post('/', [VehicleCategoryController::class, 'store'])->name('store');
    Route::get('/{id}/edit', [VehicleCategoryController::class, 'edit'])->name('edit');
    Route::put('/{id}', [VehicleCategoryController::class, 'update'])->name('update');
    Route::delete('/{id}', [VehicleCategoryController::class, 'destroy'])->name('destroy');
    Route::post('/{id}/toggle-status', [VehicleCategoryController::class, 'toggleStatus'])->name('toggle.status');
});
// Vehicle Models Routes
Route::middleware(['admin.auth'])->prefix('vehicle-models')->name('vehicle-models.')->group(function () {
    Route::get('/', [VehicleModelController::class, 'index'])->name('index');
    Route::get('/create', [VehicleModelController::class, 'create'])->name('create');
    Route::post('/', [VehicleModelController::class, 'store'])->name('store');
    Route::get('/{id}/edit', [VehicleModelController::class, 'edit'])->name('edit');
    Route::put('/{id}', [VehicleModelController::class, 'update'])->name('update');
    Route::delete('/{id}', [VehicleModelController::class, 'destroy'])->name('destroy');
    Route::post('/{id}/toggle-status', [VehicleModelController::class, 'toggleStatus'])->name('toggle.status');
});
// Vendor Plans Routes
Route::middleware(['admin.auth'])->prefix('vendor-plans')->name('vendor-plans.')->group(function () {
    Route::get('/', [VendorPlanController::class, 'index'])->name('index');
    Route::get('/create', [VendorPlanController::class, 'create'])->name('create');
    Route::post('/', [VendorPlanController::class, 'store'])->name('store');
    Route::get('/{id}/edit', [VendorPlanController::class, 'edit'])->name('edit');
    Route::put('/{id}', [VendorPlanController::class, 'update'])->name('update');
    Route::delete('/{id}', [VendorPlanController::class, 'destroy'])->name('destroy');
    Route::post('/{id}/toggle-status', [VendorPlanController::class, 'toggleStatus'])->name('toggle.status');
    Route::post('/{id}/toggle-popular', [VendorPlanController::class, 'togglePopular'])->name('toggle.popular');
});

// Vendor Payments Routes
Route::middleware(['admin.auth'])->prefix('vendor-payments')->name('vendor-payments.')->group(function () {
    Route::get('/', [VendorPaymentController::class, 'index'])->name('index');
    Route::get('/{id}', [VendorPaymentController::class, 'show'])->name('show');
    Route::get('/export', [VendorPaymentController::class, 'export'])->name('export');
});

// Vendor Plan Subscriptions Routes
Route::middleware(['admin.auth'])->prefix('vendor-subscriptions')->name('vendor-subscriptions.')->group(function () {
    Route::get('/', [VendorPlanSubscriptionController::class, 'index'])->name('index');
    Route::get('/{id}', [VendorPlanSubscriptionController::class, 'show'])->name('show');
    Route::post('/{id}/cancel', [VendorPlanSubscriptionController::class, 'cancel'])->name('cancel');
    Route::post('/{id}/renew', [VendorPlanSubscriptionController::class, 'renew'])->name('renew');
});

// Vendor Vehicles Routes
Route::middleware(['admin.auth'])->prefix('vendor-vehicles')->name('vendor-vehicles.')->group(function () {
    Route::get('/', [VendorVehicleController::class, 'index'])->name('index');
    Route::get('/{id}', [VendorVehicleController::class, 'show'])->name('show');
    Route::get('/{id}/approve', [VendorVehicleController::class, 'approve'])->name('approve');
    Route::post('/{id}/reject', [VendorVehicleController::class, 'reject'])->name('reject');
    Route::post('/{id}/toggle-listing', [VendorVehicleController::class, 'toggleListing'])->name('toggle.listing');
    Route::delete('/{id}', [VendorVehicleController::class, 'destroy'])->name('destroy');
});

// Vendor Routes
Route::middleware(['admin.auth'])->prefix('vendors')->name('vendors.')->group(function () {
    // Main routes
    Route::get('/', [VendorController::class, 'index'])->name('index');
    Route::get('/{id}', [VendorController::class, 'show'])->name('show');
    Route::post('/{id}/toggle-status', [VendorController::class, 'toggleStatus'])->name('toggle.status');

    // RC Details routes
    Route::get('/{id}/rc-details', [VendorController::class, 'viewRcDetails'])->name('rc-details');
    Route::get('/{id}/rc-export/excel', [VendorController::class, 'exportRcExcel'])->name('rc-export.excel');
    Route::get('/{id}/rc-export/csv', [VendorController::class, 'exportRcCsv'])->name('rc-export.csv');
    Route::get('/{id}/rc-export/pdf', [VendorController::class, 'exportRcPdf'])->name('rc-export.pdf');

    // DL Details routes
    Route::get('/{id}/dl-details', [VendorController::class, 'viewDlDetails'])->name('dl-details');
    Route::get('/{id}/dl-export/excel', [VendorController::class, 'exportDlExcel'])->name('dl-export.excel');
    Route::get('/{id}/dl-export/csv', [VendorController::class, 'exportDlCsv'])->name('dl-export.csv');
    Route::get('/{id}/dl-export/pdf', [VendorController::class, 'exportDlPdf'])->name('dl-export.pdf');

    // Export All Vendors
    Route::get('/export/excel', [VendorController::class, 'exportExcel'])->name('export.excel');
    Route::get('/export/csv', [VendorController::class, 'exportCsv'])->name('export.csv');
    Route::get('/export/pdf', [VendorController::class, 'exportPdf'])->name('export.pdf');
});


Route::prefix('document-verification')->name('document-verification.')->group(function () {

    // ✅ Search Form (Public - No rate limit needed)
    Route::get('/', [DocumentVerificationController::class, 'index'])
        ->name('index');

    // ✅ Search/Verify Document (Rate Limited - 10 attempts per minute)
    Route::post('/search', [DocumentVerificationController::class, 'search'])
        ->name('search')
        ->middleware('throttle:10,1'); // Max 10 requests per minute

    // ✅ Export RC PDF (Rate Limited - 5 downloads per minute)
    Route::post('/export-rc-pdf', [DocumentVerificationController::class, 'exportRcPdf'])
        ->name('export-rc-pdf')
        ->middleware('throttle:5,1');

    // ✅ Export DL PDF (Rate Limited - 5 downloads per minute)
    Route::post('/export-dl-pdf', [DocumentVerificationController::class, 'exportDlPdf'])
        ->name('export-dl-pdf')
        ->middleware('throttle:5,1');
});

// Add this temporarily for testing
Route::get('/test-surepass-api', function () {
    $token = env('SUREPASS_TOKEN');

    if (empty($token)) {
        return 'ERROR: SUREPASS_TOKEN not found in .env';
    }

    // Test RC API
    $response = \Illuminate\Support\Facades\Http::timeout(30)
        ->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Content-Type' => 'application/json',
        ])
        ->post('https://kyc-api.surepass.app/api/v1/rc/rc-full', [
            'id_number' => 'HR9074419'
        ]);

    return [
        'status' => $response->status(),
        'body' => $response->json(),
        'token_used' => substr($token, 0, 10) . '...' // Show first 10 chars only
    ];
});


Route::middleware(['admin.auth'])->prefix('employees')->name('employees.')->group(function () {

    // Main CRUD Routes
    Route::get('/', [EmployeeController::class, 'index'])->name('index');
    Route::get('/create', [EmployeeController::class, 'create'])->name('create');
    Route::post('/', [EmployeeController::class, 'store'])->name('store');
    Route::get('/{id}', [EmployeeController::class, 'show'])->name('show');
    Route::get('/{id}/edit', [EmployeeController::class, 'edit'])->name('edit');
    Route::put('/{id}', [EmployeeController::class, 'update'])->name('update');
    Route::delete('/{id}', [EmployeeController::class, 'destroy'])->name('destroy');

    // ✅ FIX: Changed from 'toggle.status' to 'toggle-status'
    Route::post('/{id}/toggle-status', [EmployeeController::class, 'toggleStatus'])->name('toggle-status');

    // Document Routes
    Route::get('/{id}/document/{type}', [EmployeeController::class, 'viewDocument'])->name('view-document');
    Route::get('/{id}/download/{type}', [EmployeeController::class, 'downloadDocument'])->name('download-document');

    // Referral Routes
    Route::get('/{id}/referrals', [EmployeeController::class, 'referrals'])->name('referrals');
    Route::get('/{id}/referrals/export/excel', [EmployeeController::class, 'exportReferralsExcel'])->name('referrals.export.excel');
    Route::get('/{id}/referrals/export/csv', [EmployeeController::class, 'exportReferralsCsv'])->name('referrals.export.csv');
    Route::get('/{id}/referrals/export/pdf', [EmployeeController::class, 'exportReferralsPdf'])->name('referrals.export.pdf');
});



// ✅ PAYMENT SUCCESS - AUTO REDIRECT TO APP
Route::get('/payment-success', function () {
    $subscriptionId = request('subscription_id', 'N/A');

    return response('
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Payment Successful</title>
        <style>
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body {
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 20px;
            }
            .container {
                background: white;
                border-radius: 20px;
                box-shadow: 0 20px 60px rgba(0,0,0,0.3);
                padding: 50px;
                text-align: center;
                max-width: 500px;
                width: 100%;
                animation: slideUp 0.5s ease-out;
            }
            @keyframes slideUp {
                from { transform: translateY(50px); opacity: 0; }
                to { transform: translateY(0); opacity: 1; }
            }
            .icon {
                width: 80px;
                height: 80px;
                background: #10b981;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                margin: 0 auto 30px;
                font-size: 50px;
                color: white;
                animation: scaleIn 0.5s ease-out 0.2s both;
            }
            @keyframes scaleIn {
                from { transform: scale(0); }
                to { transform: scale(1); }
            }
            h1 {
                color: #10b981;
                font-size: 32px;
                margin-bottom: 15px;
                font-weight: 700;
            }
            p {
                color: #6b7280;
                font-size: 18px;
                margin: 15px 0;
                line-height: 1.6;
            }
            .subscription-id {
                background: #f3f4f6;
                padding: 15px;
                border-radius: 10px;
                margin: 25px 0;
                font-family: monospace;
                color: #374151;
                font-size: 16px;
            }
            .spinner {
                border: 4px solid #f3f3f3;
                border-top: 4px solid #10b981;
                border-radius: 50%;
                width: 50px;
                height: 50px;
                animation: spin 1s linear infinite;
                margin: 25px auto;
            }
            @keyframes spin {
                0% { transform: rotate(0deg); }
                100% { transform: rotate(360deg); }
            }
            .message {
                color: #6b7280;
                font-size: 16px;
                margin-top: 20px;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="icon">✓</div>
            <h1>Payment Successful!</h1>
            <p>Your subscription has been activated successfully.</p>
            <div class="subscription-id">
                <strong>Subscription ID:</strong><br>' . htmlspecialchars($subscriptionId) . '
            </div>
            <div id="spinner" class="spinner"></div>
            <p id="message" class="message">Redirecting you back to the app...</p>
        </div>

        <script>
            function redirectToApp() {
                const subscriptionId = "' . htmlspecialchars($subscriptionId) . '";

                // ✅ DEEP LINK URL - CHANGE THIS TO YOUR APP SCHEME
                const appUrl = `digittransway://payment-success?subscription_id=${subscriptionId}&status=success`;

                console.log("Attempting to redirect to:", appUrl);

                // Try to open the app
                window.location.href = appUrl;

                // Show fallback message after 2.5 seconds if app doesn\'t open
                setTimeout(function() {
                    document.getElementById("spinner").style.display = "none";
                    document.getElementById("message").innerHTML =
                        "<strong>App not opening?</strong><br><br>" +
                        "Please return to the Digit Transway app manually to continue.<br><br>" +
                        "<small>You can now safely close this page.</small>";
                }, 2500);
            }

            // Auto redirect when page loads
            window.onload = redirectToApp;
        </script>
    </body>
    </html>
    ')->header('Content-Type', 'text/html');
});

// ✅ PAYMENT FAILED - AUTO REDIRECT TO APP
Route::get('/payment-failed', function () {
    $error = request('error', 'unknown');

    return response('
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Payment Failed</title>
        <style>
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body {
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
                background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 20px;
            }
            .container {
                background: white;
                border-radius: 20px;
                box-shadow: 0 20px 60px rgba(0,0,0,0.3);
                padding: 50px;
                text-align: center;
                max-width: 500px;
                width: 100%;
                animation: slideUp 0.5s ease-out;
            }
            @keyframes slideUp {
                from { transform: translateY(50px); opacity: 0; }
                to { transform: translateY(0); opacity: 1; }
            }
            .icon {
                width: 80px;
                height: 80px;
                background: #ef4444;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                margin: 0 auto 30px;
                font-size: 50px;
                color: white;
                animation: shake 0.5s ease-out;
            }
            @keyframes shake {
                0%, 100% { transform: translateX(0); }
                25% { transform: translateX(-10px); }
                75% { transform: translateX(10px); }
            }
            h1 {
                color: #ef4444;
                font-size: 32px;
                margin-bottom: 15px;
                font-weight: 700;
            }
            p {
                color: #6b7280;
                font-size: 18px;
                margin: 15px 0;
                line-height: 1.6;
            }
            .error-code {
                background: #fee2e2;
                padding: 12px;
                border-radius: 8px;
                color: #991b1b;
                font-size: 14px;
                margin: 20px 0;
            }
            .spinner {
                border: 4px solid #f3f3f3;
                border-top: 4px solid #ef4444;
                border-radius: 50%;
                width: 50px;
                height: 50px;
                animation: spin 1s linear infinite;
                margin: 25px auto;
            }
            @keyframes spin {
                0% { transform: rotate(0deg); }
                100% { transform: rotate(360deg); }
            }
            .message {
                color: #6b7280;
                font-size: 16px;
                margin-top: 20px;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="icon">✕</div>
            <h1>Payment Failed</h1>
            <p>We couldn\'t process your payment.</p>
            ' . ($error !== 'unknown' ? '<div class="error-code">Error: ' . htmlspecialchars(ucwords(str_replace('_', ' ', $error))) . '</div>' : '') . '
            <p>No amount has been deducted from your account.</p>
            <div id="spinner" class="spinner"></div>
            <p id="message" class="message">Redirecting you back to the app...</p>
        </div>

        <script>
            function redirectToApp() {
                const error = "' . htmlspecialchars($error) . '";

                // ✅ DEEP LINK URL - CHANGE THIS TO YOUR APP SCHEME
                const appUrl = `digittransway://payment-failed?error=${error}&status=failed`;

                console.log("Attempting to redirect to:", appUrl);

                // Try to open the app
                window.location.href = appUrl;

                // Show fallback message after 2.5 seconds if app doesn\'t open
                setTimeout(function() {
                    document.getElementById("spinner").style.display = "none";
                    document.getElementById("message").innerHTML =
                        "<strong>App not opening?</strong><br><br>" +
                        "Please return to the Digit Transway app manually to try again.<br><br>" +
                        "<small>You can now safely close this page.</small>";
                }, 2500);
            }

            // Auto redirect when page loads
            window.onload = redirectToApp;
        </script>
    </body>
    </html>
    ')->header('Content-Type', 'text/html');
});







