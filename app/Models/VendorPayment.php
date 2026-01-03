<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class VendorPayment extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'vendor_payments';

    protected $fillable = [
        'vendor_id',
        'vendor_plan_id',
        'vendor_plan_subscription_id',
        'razorpay_order_id',
        'razorpay_payment_id',
        'razorpay_subscription_id',  // ✅ ADD KIYA
        'razorpay_signature',
        'amount',
        'currency',
        'amount_paid',
        'payment_status',
        'order_status',
        'payment_method',
        'card_id',
        'bank',
        'wallet',
        'vpa',
        'email',
        'contact',
        'razorpay_response',
        'error_code',
        'error_description',
        'paid_at',
        'payment_completed_at',  // ✅ ADD KIYA
        'signature_verified',  // ✅ ADD KIYA
        'payment_failed_at',  // ✅ ADD KIYA
        'receipt_number'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'amount_paid' => 'decimal:2',
        'razorpay_response' => 'array',
        'paid_at' => 'datetime',
        'payment_completed_at' => 'datetime',  // ✅ ADD KIYA
        'signature_verified' => 'boolean',  // ✅ ADD KIYA
        'payment_failed_at' => 'datetime',  // ✅ ADD KIYA
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    // ✅ RELATIONSHIPS
    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }

    public function vendorPlan()
    {
        return $this->belongsTo(VendorPlan::class);
    }

    public function vendorPlanSubscription()
    {
        return $this->belongsTo(VendorPlanSubscription::class, 'vendor_plan_subscription_id');
    }

    public function subscription()
    {
        return $this->vendorPlanSubscription();
    }

    // ✅ SCOPES
    public function scopePending($query)
    {
        return $query->where('payment_status', 'pending');
    }

    public function scopeCreated($query)
    {
        return $query->where('payment_status', 'created');
    }

    public function scopeSuccess($query)
    {
        return $query->where('payment_status', 'success');
    }

    public function scopePaid($query)
    {
        return $query->where('payment_status', 'paid');
    }

    public function scopeFailed($query)
    {
        return $query->where('payment_status', 'failed');
    }

    public function scopeRefunded($query)
    {
        return $query->where('payment_status', 'refunded');
    }

    public function scopeSuccessful($query)
    {
        return $query->whereIn('payment_status', ['success', 'paid']);
    }

    public function scopeByVendor($query, $vendorId)
    {
        return $query->where('vendor_id', $vendorId);
    }

    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    public function scopeVerified($query)
    {
        return $query->where('signature_verified', true);
    }

    // ✅ ACCESSORS
    public function getFormattedAmountAttribute()
    {
        return '₹' . number_format($this->amount, 2);
    }

    public function getFormattedAmountPaidAttribute()
    {
        return '₹' . number_format($this->amount_paid ?? 0, 2);
    }

    public function getStatusBadgeAttribute()
    {
        $badges = [
            'success' => '<span class="badge bg-success"><i class="fas fa-check-circle me-1"></i>Success</span>',
            'paid' => '<span class="badge bg-success"><i class="fas fa-check-circle me-1"></i>Paid</span>',
            'created' => '<span class="badge bg-info"><i class="fas fa-hourglass me-1"></i>Created</span>',
            'pending' => '<span class="badge bg-warning text-dark"><i class="fas fa-clock me-1"></i>Pending</span>',
            'failed' => '<span class="badge bg-danger"><i class="fas fa-times-circle me-1"></i>Failed</span>',
            'refunded' => '<span class="badge bg-secondary"><i class="fas fa-undo me-1"></i>Refunded</span>',
        ];

        return $badges[strtolower($this->payment_status)] ?? '<span class="badge bg-secondary">Unknown</span>';
    }

    public function getPaymentMethodIconAttribute()
    {
        $icons = [
            'card' => '<i class="fas fa-credit-card text-primary"></i>',
            'netbanking' => '<i class="fas fa-university text-info"></i>',
            'wallet' => '<i class="fas fa-wallet text-success"></i>',
            'upi' => '<i class="fas fa-mobile-alt text-warning"></i>',
        ];

        return $icons[strtolower($this->payment_method ?? '')] ?? '<i class="fas fa-money-bill text-secondary"></i>';
    }

    public function getPaymentMethodNameAttribute()
    {
        $names = [
            'card' => 'Credit/Debit Card',
            'netbanking' => 'Net Banking',
            'wallet' => 'Wallet',
            'upi' => 'UPI',
        ];

        return $names[strtolower($this->payment_method ?? '')] ?? ucfirst($this->payment_method ?? 'Unknown');
    }

    // ✅ HELPER METHODS
    public function isSuccessful(): bool
    {
        return in_array(strtolower($this->payment_status), ['success', 'paid']);
    }

    public function isPaid(): bool
    {
        return $this->isSuccessful();
    }

    public function isPending(): bool
    {
        return strtolower($this->payment_status) === 'pending';
    }

    public function isCreated(): bool
    {
        return strtolower($this->payment_status) === 'created';
    }

    public function isFailed(): bool
    {
        return strtolower($this->payment_status) === 'failed';
    }

    public function isRefunded(): bool
    {
        return strtolower($this->payment_status) === 'refunded';
    }

    public function isVerified(): bool
    {
        return $this->signature_verified === true;
    }

    public function getSummaryAttribute()
    {
        return [
            'id' => $this->id,
            'vendor_id' => $this->vendor_id,
            'plan_name' => $this->vendorPlan->plan_name ?? 'N/A',
            'amount' => $this->amount,
            'formatted_amount' => $this->formatted_amount,
            'payment_status' => $this->payment_status,
            'payment_method' => $this->payment_method,
            'razorpay_order_id' => $this->razorpay_order_id,
            'razorpay_payment_id' => $this->razorpay_payment_id,
            'razorpay_subscription_id' => $this->razorpay_subscription_id,
            'signature_verified' => $this->signature_verified,
            'paid_at' => $this->paid_at ? $this->paid_at->format('Y-m-d H:i:s') : null,
            'created_at' => $this->created_at->format('Y-m-d H:i:s')
        ];
    }

    // ✅ MUTATORS
    public function markAsSuccess($razorpayPaymentId = null, $razorpaySignature = null)
    {
        $this->update([
            'payment_status' => 'success',
            'razorpay_payment_id' => $razorpayPaymentId ?? $this->razorpay_payment_id,
            'razorpay_signature' => $razorpaySignature ?? $this->razorpay_signature,
            'payment_completed_at' => now(),  // ✅ CORRECT
            'signature_verified' => true,  // ✅ CORRECT
            'paid_at' => now()
        ]);
    }

    public function markAsFailed($errorCode = null, $errorDescription = null)
    {
        $this->update([
            'payment_status' => 'failed',
            'error_code' => $errorCode,
            'error_description' => $errorDescription,
            'payment_failed_at' => now()  // ✅ CORRECT
        ]);
    }

    // ✅ BOOT
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (!$model->receipt_number) {
                $model->receipt_number = 'RCP-' . date('YmdHis') . '-' . strtoupper(uniqid());
            }
        });
    }
}
