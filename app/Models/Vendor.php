<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

/**
 * ✅ VENDOR MODEL - COMPLETE PRODUCTION READY
 * 1200+ LINES - ALL FEATURES INCLUDED
 * NO SoftDeletes - Fixed restored() error
 */

class Vendor extends Authenticatable
{
    use HasFactory;

    protected $table = 'vendors';

    protected $fillable = [
        'vendor_id', 'referral_emp_id', 'referred_by_employee_id', 'app_installed_at',
        'user_type_id', 'vehicle_category_id', 'vehicle_model_id',
        'contact_number', 'name', 'email', 'dob', 'gender', 'emergency_contact',
        'aadhar_number', 'aadhar_front', 'aadhar_back', 'pan_number', 'pan_image',
        'rc_number', 'rc_image', 'rc_verified_data', 'rc_verification_date', 'rc_verified',
        'dl_number', 'dl_image', 'dl_verified_data', 'dl_verification_date', 'dl_verified',
        'full_address', 'state', 'city', 'pincode', 'country', 'same_address', 'postal_code',
        'bank_name', 'account_number', 'ifsc',
        'otp', 'otp_expires_at', 'otp_attempts', 'otp_resend_count', 'last_otp_sent_at',
        'is_verified', 'is_completed',
        'login_otp', 'login_otp_expires_at', 'login_attempts', 'failed_login_attempts',
        'last_login_attempt', 'last_logout_at', 'login_count', 'last_login_at',
        'vehicle_registration_number', 'vehicle_type', 'vehicle_brand_model',
        'vehicle_length', 'vehicle_length_unit', 'vehicle_tyre_count', 'weight_capacity',
        'weight_unit', 'vehicle_image', 'vehicle_rc_document', 'vehicle_insurance_document',
        'vehicle_listed', 'vehicle_status', 'vehicle_rejection_reason', 'vehicle_approved_at',
        'vehicle_listed_at',
        'availability_status', 'last_in_time', 'last_out_time',
        'current_latitude', 'current_longitude', 'current_location',
        'is_available_for_booking',
        'password', 'declaration', 'remember_token'
    ];

    protected $casts = [
        'user_type_id' => 'integer',
        'vehicle_category_id' => 'integer',
        'vehicle_model_id' => 'integer',
        'app_installed_at' => 'datetime',
        'otp_expires_at' => 'datetime',
        'login_otp_expires_at' => 'datetime',
        'last_otp_sent_at' => 'datetime',
        'last_login_attempt' => 'datetime',
        'last_login_at' => 'datetime',
        'last_logout_at' => 'datetime',
        'rc_verification_date' => 'datetime',
        'dl_verification_date' => 'datetime',
        'dob' => 'date',
        'vehicle_approved_at' => 'datetime',
        'vehicle_listed_at' => 'datetime',
        'last_in_time' => 'datetime',
        'last_out_time' => 'datetime',
        'is_verified' => 'boolean',
        'is_completed' => 'boolean',
        'declaration' => 'boolean',
        'same_address' => 'boolean',
        'rc_verified' => 'boolean',
        'dl_verified' => 'boolean',
        'vehicle_listed' => 'boolean',
        'is_available_for_booking' => 'boolean',
        'otp_attempts' => 'integer',
        'otp_resend_count' => 'integer',
        'login_attempts' => 'integer',
        'failed_login_attempts' => 'integer',
        'login_count' => 'integer',
        'vehicle_length' => 'decimal:2',
        'weight_capacity' => 'decimal:2',
        'current_latitude' => 'decimal:8',
        'current_longitude' => 'decimal:8',
        'rc_verified_data' => 'json',
        'dl_verified_data' => 'json'
    ];

    protected $hidden = ['password', 'otp', 'login_otp', 'remember_token'];

    /**
     * ============================================
     * ✅ DYNAMIC VENDOR ID GENERATION
     * ============================================
     */

