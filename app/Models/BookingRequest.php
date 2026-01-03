<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BookingRequest extends Model
{
    use HasFactory;

    // ✅ Enable timestamps (Laravel auto-manages created_at & updated_at)
    public $timestamps = true;

    protected $fillable = [
        'booking_id',
        'vendor_id',
        'status',
        'sent_at',
        'expires_at',
        'responded_at',
        'sequence_number',

        // ✅ Booking details columns
        'pickup_datetime',
        'pickup_address',
        'drop_address',
        'distance_km',
        'final_amount',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'expires_at' => 'datetime',
        'responded_at' => 'datetime',
        'pickup_datetime' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'sequence_number' => 'integer',
        'distance_km' => 'decimal:2',
        'final_amount' => 'decimal:2',
    ];

    // Relationships
    public function booking()
    {
        return $this->belongsTo(TruckBooking::class, 'booking_id');
    }

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }

    // Helper methods
    public function isExpired()
    {
        return $this->expires_at && now()->greaterThan($this->expires_at);
    }

    public function isPending()
    {
        return $this->status === 'pending' && !$this->isExpired();
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeAccepted($query)
    {
        return $query->where('status', 'accepted');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    public function scopeExpired($query)
    {
        return $query->where('status', 'expired');
    }

    // Accessor
    public function getStatusBadgeAttribute()
    {
        $badges = [
            'pending' => '<span class="badge bg-warning text-dark"><i class="fas fa-clock me-1"></i>Pending</span>',
            'accepted' => '<span class="badge bg-success"><i class="fas fa-check-circle me-1"></i>Accepted</span>',
            'rejected' => '<span class="badge bg-danger"><i class="fas fa-times-circle me-1"></i>Rejected</span>',
            'expired' => '<span class="badge bg-secondary"><i class="fas fa-hourglass-end me-1"></i>Expired</span>',
        ];
        return $badges[$this->status] ?? '<span class="badge bg-secondary">Unknown</span>';
    }
}
