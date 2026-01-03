<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable;

    protected $fillable = [
        // ✅ UNIQUE USER ID
        'user_id',              // DTB000000001
        'contact_number',

        // ✅ Referral fields
        'referral_emp_id',
        'referred_by_employee_id',
        'app_installed_at',

        // ✅ OTP fields
        'otp',
        'otp_expires_at',
        'otp_attempts',
        'last_otp_sent_at',
        'otp_resend_count',

        // ✅ Basic info
        'name',
        'email',
        'dob',
        'gender',
        'emergency_contact',

        // ✅ Documents - Aadhaar
        'aadhar_number',
        'aadhar_front',
        'aadhar_back',
        'aadhar_manual',              // ✅ ADD - Aadhaar manual flag JSON

        // ✅ Documents - PAN
        'pan_number',
        'pan_image',
        'pan_verified',

        // ✅ Documents - GST (NEW SECTION)
        'gst_number',                 // ✅ ADD - GST number
        'gst_image',                  // ✅ ADD - GST image path
        'gst_verified',               // ✅ ADD - GST verification status
        'gst_manual',                 // ✅ ADD - GST manual flag JSON

        // ✅ Documents - RC (Registration Certificate)
        'rc_number',                  // ✅ ADD - RC number
        'rc_image',                   // ✅ ADD - RC image path
        'rc_verified',                // ✅ ADD - RC verification status
        'rc_verified_data',           // ✅ ADD - RC verified data JSON
        'rc_verification_date',       // ✅ ADD - RC verification date
        'rc_manual',                  // ✅ ADD - RC manual flag JSON

        // ✅ Documents - DL (Driving License)
        'dl_number',                  // ✅ ADD - DL number
        'dl_image',                   // ✅ ADD - DL image path
        'dl_verified',                // ✅ ADD - DL verification status
        'dl_verified_data',           // ✅ ADD - DL verified data JSON
        'dl_verification_date',       // ✅ ADD - DL verification date
        'dl_manual',                  // ✅ ADD - DL manual flag JSON

        // ✅ Address
        'full_address',
        'state',
        'city',
        'pincode',
        'country',
        'same_address',
        'postal_code',

        // ✅ Bank details
        'bank_name',
        'account_number',
        'ifsc',

        // ✅ Status flags
        'is_verified',
        'declaration',
        'is_completed',
        'password',
        'remember_token',

        // ✅ Aadhaar DigiLocker fields
        'aadhaar_digilocker_client_id',
        'aadhaar_verification_date',
        'aadhaar_verified_data',
        'aadhaar_verified',
        'verified_dob',
        'verified_gender',
        'verified_address',
        'verified_pincode',
        'verified_state',
        'verification_completed_at',
        'last_login_at',
        'login_count',
    ];


    protected $hidden = [
        'password',
        'remember_token',
        'otp'
    ];

    protected $casts = [
        // Date & Time casts
        'email_verified_at' => 'datetime',
        'dob' => 'date',
        'otp_expires_at' => 'datetime',
        'last_otp_sent_at' => 'datetime',
        'app_installed_at' => 'datetime',
        'aadhaar_verification_date' => 'datetime',
        'rc_verification_date' => 'datetime',      // ✅ ADD - RC verification date
        'dl_verification_date' => 'datetime',      // ✅ ADD - DL verification date
        'verification_completed_at' => 'datetime',
        'last_login_at' => 'datetime',

        // Password hashing
        'password' => 'hashed',

        // Boolean casts
        'is_verified' => 'boolean',
        'declaration' => 'boolean',
        'is_completed' => 'boolean',
        'same_address' => 'boolean',
        'aadhaar_verified' => 'boolean',
        'pan_verified' => 'boolean',
        'rc_verified' => 'boolean',
        'dl_verified' => 'boolean',                // ✅ ADD - DL verification status
        'gst_verified' => 'boolean',               // ✅ ADD - GST verification status

        // Integer casts
        'otp_attempts' => 'integer',
        'otp_resend_count' => 'integer',
        'login_count' => 'integer',

        // JSON/Array casts (automatically encode/decode)
        'aadhaar_verified_data' => 'array',
        'rc_verified_data' => 'array',             // ✅ ADD - RC verification data
        'dl_verified_data' => 'array',             // ✅ ADD - DL verification data
        'aadhar_manual' => 'array',                // ✅ ADD - Aadhaar manual flag data
        'gst_manual' => 'array',                   // ✅ ADD - GST manual flag data
        'rc_manual' => 'array',                    // ✅ ADD - RC manual flag data
        'dl_manual' => 'array',                    // ✅ ADD - DL manual flag data
    ];


    /**
     * ============================================
     * ✅ UNIQUE USER ID GENERATION (DTB000000001)
     * ============================================
     */
    public static function generateUniqueUserId(): string
    {
        DB::beginTransaction();

        try {
            // Lock table for consistent counting
            $lastUser = DB::table('users')
                ->where('user_id', 'LIKE', 'DTB%')
                ->lockForUpdate()
                ->orderByRaw('CAST(SUBSTRING(user_id, 4) AS UNSIGNED) DESC')
                ->first();

            if ($lastUser && $lastUser->user_id) {
                // Extract numeric part and increment
                $lastNumber = (int) substr($lastUser->user_id, 3);
                $newNumber = $lastNumber + 1;
            } else {
                // First user
                $newNumber = 1;
            }

            // Format: DTB + 9 digits padded with zeros
            $userId = 'DTB' . str_pad($newNumber, 9, '0', STR_PAD_LEFT);

            // Double-check uniqueness
            $exists = DB::table('users')->where('user_id', $userId)->exists();

            if ($exists) {
                // Fallback to timestamp-based unique ID
                $userId = 'DTB' . time() . rand(100, 999);
            }

            DB::commit();

            Log::info('Generated unique user ID', [
                'user_id' => $userId,
                'last_number' => $lastNumber ?? 0,
                'new_number' => $newNumber
            ]);

            return $userId;

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Error generating user ID', [
                'error' => $e->getMessage()
            ]);

            // Fallback to timestamp-based ID
            return 'DTB' . time() . rand(100, 999);
        }
    }

    /**
     * ============================================
     * JWT METHODS
     * ============================================
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [
            'user_id' => $this->user_id,
            'contact_number' => $this->contact_number,
            'is_verified' => $this->is_verified,
            'is_completed' => $this->is_completed
        ];
    }

    /**
     * ============================================
     * RELATIONSHIPS
     * ============================================
     */

    /**
     * Referred by Employee (using referral_emp_id -> emp_id)
     */
    public function referredByEmployee()
    {
        return $this->belongsTo(Employee::class, 'referral_emp_id', 'emp_id');
    }

    /**
     * Plan Subscriptions
     */
    public function subscriptions()
    {
        return $this->hasMany(PlanSubscription::class);
    }

    /**
     * Get active subscription
     */
    public function activeSubscription()
    {
        return $this->hasOne(PlanSubscription::class)
            ->where('status', 'active')
            ->where('expires_at', '>', now())
            ->latest();
    }

    /**
     * Check if user has active subscription
     */
    public function hasActiveSubscription(): bool
    {
        return $this->activeSubscription()->exists();
    }

    /**
     * ============================================
     * OTP METHODS
     * ============================================
     */

    /**
     * Generate 4-digit OTP
     */
    public function generateOtp(): string
    {
        $otp = str_pad(random_int(0, 9999), 4, '0', STR_PAD_LEFT);

        $sentTime = Carbon::now('Asia/Kolkata');
        $expiryTime = Carbon::now('Asia/Kolkata')->addMinutes(10);

        $this->update([
            'otp' => $otp,
            'last_otp_sent_at' => $sentTime,
            'otp_expires_at' => $expiryTime,
            'otp_attempts' => 0
        ]);

        Log::info('4-Digit OTP Generated', [
            'user_id' => $this->user_id,
            'db_id' => $this->id,
            'otp' => $otp,
            'otp_length' => strlen($otp),
            'sent_at' => $sentTime->format('Y-m-d H:i:s'),
            'expires_at' => $expiryTime->format('Y-m-d H:i:s'),
            'difference_minutes' => $expiryTime->diffInMinutes($sentTime)
        ]);

        return $otp;
    }

    /**
     * Validate OTP (4-digit)
     */
    public function isOtpValid($otp): bool
    {
        if (strlen($otp) !== 4 || !is_numeric($otp)) {
            Log::warning('Invalid OTP format', [
                'user_id' => $this->user_id,
                'otp_length' => strlen($otp),
                'is_numeric' => is_numeric($otp)
            ]);
            return false;
        }

        $currentTime = Carbon::now('Asia/Kolkata');
        $expiryTime = $this->otp_expires_at ?
            Carbon::parse($this->otp_expires_at)->setTimezone('Asia/Kolkata') :
            null;

        $isValid = $this->otp == $otp &&
            $expiryTime &&
            $currentTime->lte($expiryTime) &&
            $this->otp_attempts < 3;

        Log::info('OTP Validation', [
            'user_id' => $this->user_id,
            'otp_match' => $this->otp == $otp,
            'is_expired' => $expiryTime ? $currentTime->gt($expiryTime) : true,
            'attempts' => $this->otp_attempts,
            'is_valid' => $isValid
        ]);

        return $isValid;
    }

    /**
     * Increment OTP Attempts
     */
    public function incrementOtpAttempts(): void
    {
        $this->increment('otp_attempts');

        Log::info('OTP attempt incremented', [
            'user_id' => $this->user_id,
            'attempts' => $this->otp_attempts,
            'remaining' => $this->getRemainingOtpAttempts()
        ]);
    }

    /**
     * Check if can send OTP (60 seconds rate limit)
     */
    public function canSendOtp(): bool
    {
        if (!$this->last_otp_sent_at) {
            return true;
        }

        $currentTime = Carbon::now('Asia/Kolkata');
        $lastSentTime = Carbon::parse($this->last_otp_sent_at)->setTimezone('Asia/Kolkata');

        $secondsSinceLastOtp = $currentTime->diffInSeconds($lastSentTime);
        $canSend = $secondsSinceLastOtp >= 60;

        Log::info('Can send OTP check', [
            'user_id' => $this->user_id,
            'seconds_since_last' => $secondsSinceLastOtp,
            'can_send' => $canSend
        ]);

        return $canSend;
    }

    /**
     * Check if can resend OTP (90 seconds rate limit)
     */
    public function canResendOtp(): bool
    {
        if (!$this->last_otp_sent_at) {
            return true;
        }

        $currentTime = Carbon::now('Asia/Kolkata');
        $lastSentTime = Carbon::parse($this->last_otp_sent_at)->setTimezone('Asia/Kolkata');

        return $currentTime->diffInSeconds($lastSentTime) >= 90;
    }

    /**
     * Get wait time for resend
     */
    public function getResendWaitTime(): int
    {
        if (!$this->last_otp_sent_at) {
            return 0;
        }

        $currentTime = Carbon::now('Asia/Kolkata');
        $lastSentTime = Carbon::parse($this->last_otp_sent_at)->setTimezone('Asia/Kolkata');

        $canResendAt = $lastSentTime->copy()->addSeconds(90);

        if ($currentTime->gte($canResendAt)) {
            return 0;
        }

        return $canResendAt->diffInSeconds($currentTime);
    }

    /**
     * Check daily OTP limit
     */
    public function hasExceededDailyOtpLimit(): bool
    {
        $today = Carbon::now('Asia/Kolkata')->startOfDay();

        $todayOtpCount = self::where('contact_number', $this->contact_number)
            ->where('last_otp_sent_at', '>=', $today)
            ->count();

        return $todayOtpCount >= 10;
    }

    /**
     * Increment resend counter
     */
    public function incrementResendCount(): void
    {
        $this->increment('otp_resend_count');

        Log::info('OTP resend count incremented', [
            'user_id' => $this->user_id,
            'resend_count' => $this->otp_resend_count
        ]);
    }

    /**
     * Reset OTP data
     */
    public function clearOtp(): void
    {
        $this->update([
            'otp' => null,
            'otp_expires_at' => null,
            'otp_attempts' => 0,
            'otp_resend_count' => 0
        ]);

        Log::info('OTP cleared', ['user_id' => $this->user_id]);
    }

    /**
     * Check if OTP is expired
     */
    public function isOtpExpired(): bool
    {
        if (!$this->otp_expires_at) {
            return true;
        }

        $currentTime = Carbon::now('Asia/Kolkata');
        $expiryTime = Carbon::parse($this->otp_expires_at)->setTimezone('Asia/Kolkata');

        return $currentTime->gt($expiryTime);
    }

    /**
     * Get remaining OTP attempts
     */
    public function getRemainingOtpAttempts(): int
    {
        return max(0, 3 - $this->otp_attempts);
    }

    /**
     * Get OTP status
     */
    public function getOtpStatus(): array
    {
        $currentTime = Carbon::now('Asia/Kolkata');

        $expiresAt = $this->otp_expires_at ?
            Carbon::parse($this->otp_expires_at)->setTimezone('Asia/Kolkata') : null;
        $lastSentAt = $this->last_otp_sent_at ?
            Carbon::parse($this->last_otp_sent_at)->setTimezone('Asia/Kolkata') : null;

        return [
            'has_otp' => !empty($this->otp),
            'otp_length' => strlen($this->otp ?? ''),
            'is_4_digit' => strlen($this->otp ?? '') === 4,
            'is_expired' => $this->isOtpExpired(),
            'attempts_used' => $this->otp_attempts,
            'attempts_remaining' => $this->getRemainingOtpAttempts(),
            'resend_count' => $this->otp_resend_count ?? 0,
            'can_resend' => $this->canResendOtp(),
            'resend_wait_time' => $this->getResendWaitTime(),
            'expires_at' => $expiresAt ? $expiresAt->format('Y-m-d H:i:s') : null,
            'last_sent_at' => $lastSentAt ? $lastSentAt->format('Y-m-d H:i:s') : null,
            'time_difference_minutes' => $expiresAt && $lastSentAt ?
                $expiresAt->diffInMinutes($lastSentAt) : null
        ];
    }

    /**
     * ============================================
     * ACCOUNT STATUS METHODS
     * ============================================
     */

    public function getAccountStatus(): string
    {
        if ($this->is_completed && $this->is_verified) {
            return 'completed';
        } elseif ($this->is_verified && !$this->is_completed) {
            return 'verified_incomplete';
        } elseif (!$this->is_verified) {
            return 'not_verified';
        }

        return 'unknown';
    }

    public function isAccountReady(): bool
    {
        return $this->is_verified &&
            $this->is_completed &&
            !empty($this->password);
    }

    /**
     * ============================================
     * SCOPES
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

    public function scopeRecentOtpRequests($query)
    {
        $today = Carbon::now('Asia/Kolkata')->startOfDay();
        return $query->where('last_otp_sent_at', '>=', $today);
    }

    public function scopeReferred($query)
    {
        return $query->whereNotNull('referral_emp_id');
    }

    /**
     * ============================================
     * ACCESSOR/MUTATOR FOR TIMEZONE
     * ============================================
     */

    public function getIndianTimeAttribute(): string
    {
        return Carbon::now('Asia/Kolkata')->format('Y-m-d H:i:s');
    }

    public function getCreatedAtAttribute($value)
    {
        return $value ? Carbon::parse($value)->setTimezone('Asia/Kolkata') : null;
    }

    public function getUpdatedAtAttribute($value)
    {
        return $value ? Carbon::parse($value)->setTimezone('Asia/Kolkata') : null;
    }

    public function getOtpExpiresAtAttribute($value)
    {
        return $value ? Carbon::parse($value)->setTimezone('Asia/Kolkata') : null;
    }

    public function getLastOtpSentAtAttribute($value)
    {
        return $value ? Carbon::parse($value)->setTimezone('Asia/Kolkata') : null;
    }

    public function getAppInstalledAtAttribute($value)
    {
        return $value ? Carbon::parse($value)->setTimezone('Asia/Kolkata') : null;
    }

    /**
     * ============================================
     * BOOT METHOD
     * ============================================
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($user) {
            // ✅ AUTO-GENERATE UNIQUE USER ID
            if (empty($user->user_id)) {
                $user->user_id = self::generateUniqueUserId();
            }

            Log::info('Creating new user', [
                'user_id' => $user->user_id,
                'contact_number' => $user->contact_number,
                'referral_emp_id' => $user->referral_emp_id ?? 'none'
            ]);
        });

        static::created(function ($user) {
            Log::info('User created successfully', [
                'user_id' => $user->user_id,
                'db_id' => $user->id,
                'contact_number' => $user->contact_number,
                'referred_by' => $user->referral_emp_id ?? 'none'
            ]);
        });

        static::updating(function ($user) {
            if ($user->isDirty('otp')) {
                Log::info('OTP updated', [
                    'user_id' => $user->user_id,
                    'new_otp_length' => strlen($user->otp ?? ''),
                    'is_4_digit' => strlen($user->otp ?? '') === 4
                ]);
            }

            if ($user->isDirty('is_verified')) {
                Log::info('Verification status changed', [
                    'user_id' => $user->user_id,
                    'from' => $user->getOriginal('is_verified'),
                    'to' => $user->is_verified
                ]);
            }

            if ($user->isDirty('aadhaar_verified')) {
                Log::info('Aadhaar verification status changed', [
                    'user_id' => $user->user_id,
                    'from' => $user->getOriginal('aadhaar_verified'),
                    'to' => $user->aadhaar_verified
                ]);
            }
        });
    }
}
