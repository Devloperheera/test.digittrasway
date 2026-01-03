<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DistancePricing extends Model
{
    use HasFactory;

    protected $table = 'pricing_rules'; // ✅ CORRECT TABLE NAME from your database

    protected $fillable = [
        'name',
        'price_per_km',
        'distance_from',
        'distance_to',
        'minimum_charge',
        'description',
        'is_active',
        'sort_order'
    ];

    protected $casts = [
        'price_per_km' => 'decimal:2',
        'distance_from' => 'decimal:2',
        'distance_to' => 'decimal:2',
        'minimum_charge' => 'decimal:2',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * Scope to get only active pricing
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to order by sort order
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order', 'asc');
    }

    /**
     * Get distance range text
     */
    public function getDistanceRangeAttribute()
    {
        if ($this->distance_to === null || $this->distance_to == 0) {
            return $this->distance_from . '+ km';
        }
        return $this->distance_from . ' - ' . $this->distance_to . ' km';
    }

    /**
     * Calculate price for given distance
     */
    public function calculatePrice($distance)
    {
        $calculatedPrice = $distance * $this->price_per_km;
        return max($calculatedPrice, $this->minimum_charge);
    }

    /**
     * Boot method for model events
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (!$model->sort_order) {
                $model->sort_order = static::max('sort_order') + 1;
            }
        });
    }
}
