<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Log;

class Employee extends Model
{
    use HasFactory;

    protected $fillable = [
        'emp_id',
        'name',
        'email',
        'phone',
        'designation',
        'department',
        'date_of_joining',
        'salary',
        'address',
        'photo',
        'status',
        // ✅ Document fields
        'aadhar_front',
        'aadhar_back',
        'pan_card',
        'driving_license',
        'address_proof',
        'aadhar_number',
        'pan_number',
        'dl_number'
    ];

    protected $casts = [
        'date_of_joining' => 'date',
        'salary' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // ✅ RELATIONSHIPS

    /**
     * Users referred by this employee
     */
    public function referredUsers()
    {
        return $this->hasMany(User::class, 'referred_by_employee_id');
    }

    /**
     * Active referred users (verified)
     */
    public function activeReferredUsers()
    {
        return $this->hasMany(User::class, 'referred_by_employee_id')
                    ->where('is_verified', true);
    }

    // ✅ ACCESSORS & ATTRIBUTES

    /**
     * Get total app installs count
     */
    public function getAppInstallsCountAttribute()
    {
        return $this->referredUsers()->count();
    }

    /**
     * Get today's installs count
     */
    public function getTodayInstallsCountAttribute()
    {
        return $this->referredUsers()
                    ->whereDate('app_installed_at', today())
                    ->count();
    }

    /**
     * Get this week's installs count
     */
    public function getWeekInstallsCountAttribute()
    {
        return $this->referredUsers()
                    ->whereBetween('app_installed_at', [
                        now()->startOfWeek(),
                        now()->endOfWeek()
                    ])
                    ->count();
    }

    /**
     * Get this month's installs count
     */
    public function getMonthInstallsCountAttribute()
    {
        return $this->referredUsers()
                    ->whereMonth('app_installed_at', now()->month)
                    ->whereYear('app_installed_at', now()->year)
                    ->count();
    }

    /**
     * Get verified users count
     */
    public function getVerifiedUsersCountAttribute()
    {
        return $this->referredUsers()
                    ->where('is_verified', true)
                    ->count();
    }

    /**
     * Get conversion rate (verified/total)
     */
    public function getConversionRateAttribute()
    {
        $total = $this->app_installs_count;
        if ($total == 0) return 0;

        $verified = $this->verified_users_count;
        return round(($verified / $total) * 100, 2);
    }

    /**
     * Get photo URL
     */
    public function getPhotoUrlAttribute()
    {
        return $this->photo
            ? asset('storage/' . $this->photo)
            : asset('images/default-avatar.png');
    }

    /**
     * Get document URLs
     */
    public function getAadharFrontUrlAttribute()
    {
        return $this->aadhar_front ? asset('storage/' . $this->aadhar_front) : null;
    }

    public function getAadharBackUrlAttribute()
    {
        return $this->aadhar_back ? asset('storage/' . $this->aadhar_back) : null;
    }

    public function getPanCardUrlAttribute()
    {
        return $this->pan_card ? asset('storage/' . $this->pan_card) : null;
    }

    public function getDrivingLicenseUrlAttribute()
    {
        return $this->driving_license ? asset('storage/' . $this->driving_license) : null;
    }

    public function getAddressProofUrlAttribute()
    {
        return $this->address_proof ? asset('storage/' . $this->address_proof) : null;
    }

    // ✅ HELPER METHODS

    /**
     * Get install statistics
     */
    public function getInstallStats(): array
    {
        return [
            'total_installs' => $this->app_installs_count,
            'today_installs' => $this->today_installs_count,
            'week_installs' => $this->week_installs_count,
            'month_installs' => $this->month_installs_count,
            'verified_users' => $this->verified_users_count,
            'pending_users' => $this->app_installs_count - $this->verified_users_count,
            'conversion_rate' => $this->conversion_rate,
        ];
    }

    /**
     * Check if employee is active
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Get recent referred users
     */
    public function getRecentReferrals($limit = 10)
    {
        return $this->referredUsers()
                    ->latest('app_installed_at')
                    ->limit($limit)
                    ->get();
    }

    /**
     * Check if all documents uploaded
     */
    public function hasAllDocuments(): bool
    {
        return !empty($this->aadhar_front)
            && !empty($this->aadhar_back)
            && !empty($this->pan_card);
    }

    /**
     * Get uploaded documents count
     */
    public function getUploadedDocumentsCount(): int
    {
        $count = 0;
        if ($this->aadhar_front) $count++;
        if ($this->aadhar_back) $count++;
        if ($this->pan_card) $count++;
        if ($this->driving_license) $count++;
        if ($this->address_proof) $count++;
        return $count;
    }

    // ✅ SCOPES

    /**
     * Active employees scope
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Inactive employees scope
     */
    public function scopeInactive($query)
    {
        return $query->where('status', 'inactive');
    }

    /**
     * Top performers scope
     */
    public function scopeTopPerformers($query, $limit = 10)
    {
        return $query->withCount('referredUsers')
                    ->orderBy('referred_users_count', 'desc')
                    ->limit($limit);
    }

    /**
     * With install counts
     */
    public function scopeWithInstallCounts($query)
    {
        return $query->withCount([
            'referredUsers as total_installs',
            'referredUsers as today_installs' => function ($q) {
                $q->whereDate('app_installed_at', today());
            },
            'referredUsers as month_installs' => function ($q) {
                $q->whereMonth('app_installed_at', now()->month)
                  ->whereYear('app_installed_at', now()->year);
            },
            'referredUsers as verified_count' => function ($q) {
                $q->where('is_verified', true);
            }
        ]);
    }

    /**
     * Search scope
     */
    public function scopeSearch($query, $term)
    {
        return $query->where(function($q) use ($term) {
            $q->where('emp_id', 'like', "%{$term}%")
              ->orWhere('name', 'like', "%{$term}%")
              ->orWhere('email', 'like', "%{$term}%")
              ->orWhere('phone', 'like', "%{$term}%")
              ->orWhere('designation', 'like', "%{$term}%")
              ->orWhere('department', 'like', "%{$term}%");
        });
    }

    // ✅ BOOT METHOD - Auto-generate Employee ID

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($employee) {
            if (empty($employee->emp_id)) {
                $employee->emp_id = self::generateEmployeeId();
            }

            Log::info('Creating new employee', [
                'emp_id' => $employee->emp_id,
                'name' => $employee->name,
                'email' => $employee->email
            ]);
        });

