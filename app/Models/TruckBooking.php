<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TruckBooking extends Model
{
    use HasFactory;

    protected $fillable = [
        // ✅ User & Vendor Info
        'booking_id',
        'user_id',
        'assigned_vendor_id',
        'vendor_name',
        'vendor_vehicle_number',
        'vendor_contact',

        // ✅ Vehicle Info
        'vehicle_model_id',
        'truck_type_id',
        'truck_type_name',
        'truck_specification_id',
        'truck_length',
        'tyre_count',
        'truck_height',

        // ✅ Location Info
        'pickup_address',
        'pickup_latitude',
        'pickup_longitude',
        'drop_address',
        'drop_latitude',
        'drop_longitude',

        // ✅ NEW: Tracking fields (for live location)
        'current_vendor_latitude',
        'current_vendor_longitude',
        'current_vendor_location',
        'location_updated_at',
        'distance_covered_km',
        'distance_remaining_km',
        'estimated_time_remaining_mins',
        'current_speed_kmph',
        'estimated_arrival_time',
        'location_history',

        // ✅ Material Info
        'material_id',
        'material_name',
        'material_weight',

        // ✅ Trip Info
        'distance_km',
        'pickup_datetime',
        'drop_datetime',
        'special_instructions',

        // ✅ Pricing Info
        'price_per_km',
        'estimated_price',
        'adjusted_price',
        'final_amount',
        'final_price', // Keep for backward compatibility
        'price_breakdown',

        // ✅ Payment Info
        'payment_method',
        'payment_status',
        'payment_completed_at',

        // ✅ Status & Timestamps
        'status',
        'vendor_accepted_at',
        'trip_started_at',
        'trip_completed_at',
        'cancelled_at',
        'cancellation_reason'
    ];

    protected $casts = [
        // Location coordinates
        'pickup_latitude' => 'decimal:8',
        'pickup_longitude' => 'decimal:8',
        'drop_latitude' => 'decimal:8',
        'drop_longitude' => 'decimal:8',
        'current_vendor_latitude' => 'decimal:8',
        'current_vendor_longitude' => 'decimal:8',

        // Measurements
        'material_weight' => 'decimal:2',
        'truck_length' => 'decimal:2',
        'truck_height' => 'decimal:2',
        'distance_km' => 'decimal:2',
        'distance_covered_km' => 'decimal:2',
        'distance_remaining_km' => 'decimal:2',
        'current_speed_kmph' => 'decimal:2',

        // Pricing
        'price_per_km' => 'decimal:2',
        'estimated_price' => 'decimal:2',
        'adjusted_price' => 'decimal:2',
        'final_amount' => 'decimal:2',
        'final_price' => 'decimal:2',
        'price_breakdown' => 'json',
        'location_history' => 'json',

        // Integers
        'tyre_count' => 'integer',
        'estimated_time_remaining_mins' => 'integer',

        // Timestamps
        'pickup_datetime' => 'datetime',
        'drop_datetime' => 'datetime',
        'payment_completed_at' => 'datetime',
        'vendor_accepted_at' => 'datetime',
        'trip_started_at' => 'datetime',
        'trip_completed_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'location_updated_at' => 'datetime',
        'estimated_arrival_time' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    protected $attributes = [
        'status' => 'pending',
        'payment_status' => 'pending'
    ];

    // ✅ Auto-generate booking ID
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->booking_id)) {
                $model->booking_id = 'TRK' . now()->format('YmdHis') . rand(1000, 9999);
            }

            if (empty($model->status)) {
                $model->status = 'pending';
            }
        });
    }

    // ✅ Relationships
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function assignedVendor()
    {
        return $this->belongsTo(Vendor::class, 'assigned_vendor_id');
    }

    public function vehicleModel()
    {
        return $this->belongsTo(VehicleModel::class, 'vehicle_model_id');
    }

    public function material()
    {
        return $this->belongsTo(Material::class, 'material_id');
    }

    public function truckType()
    {
        return $this->belongsTo(TruckType::class, 'truck_type_id');
    }

    public function truckSpecification()
    {
        return $this->belongsTo(TruckSpecification::class, 'truck_specification_id');
    }

    // ✅ NEW: Booking Requests relationship
    public function bookingRequests()
    {
        return $this->hasMany(BookingRequest::class, 'booking_id');
    }

    // ✅ Scopes
    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByVendor($query, $vendorId)
    {
        return $query->where('assigned_vendor_id', $vendorId);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeSearchingVendor($query)
    {
        return $query->where('status', 'searching_vendor');
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', ['pending', 'confirmed', 'in_transit', 'searching_vendor']);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }

    public function scopePaymentPending($query)
    {
        return $query->where('payment_status', 'pending');
    }

    public function scopePaymentCompleted($query)
    {
        return $query->where('payment_status', 'paid');
    }

    // ✅ Accessors
    public function getIsPriceAdjustedAttribute()
    {
        return !is_null($this->adjusted_price);
    }

    public function getFinalPriceAttribute()
    {
        return $this->final_amount ?? $this->adjusted_price ?? $this->estimated_price;
    }

    public function getPaymentLocationAttribute()
    {
        return match($this->payment_method) {
            'pickup' => 'Pay at Pickup Location',
            'drop' => 'Pay at Drop Location',
            default => 'Not specified'
        };
    }

    public function getStatusBadgeAttribute()
    {
        return match($this->status) {
            'pending' => 'warning',
            'searching_vendor' => 'info',
            'confirmed' => 'info',
            'in_transit' => 'primary',
            'completed' => 'success',
            'cancelled' => 'danger',
            default => 'secondary'
        };
    }

    public function getPaymentStatusBadgeAttribute()
    {
        return match($this->payment_status) {
            'pending' => 'warning',
            'paid' => 'success',
            'failed' => 'danger',
            default => 'secondary'
        };
    }

    public function getStatusTextAttribute()
    {
        return match($this->status) {
            'pending' => 'Pending',
            'searching_vendor' => 'Searching for driver...',
            'confirmed' => 'Driver Found',
            'in_transit' => 'On the way',
            'completed' => 'Completed',
            'cancelled' => 'Cancelled',
            default => 'Unknown'
        };
    }

    // ✅ Helper Methods
    public function isPending()
    {
        return $this->status === 'pending';
    }

    public function isSearchingVendor()
    {
        return $this->status === 'searching_vendor';
    }

    public function isConfirmed()
    {
        return $this->status === 'confirmed';
    }

    public function isInTransit()
    {
        return $this->status === 'in_transit';
    }

    public function isCompleted()
    {
        return $this->status === 'completed';
    }

    public function isCancelled()
    {
        return $this->status === 'cancelled';
    }

    public function isPaymentPending()
    {
        return $this->payment_status === 'pending';
    }

    public function isPaymentCompleted()
    {
        return $this->payment_status === 'paid';
    }

    public function markAsConfirmed()
    {
        $this->update([
            'status' => 'confirmed',
            'vendor_accepted_at' => now()
        ]);
    }

    public function markAsStarted()
    {
        $this->update([
            'status' => 'in_transit',
            'trip_started_at' => now()
        ]);
    }

    public function markAsCompleted()
    {
        $this->update([
            'status' => 'completed',
            'trip_completed_at' => now()
        ]);
    }

    public function markAsCancelled($reason = null)
    {
        $this->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
            'cancellation_reason' => $reason
        ]);
    }

    public function markPaymentCompleted()
    {
        $this->update([
            'payment_status' => 'paid',
            'payment_completed_at' => now()
        ]);
    }

    public function getBookingSummary()
    {
        return [
            'booking_id' => $this->booking_id,
            'status' => $this->status,
            'status_text' => $this->status_text,
            'pickup_address' => $this->pickup_address,
            'drop_address' => $this->drop_address,
            'distance_km' => $this->distance_km,
            'material' => $this->material_name . ' (' . $this->material_weight . ' tons)',
            'vehicle' => $this->vehicleModel->model_name ?? 'N/A',
            'vendor' => $this->vendor_name ?? 'Searching...',
            'vendor_contact' => $this->vendor_contact,
            'vehicle_number' => $this->vendor_vehicle_number,
            'pricing' => [
                'estimated' => $this->estimated_price,
                'adjusted' => $this->adjusted_price,
                'final' => $this->final_price,
                'is_adjusted' => $this->is_price_adjusted
            ],
            'payment' => [
                'method' => $this->payment_method,
                'status' => $this->payment_status,
                'location' => $this->payment_location
            ],
            'pickup_datetime' => $this->pickup_datetime,
            'created_at' => $this->created_at
        ];
    }

    // ✅ NEW: Check if booking is trackable
    public function isTrackable()
    {
        return in_array($this->status, ['confirmed', 'in_transit'])
            && !is_null($this->assigned_vendor_id);
    }

    // ✅ NEW: Get distance progress percentage
    public function getDistanceProgressAttribute()
    {
        if (!$this->distance_km || !$this->distance_covered_km) {
            return 0;
        }

        return round(($this->distance_covered_km / $this->distance_km) * 100, 1);
    }
}
