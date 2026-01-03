<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Log;

class VendorPlan extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'vendor_plans';

    protected $fillable = [
        'vendor_id',  // ✅ ADD KIYA
        'plan_name',  // Changed from 'name' to 'plan_name'
        'description',
        'price',
        'setup_fee',  // ✅ ADD KIYA
        'duration_type',
        'total_billing_cycles',  // ✅ ADD KIYA
        'features',
        'is_popular',
        'is_active',
        'button_text',
        'button_color',
        'sort_order'
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'setup_fee' => 'decimal:2',  // ✅ ADD KIYA
        'total_billing_cycles' => 'integer',  // ✅ ADD KIYA
        'features' => 'array',
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
        'features' => '[]',
        'setup_fee' => 0  // ✅ ADD KIYA
    ];

    // ✅ RELATIONSHIPS

    public function vendor()
    {
        return $this->belongsTo(Vendor::class, 'vendor_id');
    }

    public function subscriptions()
    {
        return $this->hasMany(VendorPlanSubscription::class, 'vendor_plan_id');
    }

    public function activeSubscriptions()
    {
        return $this->hasMany(VendorPlanSubscription::class, 'vendor_plan_id')
            ->where('subscription_status', 'active')
            ->where('expires_at', '>', now());
    }

    public function payments()
    {
        return $this->hasMany(VendorPayment::class, 'vendor_plan_id');
    }

    public function vendors()
    {
        return $this->belongsToMany(Vendor::class, 'vendor_plan_subscriptions')
            ->withPivot('subscription_status', 'starts_at', 'expires_at')
            ->withTimestamps();
    }

    // ✅ SCOPES

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopePopular($query)
    {
        return $query->where('is_popular', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order', 'asc')
            ->orderBy('price', 'asc');
    }

    public function scopeByVendor($query, $vendorId)
    {
        return $query->where('vendor_id', $vendorId);
    }

    // ✅ ACCESSORS

    public function getFormattedPriceAttribute()
    {
        return $this->price > 0 ? '₹' . number_format($this->price, 2) : 'Free';
    }

    public function getFormattedSetupFeeAttribute()
    {
        return $this->setup_fee > 0 ? '₹' . number_format($this->setup_fee, 2) : 'Free';  // ✅ ADD KIYA
    }

    public function getDurationTextAttribute()
    {
        $durations = [
            'daily' => 'Daily',
            'weekly' => 'Weekly',
            'monthly' => 'Monthly',
            'quarterly' => 'Quarterly',
            'half_yearly' => 'Half Yearly',
            'yearly' => 'Yearly'
        ];

        return $durations[$this->duration_type] ?? ucfirst(str_replace('_', ' ', $this->duration_type));
    }

    public function getFullDurationTextAttribute()
    {
        $daysMap = [
            'daily' => 1,
            'weekly' => 7,
            'monthly' => 30,
            'quarterly' => 90,
            'half_yearly' => 180,
            'yearly' => 365
        ];

        $days = $daysMap[$this->duration_type] ?? 30;
        return $this->duration_text . ' (' . $days . ' days)';
    }

    public function getDurationInMonthsAttribute()
    {
        $daysMap = [
            'daily' => 1,
            'weekly' => 7,
            'monthly' => 30,
            'quarterly' => 90,
            'half_yearly' => 180,
            'yearly' => 365
        ];

        $days = $daysMap[$this->duration_type] ?? 30;
        return round($days / 30, 1);
    }

    public function getPricePerMonthAttribute()
    {
        $daysMap = [
            'daily' => 1,
            'weekly' => 7,
            'monthly' => 30,
            'quarterly' => 90,
            'half_yearly' => 180,
            'yearly' => 365
        ];

        $days = $daysMap[$this->duration_type] ?? 30;
        if ($days > 0 && $this->price > 0) {
            return round(($this->price / $days) * 30, 2);
        }
        return $this->price;
    }

    public function getTotalRevenueAttribute()
    {
        return $this->payments()
            ->whereIn('payment_status', ['success', 'paid'])
            ->sum('amount_paid');
    }

    public function getActiveSubscriptionsCountAttribute()
    {
        return $this->activeSubscriptions()->count();
    }

    public function getTotalSubscriptionsCountAttribute()
    {
        return $this->subscriptions()->count();
    }

    public function getSuccessfulPaymentsCountAttribute()
    {
        return $this->payments()
            ->whereIn('payment_status', ['success', 'paid'])
            ->count();
    }

    public function getStatusBadgeAttribute()
    {
        return $this->is_active
            ? '<span class="badge bg-success"><i class="fas fa-check-circle me-1"></i>Active</span>'
            : '<span class="badge bg-secondary"><i class="fas fa-times-circle me-1"></i>Inactive</span>';
    }

    public function getPopularBadgeAttribute()
    {
        return $this->is_popular
            ? '<span class="badge bg-warning text-dark"><i class="fas fa-star me-1"></i>Popular</span>'
            : '';
    }

    public function getFeaturesListAttribute()
    {
        if (is_array($this->features)) {
            return array_values(array_filter($this->features, function($feature) {
                return !empty(trim($feature));
            }));
        }
        return [];
    }

    // ✅ HELPER METHODS

    public function isDaily()
    {
        return $this->duration_type === 'daily';
    }

    public function isWeekly()
    {
        return $this->duration_type === 'weekly';
    }

    public function isMonthly()
    {
        return $this->duration_type === 'monthly';
    }

    public function isQuarterly()
    {
        return $this->duration_type === 'quarterly';
    }

    public function isHalfYearly()
    {
        return $this->duration_type === 'half_yearly';
    }

    public function isYearly()
    {
        return $this->duration_type === 'yearly';
    }

    public function isFree(): bool
    {
        return $this->price == 0;
    }

    public function hasFeature($feature): bool
    {
        return in_array($feature, $this->features_list);
    }

    public function isActive(): bool
    {
        return (bool) $this->is_active;
    }

    public function isPopular(): bool
    {
        return (bool) $this->is_popular;
    }

    public function getSummaryAttribute()
    {
        $daysMap = [
            'daily' => 1,
            'weekly' => 7,
            'monthly' => 30,
            'quarterly' => 90,
            'half_yearly' => 180,
            'yearly' => 365
        ];

        $days = $daysMap[$this->duration_type] ?? 30;

        return [
            'id' => $this->id,
            'vendor_id' => $this->vendor_id,
            'plan_name' => $this->plan_name,
            'description' => $this->description,
            'price' => $this->price,
            'formatted_price' => $this->formatted_price,
            'setup_fee' => $this->setup_fee,  // ✅ ADD KIYA
            'formatted_setup_fee' => $this->formatted_setup_fee,  // ✅ ADD KIYA
            'price_per_month' => $this->price_per_month,
            'duration_type' => $this->duration_type,
            'duration_days' => $days,
            'duration_text' => $this->duration_text,
            'full_duration_text' => $this->full_duration_text,
            'total_billing_cycles' => $this->total_billing_cycles,  // ✅ ADD KIYA
            'features' => $this->features_list,
            'is_popular' => $this->is_popular,
            'is_active' => $this->is_active,
            'button_text' => $this->button_text,
            'button_color' => $this->button_color,
            'active_subscriptions_count' => $this->active_subscriptions_count,
            'total_subscriptions_count' => $this->total_subscriptions_count,
            'total_revenue' => $this->total_revenue,
            'successful_payments_count' => $this->successful_payments_count
        ];
    }

    // ✅ BOOT

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (!$model->sort_order) {
                $model->sort_order = (static::max('sort_order') ?? 0) + 1;
            }

            Log::info('Creating vendor plan', [
                'plan_name' => $model->plan_name,
                'price' => $model->price,
                'setup_fee' => $model->setup_fee,
                'duration_type' => $model->duration_type
            ]);
        });

        static::created(function ($model) {
            Log::info('Vendor plan created', [
                'id' => $model->id,
                'plan_name' => $model->plan_name
            ]);
        });

        static::updating(function ($model) {
            if ($model->isDirty('price')) {
                Log::info('Vendor plan price changing', [
                    'id' => $model->id,
                    'from' => $model->getOriginal('price'),
                    'to' => $model->price
                ]);
            }

            if ($model->isDirty('setup_fee')) {  // ✅ ADD KIYA
                Log::info('Vendor plan setup_fee changing', [
                    'id' => $model->id,
                    'from' => $model->getOriginal('setup_fee'),
                    'to' => $model->setup_fee
                ]);
            }

            if ($model->isDirty('is_active')) {
                Log::info('Vendor plan active status changing', [
                    'id' => $model->id,
                    'from' => $model->getOriginal('is_active'),
                    'to' => $model->is_active
                ]);
            }
        });

        static::deleting(function ($model) {
            Log::info('Deleting vendor plan', [
                'id' => $model->id,
                'plan_name' => $model->plan_name
            ]);
        });
    }
}