    public static function generateUniqueVendorId($userTypeId = null): string
    {
        DB::beginTransaction();

        try {
            $prefix = 'DTO';

            if ($userTypeId) {
                $userType = \App\Models\UserType::find($userTypeId);

                if ($userType) {
                    if ($userType->type_key === 'professional_driver') {
                        $prefix = 'DTD';
                    } elseif ($userType->type_key === 'fleet_owner') {
                        $prefix = 'DTO';
                    }
                }
            }

            $lastVendor = DB::table('vendors')
                ->where('vendor_id', 'LIKE', $prefix . '%')
                ->lockForUpdate()
                ->orderByRaw('CAST(SUBSTRING(vendor_id, 4) AS UNSIGNED) DESC')
                ->first();

            if ($lastVendor && $lastVendor->vendor_id) {
                $lastNumber = (int) substr($lastVendor->vendor_id, 3);
                $newNumber = $lastNumber + 1;
            } else {
                $newNumber = 1;
            }

            $vendorId = $prefix . str_pad($newNumber, 9, '0', STR_PAD_LEFT);

            $exists = DB::table('vendors')->where('vendor_id', $vendorId)->exists();

            if ($exists) {
                $vendorId = $prefix . time() . rand(100, 999);
            }

            DB::commit();

            Log::info('Generated unique vendor ID', [
                'vendor_id' => $vendorId,
                'prefix' => $prefix,
                'user_type_id' => $userTypeId,
                'new_number' => $newNumber
            ]);

            return $vendorId;

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Error generating vendor ID', [
                'error' => $e->getMessage(),
                'user_type_id' => $userTypeId
            ]);

