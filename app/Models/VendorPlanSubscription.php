<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class VendorPlanSubscription extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'vendor_plan_subscriptions';

    protected $fillable = [
        'vendor_id',
        'vendor_plan_id',
        'vendor_payment_id',
        'razorpay_subscription_id',  // ✅ ADD KIYA
        'razorpay_plan_id',  // ✅ ADD KIYA
        'razorpay_customer_id',  // ✅ ADD KIYA
        'plan_name',
        'price_paid',
        'setup_fee',  // ✅ ADD KIYA
        'duration_type',
        'total_billing_cycles',  // ✅ ADD KIYA
        'completed_billing_cycles',  // ✅ ADD KIYA
        'starts_at',
        'expires_at',
        'next_billing_at',  // ✅ ADD KIYA
        'cancelled_at',  // ✅ ADD KIYA
        'status',
        'subscription_status',  // ✅ ADD KIYA
        'is_paid',
        'auto_renew',  // ✅ ADD KIYA
        'plan_features',
        'subscription_metadata'  // ✅ ADD KIYA
    ];

    protected $casts = [
        'price_paid' => 'decimal:2',
        'setup_fee' => 'decimal:2',  // ✅ ADD KIYA
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
        'next_billing_at' => 'datetime',  // ✅ ADD KIYA
        'cancelled_at' => 'datetime',  // ✅ ADD KIYA
        'plan_features' => 'array',
        'subscription_metadata' => 'array',  // ✅ ADD KIYA
        'is_paid' => 'boolean',
        'auto_renew' => 'boolean'  // ✅ ADD KIYA
    ];

    // ✅ RELATIONSHIPS

    public function vendor()
    {
        return $this->belongsTo(Vendor::class, 'vendor_id');
    }

    public function vendorPlan()
    {
        return $this->belongsTo(VendorPlan::class, 'vendor_plan_id');
    }

    public function vendorPayment()
    {
        return $this->belongsTo(VendorPayment::class, 'vendor_payment_id');
    }

    public function payments()
    {
        return $this->hasMany(VendorPayment::class, 'vendor_plan_subscription_id');
    }

    // ✅ SCOPES

    public function scopePending($query)
    {
        return $query->where('subscription_status', 'pending');
    }

    public function scopeAuthenticated($query)
    {
        return $query->where('subscription_status', 'authenticated');
    }

    public function scopeActive($query)
    {
        return $query->where('subscription_status', 'active')
            ->where('expires_at', '>', Carbon::now());
    }

    public function scopePaused($query)
    {
        return $query->where('subscription_status', 'paused');
    }

    public function scopeCancelled($query)
    {
        return $query->where('subscription_status', 'cancelled');
    }

    public function scopeFailed($query)
    {
        return $query->where('subscription_status', 'failed');
    }

    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<=', Carbon::now());
    }

    public function scopeExpiringSoon($query, $days = 7)
    {
        return $query->where('subscription_status', 'active')
            ->whereBetween('expires_at', [Carbon::now(), Carbon::now()->addDays($days)]);
    }

    public function scopeValid($query)
    {
        return $query->where('subscription_status', 'active')
            ->where('expires_at', '>', Carbon::now());
    }

    public function scopeByVendor($query, $vendorId)
    {
        return $query->where('vendor_id', $vendorId);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('subscription_status', $status);
    }

    // ✅ ACCESSORS (Computed Attributes)

    public function getIsActiveAttribute()
    {
        return $this->subscription_status === 'active' && $this->expires_at > Carbon::now();
    }

    public function getIsExpiredAttribute()
    {
        return $this->expires_at <= Carbon::now();
    }

    public function getDaysRemainingAttribute()
    {
        if (!$this->expires_at || $this->expires_at <= Carbon::now()) {
            return 0;
        }
        return Carbon::now()->diffInDays($this->expires_at);
    }

    public function getFormattedPriceAttribute()
    {
        return '₹' . number_format($this->price_paid, 2);
    }

    public function getFormattedSetupFeeAttribute()
    {
        return '₹' . number_format($this->setup_fee ?? 0, 2);
    }

    public function getFormattedStartsAtAttribute()
    {
        return $this->starts_at ? $this->starts_at->format('d M Y') : null;
    }

    public function getFormattedExpiresAtAttribute()
    {
        return $this->expires_at ? $this->expires_at->format('d M Y') : null;
    }

    public function getSubscriptionStatusBadgeAttribute()
    {
        $badges = [
            'pending' => '<span class="badge bg-info">Pending</span>',
            'authenticated' => '<span class="badge bg-warning text-dark">Authenticated</span>',
            'active' => '<span class="badge bg-success">Active</span>',
            'paused' => '<span class="badge bg-secondary">Paused</span>',
            'cancelled' => '<span class="badge bg-danger">Cancelled</span>',
            'failed' => '<span class="badge bg-dark">Failed</span>',
        ];

        return $badges[strtolower($this->subscription_status)] ?? '<span class="badge bg-secondary">Unknown</span>';
    }

    // ✅ HELPER METHODS

    public function isExpiringSoon($days = 7): bool
    {
        if (!$this->expires_at || $this->expires_at <= Carbon::now()) {
            return false;
        }
        return $this->expires_at <= Carbon::now()->addDays($days);
    }

    public function isActive(): bool
    {
        return $this->subscription_status === 'active' && $this->expires_at > Carbon::now();
    }

    public function isPending(): bool
    {
        return $this->subscription_status === 'pending';
    }

    public function isAuthenticated(): bool
    {
        return $this->subscription_status === 'authenticated';
    }

    public function isCancelled(): bool
    {
        return $this->subscription_status === 'cancelled';
    }

    public function isFailed(): bool
    {
        return $this->subscription_status === 'failed';
    }

    public function expire()
    {
        return $this->update([
            'status' => 'expired',
            'subscription_status' => 'cancelled',
            'expires_at' => Carbon::now(),
            'cancelled_at' => Carbon::now()
        ]);
    }

    public function renew($newExpiryDate)
    {
        return $this->update([
            'status' => 'active',
            'subscription_status' => 'active',
            'expires_at' => $newExpiryDate,
            'next_billing_at' => $newExpiryDate
        ]);
    }

    public function cancel()
    {
        return $this->update([
            'status' => 'cancelled',
            'subscription_status' => 'cancelled',
            'cancelled_at' => Carbon::now()
        ]);
    }

    public function pause()
    {
        return $this->update([
            'subscription_status' => 'paused'
        ]);
    }

    public function resume()
    {
        return $this->update([
            'subscription_status' => 'active'
        ]);
    }

    public function getStatusColor(): string
    {
        return match($this->subscription_status) {
            'active' => 'green',
            'pending' => 'blue',
            'authenticated' => 'orange',
            'paused' => 'gray',
            'cancelled' => 'red',
            'failed' => 'darkred',
            default => 'gray'
        };
    }

    public function getStatusLabel(): string
    {
        return match($this->subscription_status) {
            'pending' => 'Pending',
            'authenticated' => 'Authenticated',
            'active' => 'Active',
            'paused' => 'Paused',
            'cancelled' => 'Cancelled',
            'failed' => 'Failed',
            default => 'Unknown'
        };
    }

    // ✅ BOOT

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (!$model->subscription_status) {
                $model->subscription_status = 'pending';
            }
            if (!$model->status) {
                $model->status = 'active';
            }
        });
    }
}
