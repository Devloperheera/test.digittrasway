<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class Payment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        // User & Plan Info
        'user_id',
        'plan_id',
        'plan_subscription_id',
        'payment_id',
        'transaction_id',
        'order_id',
        'receipt_number',

        // Amount Info
        'amount',
        'amount_paid',
        'tax_amount',
        'discount_amount',
        'total_amount',
        'currency',

        // Payment Gateway Info
        'payment_gateway',
        'payment_method',
        'payment_method_type',
        'card_id',
        'card_type',
        'card_network',
        'card_last4',
        'bank',
        'wallet',
        'vpa',

        // Status
        'payment_status',
        'order_status',
        'payment_initiated_at',
        'payment_completed_at',
        'payment_failed_at',

        // Customer Info
        'email',
        'contact',
        'customer_name',

        // Error Info
        'error_code',
        'error_description',
        'error_source',
        'error_step',
        'error_reason',

        // Verification
        'razorpay_signature',
        'signature_verified',

        // Request Info
        'ip_address',
        'user_agent',

        // Metadata
        'metadata',
        'razorpay_order_response',
        'razorpay_payment_response',

        // Refund Info
        'refund_id',
        'refund_amount',
        'refund_date',
        'refund_status',
        'refund_reason',
        'description',
        'notes',

        // Subscription Fields
        'razorpay_subscription_id',
        'razorpay_customer_id',
        'razorpay_invoice_id',
        'payment_type',
        'is_subscription_payment',
        'billing_period_start',
        'billing_period_end',
        'billing_cycle_number'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'amount_paid' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'refund_amount' => 'decimal:2',
        'metadata' => 'json',
        'notes' => 'json',
        'razorpay_order_response' => 'json',
        'razorpay_payment_response' => 'json',
        'signature_verified' => 'boolean',
        'is_subscription_payment' => 'boolean',
        'payment_initiated_at' => 'datetime',
        'payment_completed_at' => 'datetime',
        'payment_failed_at' => 'datetime',
        'refund_date' => 'datetime',
        'billing_period_start' => 'datetime',
        'billing_period_end' => 'datetime',
        'billing_cycle_number' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime'
    ];

    protected $appends = [
        'formatted_amount',
        'payment_method_display',
        'status_display',
        'payment_type_display',
        'status_badge'
    ];

    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    // ✅ RELATIONSHIPS
    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

    /**
     * Get the user that owns the payment
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the plan associated with the payment
     */
    public function plan()
    {
        return $this->belongsTo(Plan::class, 'plan_id');
    }

    /**
     * Get the subscription associated with the payment
     */
    public function planSubscription()
    {
        return $this->belongsTo(PlanSubscription::class, 'plan_subscription_id');
    }

    /**
     * Alias for planSubscription
     */
    public function subscription()
    {
        return $this->planSubscription();
    }

    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    // ✅ QUERY SCOPES
    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

    /**
     * Scope: Get successful payments
     */
    public function scopeSuccessful($query)
    {
        return $query->whereIn('payment_status', ['captured', 'authorized']);
    }

    /**
     * Scope: Get completed payments
     */
    public function scopeCompleted($query)
    {
        return $query->where('payment_status', 'completed');
    }

    /**
     * Scope: Get failed payments
     */
    public function scopeFailed($query)
    {
        return $query->where('payment_status', 'failed');
    }

    /**
     * Scope: Get pending payments
     */
    public function scopePending($query)
    {
        return $query->whereIn('payment_status', ['pending', 'created', 'processing']);
    }

    /**
     * Scope: Get refunded payments
     */
    public function scopeRefunded($query)
    {
        return $query->whereIn('payment_status', ['refunded', 'partial_refund']);
    }

    /**
     * Scope: Get payments by user
     */
    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope: Get recent payments
     */
    public function scopeRecent($query, $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    /**
     * Scope: Get subscription payments
     */
    public function scopeSubscriptionPayments($query)
    {
        return $query->where('is_subscription_payment', true);
    }

    /**
     * Scope: Get non-subscription payments
     */
    public function scopeNonSubscriptionPayments($query)
    {
        return $query->where('is_subscription_payment', false);
    }

    /**
     * Scope: Get setup fee payments
     */
    public function scopeSetupFee($query)
    {
        return $query->where('payment_type', 'setup_fee');
    }

    /**
     * Scope: Get recurring payments
     */
    public function scopeRecurring($query)
    {
        return $query->where('payment_type', 'recurring');
    }

    /**
     * Scope: Get payments by subscription
     */
    public function scopeBySubscription($query, $subscriptionId)
    {
        return $query->where('plan_subscription_id', $subscriptionId);
    }

    /**
     * Scope: Get payments by Razorpay subscription ID
     */
    public function scopeByRazorpaySubscriptionId($query, $razorpaySubId)
    {
        return $query->where('razorpay_subscription_id', $razorpaySubId);
    }

    /**
     * Scope: Get payments by payment type
     */
    public function scopeByPaymentType($query, $type)
    {
        return $query->where('payment_type', $type);
    }

    /**
     * Scope: Get payments by payment method
     */
    public function scopeByPaymentMethod($query, $method)
    {
        return $query->where('payment_method', $method);
    }

    /**
     * Scope: Get payments by date range
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    /**
     * Scope: Get payments by amount range
     */
    public function scopeAmountRange($query, $minAmount, $maxAmount)
    {
        return $query->whereBetween('amount', [$minAmount, $maxAmount]);
    }

    /**
     * Scope: Get payments by gateway
     */
    public function scopeByGateway($query, $gateway)
    {
        return $query->where('payment_gateway', $gateway);
    }

    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    // ✅ ACCESSORS (Computed Attributes)
    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

    /**
     * Get formatted amount
     */
    public function getFormattedAmountAttribute(): string
    {
        $amount = $this->total_amount ?? $this->amount ?? 0;
        return '₹' . number_format($amount, 2);
    }

    /**
     * Get payment method display
     */
    public function getPaymentMethodDisplayAttribute(): string
    {
        if ($this->payment_method === 'card') {
            $cardInfo = strtoupper($this->card_network ?? 'CARD');
            if ($this->card_last4) {
                $cardInfo .= ' ****' . $this->card_last4;
            }
            return $cardInfo;
        } elseif ($this->payment_method === 'upi') {
            return 'UPI' . ($this->vpa ? ' (' . $this->vpa . ')' : '');
        } elseif ($this->payment_method === 'wallet') {
            return ucfirst($this->wallet ?? 'Wallet');
        } elseif ($this->payment_method === 'netbanking') {
            return ucfirst($this->bank ?? 'Net Banking');
        }

        return ucfirst($this->payment_method ?? 'Unknown');
    }

    /**
     * Get status display
     */
    public function getStatusDisplayAttribute(): string
    {
        return match ($this->payment_status) {
            'captured' => 'Captured',
            'authorized' => 'Authorized',
            'completed' => 'Completed',
            'failed' => 'Failed',
            'pending' => 'Pending',
            'processing' => 'Processing',
            'created' => 'Created',
            'refunded' => 'Refunded',
            'partial_refund' => 'Partially Refunded',
            'cancelled' => 'Cancelled',
            default => ucfirst($this->payment_status ?? 'Unknown')
        };
    }

    /**
     * Get payment type display
     */
    public function getPaymentTypeDisplayAttribute(): string
    {
        return match ($this->payment_type) {
            'setup_fee' => 'Setup Fee (₹1)',
            'recurring' => 'Recurring Payment',
            'one_time' => 'One-time Payment',
            'addon' => 'Add-on Payment',
            'refund' => 'Refund',
            default => 'Payment'
        };
    }

    /**
     * Get status badge
     */
    public function getStatusBadgeAttribute(): string
    {
        return match ($this->payment_status) {
            'captured', 'authorized', 'completed' => 'success',
            'failed', 'cancelled' => 'danger',
            'pending', 'processing', 'created' => 'warning',
            'refunded', 'partial_refund' => 'info',
            default => 'secondary'
        };
    }

    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    // ✅ STATUS CHECK METHODS
    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

    /**
     * Check if payment is successful
     */
    public function isSuccessful(): bool
    {
        return in_array($this->payment_status, ['captured', 'authorized', 'completed']);
    }

    /**
     * Check if payment is captured
     */
    public function isCaptured(): bool
    {
        return $this->payment_status === 'captured';
    }

    /**
     * Check if payment is authorized
     */
    public function isAuthorized(): bool
    {
        return $this->payment_status === 'authorized';
    }

    /**
     * Check if payment is failed
     */
    public function isFailed(): bool
    {
        return $this->payment_status === 'failed';
    }

    /**
     * Check if payment is pending
     */
    public function isPending(): bool
    {
        return in_array($this->payment_status, ['pending', 'created', 'processing']);
    }

    /**
     * Check if payment is refunded
     */
    public function isRefunded(): bool
    {
        return in_array($this->payment_status, ['refunded', 'partial_refund']);
    }

    /**
     * Check if payment is subscription payment
     */
    public function isSubscriptionPayment(): bool
    {
        return $this->is_subscription_payment === true;
    }

    /**
     * Check if payment is setup fee
     */
    public function isSetupFee(): bool
    {
        return $this->payment_type === 'setup_fee';
    }

    /**
     * Check if payment is recurring
     */
    public function isRecurring(): bool
    {
        return $this->payment_type === 'recurring';
    }

    /**
     * Check if signature is verified
     */
    public function isSignatureVerified(): bool
    {
        return $this->signature_verified === true;
    }

    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    // ✅ ACTION METHODS
    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

    /**
     * Mark payment as completed
     */
    public function markAsCompleted(): bool
    {
        return $this->update([
            'payment_status' => 'completed',
            'payment_completed_at' => now()
        ]);
    }

    /**
     * Mark payment as captured
     */
    public function markAsCaptured(): bool
    {
        return $this->update([
            'payment_status' => 'captured',
            'payment_completed_at' => now()
        ]);
    }

    /**
     * Mark payment as authorized
     */
    public function markAsAuthorized(): bool
    {
        return $this->update([
            'payment_status' => 'authorized',
            'payment_completed_at' => now()
        ]);
    }

    /**
     * Mark payment as failed
     */
    public function markAsFailed(string $errorCode = null, string $errorDescription = null): bool
    {
        return $this->update([
            'payment_status' => 'failed',
            'payment_failed_at' => now(),
            'error_code' => $errorCode,
            'error_description' => $errorDescription
        ]);
    }

    /**
     * Mark signature as verified
     */
    public function markSignatureVerified(): bool
    {
        return $this->update([
            'signature_verified' => true
        ]);
    }

    /**
     * Process refund
     */
    public function processRefund(float $amount, string $refundId, string $reason = null): bool
    {
        $status = $amount >= $this->amount ? 'refunded' : 'partial_refund';

        return $this->update([
            'payment_status' => $status,
            'refund_id' => $refundId,
            'refund_amount' => $amount,
            'refund_date' => now(),
            'refund_status' => 'processed',
            'refund_reason' => $reason
        ]);
    }

    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    // ✅ INFO METHODS
    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

    /**
     * Get payment summary
     */
    public function getSummary(): array
    {
        return [
            'id' => $this->id,
            'payment_id' => $this->payment_id,
            'order_id' => $this->order_id,
            'receipt_number' => $this->receipt_number,
            'transaction_id' => $this->transaction_id,

            // User Info
            'user_id' => $this->user_id,
            'customer_name' => $this->customer_name,
            'email' => $this->email,
            'contact' => $this->contact,

            // Subscription Info
            'plan_subscription_id' => $this->plan_subscription_id,
            'razorpay_subscription_id' => $this->razorpay_subscription_id,
            'razorpay_customer_id' => $this->razorpay_customer_id,
            'is_subscription_payment' => $this->is_subscription_payment,
            'payment_type' => $this->payment_type,
            'payment_type_display' => $this->payment_type_display,

            // Amount Info
            'amount' => (float) $this->amount,
            'amount_paid' => (float) $this->amount_paid,
            'tax_amount' => (float) $this->tax_amount,
            'discount_amount' => (float) $this->discount_amount,
            'total_amount' => (float) $this->total_amount,
            'formatted_amount' => $this->formatted_amount,
            'currency' => $this->currency,

            // Payment Gateway
            'payment_gateway' => $this->payment_gateway,
            'payment_method' => $this->payment_method,
            'payment_method_display' => $this->payment_method_display,

            // Status
            'payment_status' => $this->payment_status,
            'order_status' => $this->order_status,
            'status_display' => $this->status_display,
            'status_badge' => $this->status_badge,
            'is_successful' => $this->isSuccessful(),
            'signature_verified' => $this->signature_verified,

            // Billing
            'billing_cycle_number' => $this->billing_cycle_number,
            'billing_period_start' => $this->billing_period_start?->format('Y-m-d'),
            'billing_period_end' => $this->billing_period_end?->format('Y-m-d'),

            // Dates
            'payment_initiated_at' => $this->payment_initiated_at?->format('Y-m-d H:i:s'),
            'payment_completed_at' => $this->payment_completed_at?->format('Y-m-d H:i:s'),
            'payment_failed_at' => $this->payment_failed_at?->format('Y-m-d H:i:s'),
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),

            // Refund
            'refund_status' => $this->refund_status,
            'refund_amount' => (float) $this->refund_amount,
            'refund_date' => $this->refund_date?->format('Y-m-d H:i:s'),
            'refund_reason' => $this->refund_reason
        ];
    }

    /**
     * Get error details
     */
    public function getErrorDetails(): ?array
    {
        if (!$this->error_code) {
            return null;
        }

        return [
            'error_code' => $this->error_code,
            'error_description' => $this->error_description,
            'error_source' => $this->error_source,
            'error_step' => $this->error_step,
            'error_reason' => $this->error_reason
        ];
    }

    /**
     * Get card details
     */
    public function getCardDetails(): ?array
    {
        if ($this->payment_method !== 'card') {
            return null;
        }

        return [
            'card_id' => $this->card_id,
            'card_type' => $this->card_type,
            'card_network' => $this->card_network,
            'card_last4' => $this->card_last4
        ];
    }

    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    // ✅ STATIC HELPER METHODS
    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

    /**
     * Generate unique payment ID
     */
    public static function generatePaymentId(): string
    {
        return 'PAY_' . strtoupper(uniqid()) . '_' . time();
    }

    /**
     * Generate unique receipt number
     */
    public static function generateReceiptNumber(): string
    {
        return 'RCP_' . date('Ymd') . '_' . strtoupper(substr(uniqid(), -8));
    }

    /**
     * Generate unique transaction ID
     */
    public static function generateTransactionId(): string
    {
        return 'TXN_' . strtoupper(uniqid()) . '_' . time();
    }

    /**
     * Get total revenue
     */
    public static function getTotalRevenue(?int $userId = null): float
    {
        $query = self::successful();

        if ($userId) {
            $query->where('user_id', $userId);
        }

        return (float) ($query->sum('total_amount') ?? 0.0);
    }

    /**
     * Get subscription revenue
     */
    public static function getSubscriptionRevenue(?int $userId = null): float
    {
        $query = self::successful()->subscriptionPayments();

        if ($userId) {
            $query->where('user_id', $userId);
        }

        return (float) ($query->sum('total_amount') ?? 0.0);
    }

    /**
     * Get success rate
     */
    public static function getSuccessRate(?int $userId = null): float
    {
        $query = self::query();

        if ($userId) {
            $query->where('user_id', $userId);
        }

        $total = $query->count();

        if ($total === 0) {
            return 0.0;
        }

        $successful = $query->successful()->count();

        return round(($successful / $total) * 100, 2);
    }

    /**
     * Get total payments count
     */
    public static function getTotalPaymentCount(?int $userId = null): int
    {
        $query = self::query();

        if ($userId) {
            $query->where('user_id', $userId);
        }

        return $query->count();
    }

    /**
     * Get failed payments count
     */
    public static function getFailedPaymentCount(?int $userId = null): int
    {
        $query = self::failed();

        if ($userId) {
            $query->where('user_id', $userId);
        }

        return $query->count();
    }

    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    // ✅ BOOT METHOD
    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

    protected static function boot()
    {
        parent::boot();

        // Auto-generate receipt number on create
        static::creating(function ($payment) {
            if (empty($payment->receipt_number)) {
                $payment->receipt_number = self::generateReceiptNumber();
            }

            if (empty($payment->payment_gateway)) {
                $payment->payment_gateway = 'razorpay';
            }

            if (empty($payment->currency)) {
                $payment->currency = 'INR';
            }

            if (empty($payment->payment_initiated_at)) {
                $payment->payment_initiated_at = now();
            }

            // Set total_amount if not set
            if (empty($payment->total_amount) && !empty($payment->amount)) {
                $payment->total_amount = $payment->amount;
            }
        });

        // Auto-set completion/failure times on status update
        static::updating(function ($payment) {
            if ($payment->isDirty('payment_status')) {
                if (in_array($payment->payment_status, ['captured', 'authorized', 'completed']) &&
                    empty($payment->payment_completed_at)) {
                    $payment->payment_completed_at = now();
                } elseif ($payment->payment_status === 'failed' && empty($payment->payment_failed_at)) {
                    $payment->payment_failed_at = now();
                }
            }
        });
    }
}