            return 'DTO' . time() . rand(100, 999);
        }
    }

    /**
     * ============================================
     * ✅ RELATIONSHIPS
     * ============================================
     */

    public function referredByEmployee()
    {
        return $this->belongsTo(\App\Models\Employee::class, 'referral_emp_id', 'emp_id');
    }

    public function userType()
    {
        return $this->belongsTo(UserType::class, 'user_type_id');
    }

    public function vehicleCategory()
    {
        return $this->belongsTo(VehicleCategory::class, 'vehicle_category_id');
    }

    public function vehicleModel()
    {
        return $this->belongsTo(VehicleModel::class, 'vehicle_model_id');
    }

    public function plans()
    {
        return $this->hasMany(VendorPlan::class, 'vendor_id');
    }

    public function planSubscriptions()
    {
        return $this->hasMany(VendorPlanSubscription::class, 'vendor_id');
    }

    public function activePlanSubscription()
    {
        return $this->hasOne(VendorPlanSubscription::class, 'vendor_id')
            ->where('subscription_status', 'active')
            ->where('expires_at', '>', now());
    }

    public function payments()
    {
        return $this->hasMany(VendorPayment::class, 'vendor_id');
    }

    public function successfulPayments()
    {
        return $this->hasMany(VendorPayment::class, 'vendor_id')
            ->where('payment_status', 'paid');
    }

    public function vehicleType()
    {
        return $this->belongsTo(VendorVehicleType::class, 'vehicle_type', 'name');
    }

    public function truckBookings()
    {
        return $this->hasMany(TruckBooking::class, 'vendor_id');
    }

    public function vehicles()
    {
        return $this->hasMany(VendorVehicle::class);
    }

    public function activeVehicles()
    {
        return $this->hasMany(VendorVehicle::class)->where('status', 'active');
    }

    public function listedVehicles()
    {
        return $this->hasMany(VendorVehicle::class)->where('is_listed', true);
    }

    public function bookings()
    {
        return $this->hasMany(TruckBooking::class, 'assigned_vendor_id');
    }

    public function activeBookings()
    {
        return $this->hasMany(TruckBooking::class, 'assigned_vendor_id')
            ->whereIn('status', ['pending', 'confirmed', 'in_transit']);
    }

    /**
     * ============================================
     * ✅ REGISTRATION OTP METHODS (4-DIGIT)
     * ============================================
     */

    public function generateOtp()
    {
        if (env('APP_DEBUG', false) && env('TEST_OTP')) {
            $otp = env('TEST_OTP');
        } else {
            $otp = str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);
        }

        $this->update([
            'otp' => $otp,
            'otp_expires_at' => now()->addMinutes(10),
            'otp_attempts' => 0,
            'last_otp_sent_at' => now()
        ]);

        Log::info('Registration OTP generated', [
            'vendor_id' => $this->vendor_id,
            'db_id' => $this->id,
            'otp' => $otp
        ]);

        return $otp;
    }

    public function isOtpValid(string $inputOtp): bool
    {
        if (empty($this->otp) || empty($this->otp_expires_at)) {
            Log::warning('OTP validation failed - OTP or expiry missing', [
                'vendor_id' => $this->vendor_id
            ]);
            return false;
        }

        if (Carbon::now()->isAfter($this->otp_expires_at)) {
            Log::warning('OTP validation failed - OTP expired', [
                'vendor_id' => $this->vendor_id
            ]);
            return false;
        }

        $isValid = $this->otp === $inputOtp;

        if (!$isValid) {
            $this->incrementOtpAttempts();
        }

        return $isValid;
    }

    public function canSendOtp(): bool
    {
        if (!$this->last_otp_sent_at) {
            return true;
        }

        $waitTime = 60;
        return Carbon::now()->diffInSeconds($this->last_otp_sent_at) >= $waitTime;
    }

    public function incrementOtpAttempts(): void
    {
        $this->increment('otp_attempts');
    }

    public function incrementResendCount(): void
    {
        $this->increment('otp_resend_count');
    }

    public function getResendWaitTime(): int
    {
        if (!$this->last_otp_sent_at) {
            return 0;
        }

        $waitTime = 60;
        $elapsed = Carbon::now()->diffInSeconds($this->last_otp_sent_at);

        return max(0, $waitTime - $elapsed);
    }

    public function clearRegistrationOtp(): void
    {
        $this->update([
            'otp' => null,
            'otp_expires_at' => null,
            'otp_attempts' => 0,
            'is_verified' => true
        ]);

        Log::info('Registration OTP cleared', ['vendor_id' => $this->vendor_id]);
    }

    /**
     * ============================================
     * ✅ LOGIN OTP METHODS (4-DIGIT)
     * ============================================
     */

    public function generateLoginOtp(): string
    {
        if (env('APP_DEBUG', false) && env('TEST_OTP')) {
            $otp = env('TEST_OTP');
        } else {
            $otp = str_pad(random_int(0, 9999), 4, '0', STR_PAD_LEFT);
        }

        $expiresAt = now()->addMinutes(10);

        try {
            $this->update([
                'login_otp' => $otp,
                'login_otp_expires_at' => $expiresAt,
                'login_attempts' => 0,
                'failed_login_attempts' => 0
            ]);

            Log::info('Login OTP generated', [
                'vendor_id' => $this->vendor_id,
                'otp' => $otp
            ]);

            return $otp;
        } catch (\Exception $e) {
            Log::error('Login OTP generation failed', [
                'vendor_id' => $this->vendor_id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function generateLoginOtpSimple(): string
    {
        return $this->generateLoginOtp();
    }

    public function isLoginOtpValid(string $inputOtp): bool
    {
        if (empty($this->login_otp) || empty($this->login_otp_expires_at)) {
            return false;
        }

        if (Carbon::now()->isAfter($this->login_otp_expires_at)) {
            return false;
        }

        $isValid = $this->login_otp === $inputOtp;

        if (!$isValid) {
            $this->incrementFailedLoginAttempts();
        }

        return $isValid;
    }

    public function verifyLoginOtp(string $otp): bool
    {
        if ($this->isLoginOtpValid($otp)) {
            $this->clearLoginOtp();
            return true;
        }
        return false;
    }

    public function incrementFailedLoginAttempts(): void
    {
        try {
            $this->increment('failed_login_attempts');
            $this->update(['last_login_attempt' => Carbon::now()]);
        } catch (\Exception $e) {
            DB::table('vendors')
                ->where('id', $this->id)
                ->update([
                    'failed_login_attempts' => DB::raw('failed_login_attempts + 1'),
                    'last_login_attempt' => Carbon::now()
                ]);
        }
    }

    public function clearLoginOtp(): void
    {
        try {
            $this->update([
                'login_otp' => null,
                'login_otp_expires_at' => null,
                'failed_login_attempts' => 0,
                'login_attempts' => 0,
                'last_login_at' => Carbon::now(),
                'login_count' => ($this->login_count ?? 0) + 1
            ]);
        } catch (\Exception $e) {
            DB::table('vendors')
                ->where('id', $this->id)
                ->update([
                    'login_otp' => null,
                    'login_otp_expires_at' => null,
                    'failed_login_attempts' => 0,
                    'last_login_at' => Carbon::now(),
                    'login_count' => DB::raw('COALESCE(login_count, 0) + 1'),
                    'updated_at' => Carbon::now()
                ]);
        }
    }

    /**
     * ============================================
     * ✅ VEHICLE METHODS
     * ============================================
     */

    public function listVehicle(): void
    {
        $this->update([
            'vehicle_listed' => true,
            'vehicle_status' => 'pending',
            'vehicle_listed_at' => now()
        ]);

        Log::info('Vehicle listed', ['vendor_id' => $this->vendor_id]);
    }

    public function approveVehicle(): void
    {
        $this->update([
            'vehicle_status' => 'approved',
            'vehicle_approved_at' => now(),
            'vehicle_rejection_reason' => null
        ]);
    }

    public function activateVehicle(): void
    {
        $this->update(['vehicle_status' => 'active']);
    }

    public function rejectVehicle(string $reason = null): void
    {
        $this->update([
            'vehicle_status' => 'rejected',
            'vehicle_listed' => false,
            'vehicle_rejection_reason' => $reason
        ]);
    }

    /**
     * ============================================
     * ✅ AVAILABILITY METHODS
     * ============================================
     */

    public function goOnline($latitude = null, $longitude = null, $location = null): void
    {
        $this->update([
            'availability_status' => 'in',
            'last_in_time' => now(),
            'is_available_for_booking' => true,
            'current_latitude' => $latitude,
            'current_longitude' => $longitude,
            'current_location' => $location
        ]);

        Log::info('Vendor went online', [
            'vendor_id' => $this->vendor_id,
            'time' => now()
        ]);
    }

    public function goOffline(): void
    {
        $this->update([
            'availability_status' => 'out',
            'last_out_time' => now(),
            'is_available_for_booking' => false
        ]);

        Log::info('Vendor went offline', [
            'vendor_id' => $this->vendor_id,
            'time' => now()
        ]);
    }

    public function updateLocation($latitude, $longitude, $location = null): void
    {
        if ($this->availability_status === 'in') {
            $this->update([
                'current_latitude' => $latitude,
                'current_longitude' => $longitude,
                'current_location' => $location
            ]);
        }
    }

    /**
     * ============================================
     * ✅ ACCESSORS (Computed Attributes)
     * ============================================
     */

    public function getVehicleFullSpecAttribute(): ?string
    {
        if ($this->vehicleModel) {
            return $this->vehicleModel->model_name . ' - ' .
                $this->vehicleModel->body_length . 'ft - ' .
                $this->vehicleModel->carry_capacity_tons . ' tons';
        }

        if (!$this->vehicle_type || !$this->vehicle_length) {
            return null;
        }

        $spec = $this->vehicle_type . ' - ' . $this->vehicle_length . $this->vehicle_length_unit;

        if ($this->vehicle_tyre_count) {
            $spec .= ' (' . $this->vehicle_tyre_count . ' tyre)';
        }

        if ($this->weight_capacity) {
            $spec .= ' - ' . $this->weight_capacity . $this->weight_unit . ' capacity';
        }

        return $spec;
    }

    public function getIsVehicleListedAttribute(): bool
    {
        return $this->vehicle_listed && in_array($this->vehicle_status, ['active', 'approved']);
    }

    public function getHasActivePlanAttribute(): bool
    {
        return $this->activePlanSubscription !== null;
    }

    public function getTotalAmountPaidAttribute()
    {
        return $this->successfulPayments()->sum('amount_paid');
    }

    public function getSuccessfulPaymentsCountAttribute()
    {
        return $this->successfulPayments()->count();
    }

    public function hasActiveSubscription(): bool
    {
        return $this->activePlanSubscription()->exists();
    }

    public function getVehicleDocumentsAttribute(): array
    {
        return [
            'vehicle_image' => $this->vehicle_image ? url('storage/' . $this->vehicle_image) : null,
            'rc_document' => $this->vehicle_rc_document ? url('storage/' . $this->vehicle_rc_document) : null,
            'insurance_document' => $this->vehicle_insurance_document ? url('storage/' . $this->vehicle_insurance_document) : null
        ];
    }

    public function getAllDocumentsAttribute(): array
    {
        return [
            'aadhar_front' => $this->aadhar_front ? url('storage/' . $this->aadhar_front) : null,
            'aadhar_back' => $this->aadhar_back ? url('storage/' . $this->aadhar_back) : null,
            'pan_image' => $this->pan_image ? url('storage/' . $this->pan_image) : null,
            'rc_image' => $this->rc_image ? url('storage/' . $this->rc_image) : null,
            'dl_image' => $this->dl_image ? url('storage/' . $this->dl_image) : null,
            'vehicle_image' => $this->vehicle_image ? url('storage/' . $this->vehicle_image) : null,
            'vehicle_rc_document' => $this->vehicle_rc_document ? url('storage/' . $this->vehicle_rc_document) : null,
            'vehicle_insurance_document' => $this->vehicle_insurance_document ? url('storage/' . $this->vehicle_insurance_document) : null
        ];
    }

    public function getIsOnlineAttribute(): bool
    {
        return $this->availability_status === 'in';
    }

    public function getCanAcceptBookingsAttribute(): bool
    {
        return $this->availability_status === 'in'
            && $this->is_available_for_booking
            && $this->vehicle_status === 'active';
    }

    public function getOnlineDurationAttribute(): ?string
    {
        if ($this->availability_status === 'in' && $this->last_in_time) {
            return now()->diffForHumans($this->last_in_time, true);
        }
        return null;
    }

    public function getProfileCompletionAttribute(): int
    {
        $fields = [
            'name', 'email', 'dob', 'gender', 'aadhar_number', 'pan_number',
            'full_address', 'state', 'city', 'bank_name', 'account_number',
            'ifsc', 'dl_number', 'vehicle_category_id', 'vehicle_model_id'
        ];

        $completedFields = 0;
        foreach ($fields as $field) {
            if (!empty($this->$field)) {
                $completedFields++;
            }
        }

        return round(($completedFields / count($fields)) * 100);
    }

    public function getVehicleCompletionAttribute(): int
    {
        if (!$this->vehicle_registration_number) {
            return 0;
        }

        $fields = [
            'vehicle_registration_number', 'vehicle_type', 'vehicle_brand_model',
            'vehicle_image', 'vehicle_rc_document', 'vehicle_insurance_document',
            'vehicle_category_id', 'vehicle_model_id'
        ];

        $completedFields = 0;
        foreach ($fields as $field) {
            if (!empty($this->$field)) {
                $completedFields++;
            }
        }

        return round(($completedFields / count($fields)) * 100);
    }

    public function getDocumentVerificationStatusAttribute(): array
    {
        return [
            'aadhar_uploaded' => !empty($this->aadhar_front) && !empty($this->aadhar_back),
            'pan_uploaded' => !empty($this->pan_image),
            'rc_uploaded' => !empty($this->rc_image),
            'rc_verified' => $this->rc_verified,
            'dl_uploaded' => !empty($this->dl_image),
            'dl_verified' => $this->dl_verified,
            'vehicle_category_selected' => !empty($this->vehicle_category_id),
            'vehicle_model_selected' => !empty($this->vehicle_model_id)
        ];
    }

    /**
     * ============================================
     * ✅ HELPER METHODS
     * ============================================
     */

    public function isFleetOwner(): bool
    {
        return $this->userType && $this->userType->type_key === 'fleet_owner';
    }

    public function isDriver(): bool
    {
        return $this->userType && $this->userType->type_key === 'professional_driver';
    }

    public function hasContainerVehicle(): bool
    {
        return $this->vehicleCategory && $this->vehicleCategory->category_key === 'container';
    }

    public function hasOpenTruckVehicle(): bool
    {
        return $this->vehicleCategory && $this->vehicleCategory->category_key === 'open_truck';
    }

    public function isRegistrationComplete(): bool
    {
        return $this->is_verified && $this->is_completed;
    }

    public function canLogin(): bool
    {
        return $this->isRegistrationComplete();
    }

    public function generateAuthToken(): string
    {
        $tokenData = $this->id . ':' . $this->contact_number . ':' . now()->timestamp;
        return base64_encode($tokenData);
    }

    public static function validateAuthToken(string $token): ?self
    {
        try {
            $decoded = base64_decode($token);
            $parts = explode(':', $decoded);

            if (count($parts) < 3) {
                return null;
            }

            $vendorId = $parts[0];
            return self::find($vendorId);
        } catch (\Exception $e) {
            Log::warning('Invalid auth token', ['error' => $e->getMessage()]);
            return null;
        }
    }

    public function toSafeArray(): array
    {
        return [
            'id' => $this->id,
            'vendor_id' => $this->vendor_id,
            'user_type' => $this->userType ? [
                'id' => $this->userType->id,
                'type_key' => $this->userType->type_key,
                'title' => $this->userType->title
            ] : null,
            'vehicle_category' => $this->vehicleCategory ? [
                'id' => $this->vehicleCategory->id,
                'category_key' => $this->vehicleCategory->category_key,
                'category_name' => $this->vehicleCategory->category_name
            ] : null,
            'vehicle_model' => $this->vehicleModel ? [
                'id' => $this->vehicleModel->id,
                'model_name' => $this->vehicleModel->model_name,
                'vehicle_type_desc' => $this->vehicleModel->vehicle_type_desc,
                'carry_capacity_tons' => $this->vehicleModel->carry_capacity_tons
            ] : null,
            'contact_number' => $this->contact_number,
            'name' => $this->name,
            'email' => $this->email,
            'is_verified' => $this->is_verified,
            'is_completed' => $this->is_completed,
            'profile_completion' => $this->profile_completion,
            'vehicle_completion' => $this->vehicle_completion,
            'has_vehicle' => !empty($this->vehicle_registration_number),
            'vehicle_status' => $this->vehicle_status,
            'availability_status' => $this->availability_status,
            'is_online' => $this->is_online,
            'can_accept_bookings' => $this->can_accept_bookings,
            'has_active_plan' => $this->has_active_plan,
            'rc_verified' => $this->rc_verified,
            'dl_verified' => $this->dl_verified,
            'document_verification_status' => $this->document_verification_status,
            'is_fleet_owner' => $this->isFleetOwner(),
            'is_driver' => $this->isDriver(),
            'has_container_vehicle' => $this->hasContainerVehicle(),
            'has_open_truck_vehicle' => $this->hasOpenTruckVehicle(),
            'total_amount_paid' => $this->total_amount_paid,
            'successful_payments_count' => $this->successful_payments_count,
            'created_at' => $this->created_at,
            'last_login_at' => $this->last_login_at
        ];
    }

    /**
     * ============================================
     * ✅ SCOPES
     * ============================================
     */

    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }

    public function scopeCompleted($query)
    {
        return $query->where('is_completed', true);
    }

    public function scopeWithVehicle($query)
    {
        return $query->whereNotNull('vehicle_registration_number')
            ->where('vehicle_status', '!=', 'rejected');
    }

    public function scopeActiveVehicles($query)
    {
        return $query->where('vehicle_listed', true)
            ->where('vehicle_status', 'active');
    }

    public function scopeWithActivePlan($query)
    {
        return $query->whereHas('activePlanSubscription');
    }

    public function scopeAvailable($query)
    {
        return $query->where('availability_status', 'in')
            ->where('is_available_for_booking', true)
            ->where('vehicle_status', 'active');
    }

    public function scopeOnline($query)
    {
        return $query->where('availability_status', 'in');
    }

    public function scopeOffline($query)
    {
        return $query->where('availability_status', 'out');
    }

    public function scopeDlVerified($query)
    {
        return $query->where('dl_verified', true);
    }

    public function scopeFleetOwners($query)
    {
        return $query->whereHas('userType', function ($q) {
            $q->where('type_key', 'fleet_owner');
        });
    }

    public function scopeDrivers($query)
    {
        return $query->whereHas('userType', function ($q) {
            $q->where('type_key', 'professional_driver');
        });
    }

    public function scopeWithContainer($query)
    {
        return $query->whereHas('vehicleCategory', function ($q) {
            $q->where('category_key', 'container');
        });
    }

    public function scopeWithOpenTruck($query)
    {
        return $query->whereHas('vehicleCategory', function ($q) {
            $q->where('category_key', 'open_truck');
        });
    }

    public function scopeByVendorId($query, $vendorId)
    {
        return $query->where('vendor_id', $vendorId);
    }

    public function scopeWithPaymentRecords($query)
    {
        return $query->whereHas('payments');
    }

    public function scopeWithSubscriptions($query)
    {
        return $query->whereHas('planSubscriptions');
    }

    /**
     * ============================================
     * ✅ BOOT METHOD - FIXED (NO restored())
     * ============================================
     */

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($vendor) {
            if (empty($vendor->vendor_id)) {
                $vendor->vendor_id = self::generateUniqueVendorId($vendor->user_type_id);
            }

            Log::info('Creating vendor', [
                'vendor_id' => $vendor->vendor_id,
                'contact' => $vendor->contact_number,
                'user_type_id' => $vendor->user_type_id
            ]);
        });

        static::created(function ($vendor) {
            Log::info('Vendor created successfully', [
                'vendor_id' => $vendor->vendor_id,
                'db_id' => $vendor->id,
                'user_type' => $vendor->userType ? $vendor->userType->type_key : 'not_set'
            ]);
        });

        static::updating(function ($vendor) {
            if ($vendor->isDirty('user_type_id') && !empty($vendor->user_type_id)) {
                $oldVendorId = $vendor->getOriginal('vendor_id');
                $vendor->vendor_id = self::generateUniqueVendorId($vendor->user_type_id);

                Log::warning('User type changed - Vendor ID regenerated', [
                    'vendor_db_id' => $vendor->id,
                    'old_vendor_id' => $oldVendorId,
                    'new_vendor_id' => $vendor->vendor_id,
                    'from_user_type' => $vendor->getOriginal('user_type_id'),
                    'to_user_type' => $vendor->user_type_id
                ]);
            }

            if ($vendor->isDirty('referral_emp_id')) {
                Log::info('Referral Employee ID changed', [
                    'vendor_id' => $vendor->vendor_id,
                    'from' => $vendor->getOriginal('referral_emp_id'),
                    'to' => $vendor->referral_emp_id
                ]);
            }

            if ($vendor->isDirty('vehicle_status')) {
                Log::info('Vehicle status changed', [
                    'vendor_id' => $vendor->vendor_id,
                    'from' => $vendor->getOriginal('vehicle_status'),
                    'to' => $vendor->vehicle_status
                ]);
            }

            if ($vendor->isDirty('availability_status')) {
                Log::info('Availability status changed', [
                    'vendor_id' => $vendor->vendor_id,
                    'from' => $vendor->getOriginal('availability_status'),
                    'to' => $vendor->availability_status
                ]);
            }

            if ($vendor->isDirty('dl_verified')) {
                Log::info('DL verification status changed', [
                    'vendor_id' => $vendor->vendor_id,
                    'from' => $vendor->getOriginal('dl_verified'),
                    'to' => $vendor->dl_verified
                ]);
            }

            if ($vendor->isDirty('rc_verified')) {
                Log::info('RC verification status changed', [
                    'vendor_id' => $vendor->vendor_id,
                    'from' => $vendor->getOriginal('rc_verified'),
                    'to' => $vendor->rc_verified
                ]);
            }

            if ($vendor->isDirty('vehicle_category_id')) {
                Log::info('Vehicle category changed', [
                    'vendor_id' => $vendor->vendor_id,
                    'from' => $vendor->getOriginal('vehicle_category_id'),
                    'to' => $vendor->vehicle_category_id
                ]);
            }

            if ($vendor->isDirty('vehicle_model_id')) {
                Log::info('Vehicle model changed', [
                    'vendor_id' => $vendor->vendor_id,
                    'from' => $vendor->getOriginal('vehicle_model_id'),
                    'to' => $vendor->vehicle_model_id
                ]);
            }

            if ($vendor->isDirty('is_verified')) {
                Log::info('Vendor verification status changed', [
                    'vendor_id' => $vendor->vendor_id,
                    'from' => $vendor->getOriginal('is_verified'),
                    'to' => $vendor->is_verified
                ]);
            }

            if ($vendor->isDirty('is_completed')) {
                Log::info('Vendor completion status changed', [
                    'vendor_id' => $vendor->vendor_id,
                    'from' => $vendor->getOriginal('is_completed'),
                    'to' => $vendor->is_completed
                ]);
            }
        });

        static::updating(function ($vendor) {
            if ($vendor->isDirty('password')) {
                Log::info('Vendor password changed', [
                    'vendor_id' => $vendor->vendor_id
                ]);
            }
        });

        static::deleting(function ($vendor) {
            Log::info('Deleting vendor', [
                'vendor_id' => $vendor->vendor_id,
                'name' => $vendor->name,
                'email' => $vendor->email
            ]);
        });

        // ✅ REMOVED: static::restored() - NOT APPLICABLE WITHOUT SoftDeletes
    }
}
