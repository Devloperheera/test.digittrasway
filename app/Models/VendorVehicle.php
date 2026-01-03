<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class VendorVehicle extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'vendor_vehicles';

    protected $fillable = [
        'vendor_id',
        'vehicle_category_id',
        'vehicle_model_id',
        'vehicle_registration_number',
        'vehicle_name',
        'manufacturing_year',
        'vehicle_color',
        'chassis_number',
        'engine_number',
        'owner_name',
        'rc_number',
        'insurance_number',
        'insurance_expiry',
        'fitness_expiry',
        'permit_expiry',
        'rc_verified',
        'rc_verified_data',
        'rc_verification_date',
        'rc_verification_status',
        'dl_number',
        'dl_verified',
        'dl_verified_data',
        'dl_verification_date',
        'vehicle_image',
        'rc_front_image',
        'rc_back_image',
        'insurance_image',
        'fitness_certificate',
        'permit_image',
        'dl_image',
        'status',
        'rejection_reason',
        'approved_at',
        'approved_by',
        'is_listed',
        'listed_at',
        'is_available',
        'availability_status',
        'current_latitude',
        'current_longitude',
        'current_location',
        'last_location_update',
        'can_accept_bookings',
        'completed_trips',
        'cancelled_trips',
        'average_rating',
        'total_ratings',
        'features',
        'description',
        'has_gps',
        'has_insurance',
        'display_order'
    ];

    protected $casts = [
        'rc_verified' => 'boolean',
        'dl_verified' => 'boolean',
        'is_listed' => 'boolean',
        'is_available' => 'boolean',
        'can_accept_bookings' => 'boolean',
        'has_gps' => 'boolean',
        'has_insurance' => 'boolean',
        'rc_verified_data' => 'array',
        'dl_verified_data' => 'array',
        'features' => 'array',
        'insurance_expiry' => 'date',
        'fitness_expiry' => 'date',
        'permit_expiry' => 'date',
        'rc_verification_date' => 'datetime',
        'dl_verification_date' => 'datetime',
        'approved_at' => 'datetime',
        'listed_at' => 'datetime',
        'last_location_update' => 'datetime',
        'current_latitude' => 'decimal:8',
        'current_longitude' => 'decimal:8',
        'average_rating' => 'decimal:2',
        'completed_trips' => 'integer',
        'cancelled_trips' => 'integer',
        'total_ratings' => 'integer',
        'display_order' => 'integer',
        'manufacturing_year' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime'
    ];

    /**
     * Relationships
     */
    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }

    public function category()
    {
        return $this->belongsTo(VehicleCategory::class, 'vehicle_category_id');
    }

    public function vehicleCategory()
    {
        return $this->category(); // Alias for consistency
    }

    public function model()
    {
        return $this->belongsTo(VehicleModel::class, 'vehicle_model_id');
    }

    public function vehicleModel()
    {
        return $this->model(); // Alias for consistency
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function bookings()
    {
        return $this->hasMany(TruckBooking::class, 'vendor_vehicle_id');
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->whereIn('status', ['approved', 'active']);
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    public function scopeListed($query)
    {
        return $query->where('is_listed', true);
    }

    public function scopeAvailable($query)
    {
        return $query->where('is_available', true)
                     ->where('availability_status', 'available');
    }

    public function scopeVerified($query)
    {
        return $query->where('rc_verified', true);
    }

    /**
     * Check if vehicle is active
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Check if vehicle is approved
     */
    public function isApproved(): bool
    {
        return in_array($this->status, ['approved', 'active']);
    }

    /**
     * Check if vehicle is pending
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if RC is verified
     */
    public function isVerified(): bool
    {
        return $this->rc_verified === true;
    }

    /**
     * Check if RC is verified (alias)
     */
    public function isRcVerified(): bool
    {
        return $this->isVerified();
    }

    /**
     * Check if DL is verified
     */
    public function isDlVerified(): bool
    {
        return $this->dl_verified === true;
    }

    /**
     * Check if vehicle can accept bookings
     */
    public function canAcceptBookings(): bool
    {
        return $this->is_available
            && $this->is_listed
            && in_array($this->status, ['active', 'approved'])
            && $this->availability_status === 'available'
            && !$this->hasExpiredDocuments();
    }

    /**
     * Check if documents are expired
     */
    public function hasExpiredDocuments(): bool
    {
        $now = now();

        return ($this->insurance_expiry && $this->insurance_expiry < $now) ||
               ($this->fitness_expiry && $this->fitness_expiry < $now) ||
               ($this->permit_expiry && $this->permit_expiry < $now);
    }

    /**
     * Get all document URLs
     */
    public function getDocumentsAttribute(): array
    {
        return [
            'vehicle_image' => $this->vehicle_image ? asset('storage/' . $this->vehicle_image) : null,
            'rc_front_image' => $this->rc_front_image ? asset('storage/' . $this->rc_front_image) : null,
            'rc_back_image' => $this->rc_back_image ? asset('storage/' . $this->rc_back_image) : null,
            'insurance_image' => $this->insurance_image ? asset('storage/' . $this->insurance_image) : null,
            'fitness_certificate' => $this->fitness_certificate ? asset('storage/' . $this->fitness_certificate) : null,
            'permit_image' => $this->permit_image ? asset('storage/' . $this->permit_image) : null,
            'dl_image' => $this->dl_image ? asset('storage/' . $this->dl_image) : null
        ];
    }

    /**
     * Get vehicle full name
     */
    public function getVehicleFullNameAttribute(): string
    {
        $category = $this->category ? $this->category->category_name : '';
        $model = $this->model ? $this->model->model_name : '';
        $regNo = $this->vehicle_registration_number;

        return trim("{$category} - {$model} ({$regNo})");
    }

    /**
     * Get status badge
     */
    public function getStatusBadgeAttribute(): string
    {
        $badges = [
            'pending' => '<span class="badge bg-warning text-dark"><i class="fas fa-clock me-1"></i>Pending</span>',
            'approved' => '<span class="badge bg-success"><i class="fas fa-check-circle me-1"></i>Approved</span>',
            'active' => '<span class="badge bg-success"><i class="fas fa-check-circle me-1"></i>Active</span>',
            'rejected' => '<span class="badge bg-danger"><i class="fas fa-times-circle me-1"></i>Rejected</span>',
            'under_review' => '<span class="badge bg-info"><i class="fas fa-eye me-1"></i>Under Review</span>',
        ];

        return $badges[$this->status] ?? '<span class="badge bg-secondary">Unknown</span>';
    }

    /**
     * Get availability badge
     */
    public function getAvailabilityBadgeAttribute(): string
    {
        return $this->is_available
            ? '<span class="badge bg-success"><i class="fas fa-check-circle me-1"></i>Available</span>'
            : '<span class="badge bg-secondary"><i class="fas fa-times-circle me-1"></i>Unavailable</span>';
    }

    /**
     * Approve vehicle
     */
    public function approve($approvedBy = null): bool
    {
        return $this->update([
            'status' => 'approved',
            'approved_at' => now(),
            'approved_by' => $approvedBy,
            'rejection_reason' => null
        ]);
    }

    /**
     * Reject vehicle
     */
    public function reject($reason): bool
    {
        return $this->update([
            'status' => 'rejected',
            'rejection_reason' => $reason,
            'approved_at' => null,
            'approved_by' => null,
            'is_listed' => false
        ]);
    }

    /**
     * List vehicle
     */
    public function list(): bool
    {
        if ($this->isApproved()) {
            return $this->update([
                'is_listed' => true,
                'listed_at' => now()
            ]);
        }
        return false;
    }

    /**
     * Unlist vehicle
     */
    public function unlist(): bool
    {
        return $this->update([
            'is_listed' => false
        ]);
    }

    /**
     * Update location
     */
    public function updateLocation($latitude, $longitude, $location = null): bool
    {
        return $this->update([
            'current_latitude' => $latitude,
            'current_longitude' => $longitude,
            'current_location' => $location,
            'last_location_update' => now()
        ]);
    }

    /**
     * Mark as available
     */
    public function markAsAvailable(): bool
    {
        return $this->update([
            'is_available' => true,
            'availability_status' => 'available'
        ]);
    }

    /**
     * Mark as unavailable
     */
    public function markAsUnavailable(): bool
    {
        return $this->update([
            'is_available' => false,
            'availability_status' => 'unavailable'
        ]);
    }
}