        static::created(function ($employee) {
            Log::info('Employee created successfully', [
                'id' => $employee->id,
                'emp_id' => $employee->emp_id,
                'name' => $employee->name
            ]);
        });

        static::updating(function ($employee) {
            if ($employee->isDirty('status')) {
                Log::info('Employee status changed', [
                    'emp_id' => $employee->emp_id,
                    'from' => $employee->getOriginal('status'),
                    'to' => $employee->status
                ]);
            }
        });

        static::deleted(function ($employee) {
            Log::warning('Employee deleted', [
                'emp_id' => $employee->emp_id,
                'name' => $employee->name
            ]);
        });
    }

    /**
     * Generate unique Employee ID (DTE0001, DTE0002, etc)
     */
    public static function generateEmployeeId(): string
    {
        // Get last employee ordered by ID
        $lastEmployee = self::orderBy('id', 'desc')->first();

        if (!$lastEmployee || empty($lastEmployee->emp_id)) {
            return 'DTE0001';
        }

        // Extract number from last ID (e.g., DTE0001 -> 1)
        $lastNumber = (int) substr($lastEmployee->emp_id, 3);

        // Increment
        $newNumber = $lastNumber + 1;

        // Format with leading zeros (DTE0001, DTE0002, etc.)
        return 'DTE' . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Validate Employee ID format (DTE0001)
     */
    public static function isValidEmpId(string $empId): bool
    {
        return preg_match('/^DTE\d{4}$/', $empId) === 1;
    }

    /**
     * Find employee by emp_id
     */
    public static function findByEmpId(string $empId)
    {
        return self::where('emp_id', $empId)->first();
    }

    /**
     * Get next employee ID (for preview)
     */
    public static function getNextEmployeeId(): string
    {
        $lastEmployee = self::orderBy('id', 'desc')->first();

        if (!$lastEmployee || empty($lastEmployee->emp_id)) {
            return 'DTE0001';
        }

        $lastNumber = (int) substr($lastEmployee->emp_id, 3);
        $newNumber = $lastNumber + 1;

        return 'DTE' . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }
}
