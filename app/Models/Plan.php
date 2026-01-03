<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class Plan extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'price',              // Recurring subscription price (₹249)
        'setup_fee',          // ✅ One-time setup fee (₹1)
        'duration_type',
        'duration_days',
        'features',
        'is_popular',
        'is_active',
        'button_text',
        'button_color',
        'contact_info',
        'sort_order'
    ];

    protected $casts = [
        'features' => 'json',
        'price' => 'decimal:2',
        'setup_fee' => 'decimal:2',
        'duration_days' => 'integer',
        'is_popular' => 'boolean',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    protected $attributes = [
        'is_active' => true,
        'is_popular' => false,
        'sort_order' => 1,
        'setup_fee' => 1.00,
        'features' => '[]'
    ];

    protected $appends = [
        'duration_text',
        'duration_full_text',
        'subscribers_count',
        'total_subscribers',
        'formatted_price',
        'formatted_setup_fee',
        'features_list',
        'price_in_paise',
        'setup_fee_in_paise',
        'first_payment',
        'first_payment_in_paise',
        'duration_in_months',
        'price_per_month',
        'pricing_details'
    ];

    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    // ✅ RELATIONSHIPS
    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

    /**
     * Get all subscriptions for this plan
     */
    public function subscriptions()
    {
        return $this->hasMany(PlanSubscription::class, 'plan_id');
    }

    /**
     * Get only active subscriptions for this plan
     */
    public function activeSubscriptions()
    {
        return $this->hasMany(PlanSubscription::class, 'plan_id')
            ->where('subscription_status', 'active')
            ->where('status', 'active')
            ->where(function ($q) {
                $q->whereNull('expires_at')
                  ->orWhere('expires_at', '>', now());
            });
    }

    /**
     * Get payments for this plan
     */
    public function payments()
    {
        return $this->hasMany(Payment::class, 'plan_id');
    }

    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    // ✅ SCOPES
    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

    /**
     * Scope: Get only active plans
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Get popular plans
     */
    public function scopePopular($query)
    {
        return $query->where('is_popular', true);
    }

    /**
     * Scope: Get plans ordered by sort_order
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order', 'asc')->orderBy('id', 'asc');
    }

    /**
     * Scope: Get plans by duration type
     */
    public function scopeByDurationType($query, $durationType)
    {
        return $query->where('duration_type', $durationType);
    }

    /**
     * Scope: Get plans by price range
     */
    public function scopePriceRange($query, $minPrice, $maxPrice)
    {
        return $query->whereBetween('price', [$minPrice, $maxPrice]);
    }

    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    // ✅ ACCESSORS (Computed Attributes)
    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

    /**
     * Get human-readable duration type
     */
    public function getDurationTextAttribute(): string
    {
        $durations = [
            'daily' => 'Daily',
            'weekly' => 'Weekly',
            'monthly' => 'Monthly',
            'quarterly' => 'Quarterly',
            'halfyearly' => 'Half Yearly',
            'half_yearly' => 'Half Yearly',
            'yearly' => 'Yearly',
            'lifetime' => 'Lifetime'
        ];

        return $durations[$this->duration_type] ?? ucfirst(str_replace('_', ' ', $this->duration_type));
    }

    /**
     * Get full duration text with days
     */
    public function getDurationFullTextAttribute(): string
    {
        $text = $this->duration_text;
        if ($this->duration_days && $this->duration_days > 0) {
            $text .= " ({$this->duration_days} days)";
        }
        return $text;
    }

    /**
     * Get count of active subscribers
     */
    public function getSubscribersCountAttribute(): int
    {
        return $this->activeSubscriptions()->count();
    }

    /**
     * Get total subscribers (all time)
     */
    public function getTotalSubscribersAttribute(): int
    {
        return $this->subscriptions()->count();
    }

    /**
     * Get formatted price
     */
    public function getFormattedPriceAttribute(): string
    {
        if ($this->price > 0) {
            return '₹' . number_format($this->price, 2);
        }
        return 'Custom';
    }

    /**
     * Get formatted setup fee
     */
    public function getFormattedSetupFeeAttribute(): string
    {
        if ($this->setup_fee >= 0) {
            return '₹' . number_format($this->setup_fee, 2);
        }
        return 'N/A';
    }

    /**
     * Get plan features as clean array
     */
    public function getFeaturesListAttribute(): array
    {
        $features = $this->features;

        if (is_string($features)) {
            $features = json_decode($features, true);
        }

        if (is_array($features)) {
            return array_values(
                array_filter($features, function ($feature) {
                    return !empty(trim((string) $feature));
                })
            );
        }

        return [];
    }

    /**
     * Get price in paise (for Razorpay)
     */
    public function getPriceInPaiseAttribute(): int
    {
        return (int) ($this->price * 100);
    }

    /**
     * Get setup fee in paise (for Razorpay)
     */
    public function getSetupFeeInPaiseAttribute(): int
    {
        return (int) ($this->setup_fee * 100);
    }

    /**
     * Get first payment amount (setup fee)
     */
    public function getFirstPaymentAttribute(): float
    {
        return (float) $this->setup_fee;
    }

    /**
     * Get first payment in paise
     */
    public function getFirstPaymentInPaiseAttribute(): int
    {
        return (int) ($this->first_payment * 100);
    }

    /**
     * Get duration in months
     */
    public function getDurationInMonthsAttribute(): float
    {
        if ($this->duration_days > 0) {
            return round($this->duration_days / 30, 1);
        }
        return 0;
    }

    /**
     * Get price per month
     */
    public function getPricePerMonthAttribute(): float
    {
        if ($this->duration_days > 0 && $this->price > 0) {
            return round(($this->price / $this->duration_days) * 30, 2);
        }
        return (float) $this->price;
    }

    /**
     * Get complete pricing details
     */
    public function getPricingDetailsAttribute(): array
    {
        return [
            'setup_fee' => (float) $this->setup_fee,
            'setup_fee_formatted' => $this->formatted_setup_fee,
            'setup_fee_paise' => $this->setup_fee_in_paise,

            'recurring_price' => (float) $this->price,
            'recurring_price_formatted' => $this->formatted_price,
            'recurring_price_paise' => $this->price_in_paise,

            'first_payment' => (float) $this->first_payment,
            'first_payment_formatted' => '₹' . number_format($this->first_payment, 2),
            'first_payment_paise' => $this->first_payment_in_paise,

            'price_per_month' => (float) $this->price_per_month,
            'price_per_month_formatted' => '₹' . number_format($this->price_per_month, 2),

            'duration_type' => $this->duration_type,
            'duration_text' => $this->duration_text,
            'duration_days' => $this->duration_days
        ];
    }

    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    // ✅ HELPER METHODS - STATUS CHECKS
    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

    /**
     * Check if plan is marked as popular
     */
    public function isPopular(): bool
    {
        return (bool) $this->is_popular;
    }

    /**
     * Check if plan is active
     */
    public function isActive(): bool
    {
        return (bool) $this->is_active;
    }

    /**
     * Check if plan has subscribers
     */
    public function hasSubscribers(): bool
    {
        return $this->subscriptions()->exists();
    }

    /**
     * Check if plan has specific feature
     */
    public function hasFeature(string $feature): bool
    {
        return in_array($feature, $this->features_list);
    }

    /**
     * Check if plan is free
     */
    public function isFree(): bool
    {
        return $this->price == 0 && $this->setup_fee == 0;
    }

    /**
     * Check if plan has setup fee
     */
    public function hasSetupFee(): bool
    {
        return $this->setup_fee > 0;
    }

    /**
     * Check if plan is lifetime
     */
    public function isLifetime(): bool
    {
        return $this->duration_type === 'lifetime';
    }

    /**
     * Check if plan requires custom pricing
     */
    public function isCustomPrice(): bool
    {
        return $this->price == 0 && $this->setup_fee == 0;
    }

    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    // ✅ INFO METHODS
    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

    /**
     * Get plan summary
     */
    public function getSummary(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,

            // Pricing
            'setup_fee' => (float) $this->setup_fee,
            'setup_fee_formatted' => $this->formatted_setup_fee,
            'price' => (float) $this->price,
            'price_formatted' => $this->formatted_price,
            'first_payment' => (float) $this->first_payment,
            'first_payment_formatted' => '₹' . number_format($this->first_payment, 2),

            // Duration
            'duration_type' => $this->duration_type,
            'duration_text' => $this->duration_text,
            'duration_days' => $this->duration_days,

            // Status
            'is_active' => $this->is_active,
            'is_popular' => $this->is_popular,

            // Subscribers
            'active_subscribers' => $this->subscribers_count,
            'total_subscribers' => $this->total_subscribers,

            // Features
            'features' => $this->features_list,
            'feature_count' => count($this->features_list),

            // Additional
            'button_text' => $this->button_text,
            'button_color' => $this->button_color,
            'sort_order' => $this->sort_order,
            'created_at' => $this->created_at->toDateTimeString()
        ];
    }

    /**
     * Get detailed plan info
     */
    public function getDetailedInfo(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,

            // Pricing Details
            'pricing' => $this->pricing_details,

            // Features
            'features' => $this->features_list,

            // Statistics
            'statistics' => [
                'active_subscribers' => $this->subscribers_count,
                'total_subscribers' => $this->total_subscribers,
                'total_revenue' => (float) $this->payments()
                    ->where('payment_status', 'completed')
                    ->sum('amount')
            ],

            // Status
            'is_active' => $this->is_active,
            'is_popular' => $this->is_popular,
            'is_free' => $this->isFree(),
            'is_lifetime' => $this->isLifetime(),

            // Additional Info
            'button_text' => $this->button_text,
            'button_color' => $this->button_color,
            'contact_info' => $this->contact_info,
            'sort_order' => $this->sort_order,

            // Timestamps
            'created_at' => $this->created_at->toDateTimeString(),
            'updated_at' => $this->updated_at->toDateTimeString()
        ];
    }

    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    // ✅ BOOT METHOD
    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

    protected static function boot()
    {
        parent::boot();

        /**
         * On creating new plan
         */
        static::creating(function ($plan) {
            // Ensure features is valid JSON
            if (is_array($plan->features)) {
                $plan->features = json_encode($plan->features);
            }

            // Ensure setup_fee has default
            if (is_null($plan->setup_fee)) {
                $plan->setup_fee = 1.00;
            }

            Log::info('📝 Creating new plan', [
                'name' => $plan->name,
                'price' => $plan->price,
                'setup_fee' => $plan->setup_fee,
                'duration_type' => $plan->duration_type
            ]);
        });

        /**
         * After plan created
         */
        static::created(function ($plan) {
            Log::info('✅ Plan created successfully', [
                'id' => $plan->id,
                'name' => $plan->name,
                'setup_fee' => $plan->setup_fee,
                'price' => $plan->price
            ]);
        });

        /**
         * On updating plan
         */
        static::updating(function ($plan) {
            // Ensure features is valid JSON
            if (is_array($plan->features)) {
                $plan->features = json_encode($plan->features);
            }

            // Log price changes
            if ($plan->isDirty('price')) {
                Log::warning('💰 Plan price changing', [
                    'id' => $plan->id,
                    'name' => $plan->name,
                    'from' => $plan->getOriginal('price'),
                    'to' => $plan->price
                ]);
            }

            // Log setup fee changes
            if ($plan->isDirty('setup_fee')) {
                Log::warning('💰 Setup fee changing', [
                    'id' => $plan->id,
                    'name' => $plan->name,
                    'from' => $plan->getOriginal('setup_fee'),
                    'to' => $plan->setup_fee
                ]);
            }

            // Log active status changes
            if ($plan->isDirty('is_active')) {
                Log::info('🔄 Plan active status changing', [
                    'id' => $plan->id,
                    'name' => $plan->name,
                    'from' => $plan->getOriginal('is_active') ? 'Active' : 'Inactive',
                    'to' => $plan->is_active ? 'Active' : 'Inactive'
                ]);
            }

            // Log popular status changes
            if ($plan->isDirty('is_popular')) {
                Log::info('⭐ Plan popular status changing', [
                    'id' => $plan->id,
                    'name' => $plan->name,
                    'is_popular' => $plan->is_popular
                ]);
            }
        });

        /**
         * After plan updated
         */
        static::updated(function ($plan) {
            Log::info('✅ Plan updated', [
                'id' => $plan->id,
                'name' => $plan->name
            ]);
        });

        /**
         * On deleting plan
         */
        static::deleting(function ($plan) {
            $hasSubscribers = $plan->hasSubscribers();

            if ($hasSubscribers) {
                Log::warning('⚠️ Deleting plan with active subscriptions', [
                    'id' => $plan->id,
                    'name' => $plan->name,
                    'total_subscribers' => $plan->total_subscribers
                ]);
            } else {
                Log::info('🗑️ Deleting plan', [
                    'id' => $plan->id,
                    'name' => $plan->name
                ]);
            }
        });

        /**
         * After plan deleted
         */
        static::deleted(function ($plan) {
            Log::info('✅ Plan deleted', [
                'id' => $plan->id,
                'name' => $plan->name
            ]);
        });
    }
}
