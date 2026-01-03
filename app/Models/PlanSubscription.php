<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class PlanSubscription extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        // Basic Fields
        'user_id',
        'plan_id',
        'plan_name',
        'price_paid',
        'setup_fee',
        'duration_type',
        'selected_features',
        'starts_at',
        'expires_at',
        'status',

        // Razorpay Fields
        'razorpay_subscription_id',
        'razorpay_customer_id',
        'razorpay_plan_id',
        'subscription_status',
        'total_billing_cycles',
        'completed_billing_cycles',
        'remaining_billing_cycles',
        'next_billing_at',
        'cancelled_at',
        'completed_at',
        'paused_at',
        'resumed_at',
        'auto_renew',
        'is_trial',
        'subscription_metadata',
        'cancellation_reason'
    ];

    protected $casts = [
        'price_paid' => 'decimal:2',
        'setup_fee' => 'decimal:2',
        'selected_features' => 'json',
        'subscription_metadata' => 'json',
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
        'next_billing_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'completed_at' => 'datetime',
        'paused_at' => 'datetime',
        'resumed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
        'auto_renew' => 'boolean',
        'is_trial' => 'boolean',
        'total_billing_cycles' => 'integer',
        'completed_billing_cycles' => 'integer',
        'remaining_billing_cycles' => 'integer'
    ];

    protected $appends = [
        'duration_text',
        'is_active',
        'days_remaining',
        'formatted_starts_at',
        'formatted_expires_at',
        'formatted_next_billing_at'
    ];

    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    // ✅ RELATIONSHIPS
    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

    /**
     * Get the user that owns the subscription
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the plan associated with the subscription
     */
    public function plan()
    {
        return $this->belongsTo(Plan::class, 'plan_id');
    }

    /**
     * Get all payments for this subscription
     */
    public function payments()
    {
        return $this->hasMany(Payment::class, 'plan_subscription_id', 'id');
    }

    /**
     * Get only subscription payments
     */
    public function subscriptionPayments()
    {
        return $this->payments()->where('is_subscription_payment', true);
    }

    /**
     * Get setup fee payment
     */
    public function setupFeePayment()
    {
        return $this->payments()
            ->where('payment_type', 'setup_fee')
            ->where('payment_status', 'completed')
            ->first();
    }

    /**
     * Get recurring payments
     */
    public function recurringPayments()
    {
        return $this->payments()
            ->where('payment_type', 'recurring')
            ->where('payment_status', 'completed')
            ->orderBy('created_at', 'desc');
    }

    /**
     * Get latest payment
     */
    public function latestPayment()
    {
        return $this->payments()->latest('created_at')->first();
    }

    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    // ✅ ACCESSORS (Computed Attributes)
    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

    /**
     * Get human-readable duration text
     */
    public function getDurationTextAttribute(): string
    {
        $map = [
            'monthly' => 'Monthly (1 Month)',
            'quarterly' => 'Quarterly (3 Months)',
            'halfyearly' => 'Half-Yearly (6 Months)',
            'half_yearly' => 'Half-Yearly (6 Months)',
            'yearly' => 'Yearly (12 Months)',
            'lifetime' => 'Lifetime'
        ];

        return $map[$this->duration_type] ?? ucfirst(str_replace('_', ' ', $this->duration_type));
    }

    /**
     * Check if subscription is currently active
     */
    public function getIsActiveAttribute(): bool
    {
        // Check subscription_status first (Razorpay status)
        if (!in_array($this->subscription_status, ['authenticated', 'active'])) {
            return false;
        }

        // Check legacy status field
        if ($this->status !== 'active') {
            return false;
        }

        // Lifetime plans (no expiry)
        if ($this->expires_at === null) {
            return true;
        }

        // Check if not expired
        return $this->expires_at > now();
    }

    /**
     * Get days remaining until expiry
     */
    public function getDaysRemainingAttribute()
    {
        // Lifetime or no expiry
        if ($this->expires_at === null) {
            return null;
        }

        // Already expired
        if ($this->expires_at <= now()) {
            return 0;
        }

        // Calculate remaining days
        return now()->diffInDays($this->expires_at, false);
    }

    /**
     * Get formatted start date
     */
    public function getFormattedStartsAtAttribute(): string
    {
        return $this->starts_at ? $this->starts_at->format('M d, Y') : 'N/A';
    }

    /**
     * Get formatted expiry date
     */
    public function getFormattedExpiresAtAttribute(): string
    {
        if ($this->expires_at === null) {
            return 'Never (Lifetime)';
        }
        return $this->expires_at->format('M d, Y');
    }

    /**
     * Get formatted next billing date
     */
    public function getFormattedNextBillingAtAttribute(): string
    {
        if ($this->next_billing_at === null) {
            return 'N/A';
        }
        return $this->next_billing_at->format('M d, Y');
    }

    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    // ✅ QUERY SCOPES
    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

    /**
     * Scope: Get subscriptions by user ID
     */
    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope: Get active subscriptions
     */
    public function scopeActive($query)
    {
        return $query->where('subscription_status', 'active')
                    ->where('status', 'active')
                    ->where(function ($q) {
                        $q->whereNull('expires_at')
                          ->orWhere('expires_at', '>', now());
                    });
    }

    /**
     * Scope: Get Razorpay active subscriptions
     */
    public function scopeRazorpayActive($query)
    {
        return $query->whereIn('subscription_status', ['authenticated', 'active']);
    }

    /**
     * Scope: Get expired subscriptions
     */
    public function scopeExpired($query)
    {
        return $query->where('subscription_status', 'active')
                    ->whereNotNull('expires_at')
                    ->where('expires_at', '<=', now());
    }

    /**
     * Scope: Get cancelled subscriptions
     */
    public function scopeCancelled($query)
    {
        return $query->where('subscription_status', 'cancelled');
    }

    /**
     * Scope: Get paused subscriptions
     */
    public function scopePaused($query)
    {
        return $query->where('subscription_status', 'paused');
    }

    /**
     * Scope: Get completed subscriptions
     */
    public function scopeCompleted($query)
    {
        return $query->where('subscription_status', 'completed');
    }

    /**
     * Scope: Get subscriptions by plan
     */
    public function scopeByPlan($query, $planId)
    {
        return $query->where('plan_id', $planId);
    }

    /**
     * Scope: Get subscriptions by Razorpay subscription ID
     */
    public function scopeByRazorpaySubscriptionId($query, $razorpaySubId)
    {
        return $query->where('razorpay_subscription_id', $razorpaySubId);
    }

    /**
     * Scope: Get subscriptions by duration type
     */
    public function scopeByDurationType($query, $durationType)
    {
        return $query->where('duration_type', $durationType);
    }

    /**
     * Scope: Get recent subscriptions
     */
    public function scopeRecent($query, $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    /**
     * Scope: Get subscriptions expiring soon
     */
    public function scopeExpiringSoon($query, $days = 7)
    {
        return $query->where('subscription_status', 'active')
                    ->whereNotNull('expires_at')
                    ->whereBetween('expires_at', [now(), now()->addDays($days)]);
    }

    /**
     * Scope: Get subscriptions with auto-renew enabled
     */
    public function scopeAutoRenewEnabled($query)
    {
        return $query->where('auto_renew', true);
    }

    /**
     * Scope: Get trial subscriptions
     */
    public function scopeTrial($query)
    {
        return $query->where('is_trial', true);
    }

    /**
     * Scope: Get pending subscriptions (awaiting payment)
     */
    public function scopePending($query)
    {
        return $query->where('subscription_status', 'pending')
                    ->where('status', 'pending');
    }

    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    // ✅ STATUS CHECK METHODS
    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

    /**
     * Check if subscription is active
     */
    public function isActive(): bool
    {
        return $this->is_active;
    }

    /**
     * Check if subscription is Razorpay active
     */
    public function isRazorpayActive(): bool
    {
        return in_array($this->subscription_status, ['authenticated', 'active']);
    }

    /**
     * Check if subscription is expired
     */
    public function isExpired(): bool
    {
        if ($this->expires_at === null) {
            return false;
        }
        return $this->expires_at <= now();
    }

    /**
     * Check if subscription is cancelled
     */
    public function isCancelled(): bool
    {
        return $this->subscription_status === 'cancelled';
    }

    /**
     * Check if subscription is paused
     */
    public function isPaused(): bool
    {
        return $this->subscription_status === 'paused';
    }

    /**
     * Check if subscription is completed
     */
    public function isCompleted(): bool
    {
        return $this->subscription_status === 'completed';
    }

    /**
     * Check if subscription is pending
     */
    public function isPending(): bool
    {
        return $this->subscription_status === 'pending' && $this->status === 'pending';
    }

    /**
     * Check if subscription is lifetime
     */
    public function isLifetime(): bool
    {
        return $this->duration_type === 'lifetime' || $this->expires_at === null;
    }

    /**
     * Check if subscription is trial
     */
    public function isTrial(): bool
    {
        return $this->is_trial === true;
    }

    /**
     * Check if auto-renew is enabled
     */
    public function hasAutoRenew(): bool
    {
        return $this->auto_renew === true;
    }

    /**
     * Check if can be cancelled
     */
    public function canCancel(): bool
    {
        return in_array($this->subscription_status, ['authenticated', 'active', 'pending']);
    }

    /**
     * Check if can be paused
     */
    public function canPause(): bool
    {
        return $this->subscription_status === 'active' && $this->is_trial === false;
    }

    /**
     * Check if can be resumed
     */
    public function canResume(): bool
    {
        return $this->subscription_status === 'paused';
    }

    /**
     * Check if can be renewed
     */
    public function canRenew(): bool
    {
        return $this->isLifetime() === false;
    }

    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    // ✅ ACTION METHODS
    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

    /**
     * Mark subscription as active (after payment)
     */
    public function markActive(): bool
    {
        return $this->update([
            'subscription_status' => 'active',
            'status' => 'active'
        ]);
    }

    /**
     * Mark subscription as authenticated (Razorpay)
     */
    public function markAuthenticated(): bool
    {
        return $this->update([
            'subscription_status' => 'authenticated',
            'status' => 'active'
        ]);
    }

    /**
     * Cancel subscription
     */
    public function cancel(string $reason = null): bool
    {
        return $this->update([
            'subscription_status' => 'cancelled',
            'status' => 'cancelled',
            'cancelled_at' => now(),
            'cancellation_reason' => $reason,
            'auto_renew' => false
        ]);
    }

    /**
     * Pause subscription
     */
    public function pause(): bool
    {
        if (!$this->canPause()) {
            return false;
        }

        return $this->update([
            'subscription_status' => 'paused',
            'paused_at' => now()
        ]);
    }

    /**
     * Resume subscription
     */
    public function resume(): bool
    {
        if (!$this->canResume()) {
            return false;
        }

        return $this->update([
            'subscription_status' => 'active',
            'resumed_at' => now(),
            'paused_at' => null
        ]);
    }

    /**
     * Mark as completed
     */
    public function markCompleted(): bool
    {
        return $this->update([
            'subscription_status' => 'completed',
            'status' => 'completed',
            'completed_at' => now()
        ]);
    }

    /**
     * Mark as failed (payment failed)
     */
    public function markFailed(): bool
    {
        return $this->update([
            'subscription_status' => 'failed',
            'status' => 'failed'
        ]);
    }

    /**
     * Renew subscription for next period
     */
    public function renew(): bool
    {
        if (!$this->canRenew()) {
            return false;
        }

        $daysToAdd = match ($this->duration_type) {
            'monthly' => 30,
            'quarterly' => 90,
            'halfyearly' => 180,
            'yearly' => 365,
            default => 30
        };

        $newExpiryDate = $this->expires_at > now()
            ? $this->expires_at->addDays($daysToAdd)
            : now()->addDays($daysToAdd);

        return $this->update([
            'expires_at' => $newExpiryDate,
            'next_billing_at' => $newExpiryDate,
            'subscription_status' => 'active',
            'status' => 'active'
        ]);
    }

    /**
     * Update billing cycles after payment
     */
    public function updateBillingCycles(): bool
    {
        $completed = $this->completed_billing_cycles + 1;
        $remaining = $this->total_billing_cycles > 0
            ? max(0, $this->total_billing_cycles - $completed)
            : 0;

        return $this->update([
            'completed_billing_cycles' => $completed,
            'remaining_billing_cycles' => $remaining
        ]);
    }

    /**
     * Update next billing date
     */
    public function updateNextBillingDate(Carbon $date): bool
    {
        return $this->update([
            'next_billing_at' => $date
        ]);
    }

    /**
     * Mark as expired
     */
    public function markExpired(): bool
    {
        return $this->update([
            'subscription_status' => 'expired',
            'status' => 'expired'
        ]);
    }

    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    // ✅ INFO METHODS
    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

    /**
     * Get subscription summary
     */
    public function getSummary(): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'plan_id' => $this->plan_id,
            'plan_name' => $this->plan_name,
            'price_paid' => (float) $this->price_paid,
            'setup_fee' => (float) $this->setup_fee,
            'duration_type' => $this->duration_type,
            'duration_text' => $this->duration_text,

            // Razorpay Info
            'razorpay_subscription_id' => $this->razorpay_subscription_id,
            'razorpay_customer_id' => $this->razorpay_customer_id,
            'razorpay_plan_id' => $this->razorpay_plan_id,

            // Status
            'status' => $this->status,
            'subscription_status' => $this->subscription_status,
            'is_active' => $this->is_active,
            'is_cancelled' => $this->isCancelled(),
            'is_paused' => $this->isPaused(),
            'is_trial' => $this->is_trial,
            'is_expired' => $this->isExpired(),

            // Billing
            'total_billing_cycles' => $this->total_billing_cycles,
            'completed_billing_cycles' => $this->completed_billing_cycles,
            'remaining_billing_cycles' => $this->remaining_billing_cycles,
            'auto_renew' => $this->auto_renew,

            // Dates
            'starts_at' => $this->starts_at?->toDateTimeString(),
            'expires_at' => $this->expires_at?->toDateTimeString(),
            'next_billing_at' => $this->next_billing_at?->toDateTimeString(),
            'cancelled_at' => $this->cancelled_at?->toDateTimeString(),

            // Additional
            'days_remaining' => $this->days_remaining,
            'is_lifetime' => $this->isLifetime(),
            'created_at' => $this->created_at->toDateTimeString(),
            'updated_at' => $this->updated_at->toDateTimeString()
        ];
    }

    /**
     * Get payment history
     */
    public function getPaymentHistory(): array
    {
        $payments = $this->payments;

        return [
            'total_payments' => $payments->count(),
            'successful_payments' => $payments->where('payment_status', 'completed')->count(),
            'failed_payments' => $payments->where('payment_status', 'failed')->count(),
            'pending_payments' => $payments->where('payment_status', 'created')->count(),
            'total_amount_paid' => (float) $payments->where('payment_status', 'completed')->sum('amount'),
            'latest_payment' => $this->latestPayment()?->toArray() ?? null,
            'payments' => $payments->toArray()
        ];
    }

    /**
     * Get billing info
     */
    public function getBillingInfo(): array
    {
        return [
            'plan_name' => $this->plan_name,
            'monthly_price' => (float) $this->price_paid,
            'setup_fee' => (float) $this->setup_fee,
            'total_price' => (float) ($this->price_paid + $this->setup_fee),
            'duration_type' => $this->duration_type,
            'billing_cycles' => $this->total_billing_cycles,
            'completed_cycles' => $this->completed_billing_cycles,
            'remaining_cycles' => $this->remaining_billing_cycles,
            'next_billing_date' => $this->next_billing_at?->toDateString(),
            'expires_at' => $this->expires_at?->toDateString(),
            'is_auto_renew' => $this->auto_renew
        ];
    }

    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    // ✅ BOOT METHOD
    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

    protected static function boot()
    {
        parent::boot();

        // Auto-calculate remaining billing cycles on save
        static::saving(function ($subscription) {
            if ($subscription->total_billing_cycles > 0) {
                $subscription->remaining_billing_cycles = max(
                    0,
                    $subscription->total_billing_cycles - $subscription->completed_billing_cycles
                );
            }

            // Auto-mark as expired if past expiry date
            if ($subscription->expires_at &&
                $subscription->expires_at <= now() &&
                $subscription->subscription_status === 'active') {
                $subscription->subscription_status = 'expired';
                $subscription->status = 'expired';
            }
        });
    }
}
