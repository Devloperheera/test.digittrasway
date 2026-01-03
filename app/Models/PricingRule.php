<?php
// app/Models/PricingRule.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PricingRule extends Model
{
    use HasFactory;

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
        'is_active' => 'boolean'
    ];

    // ✅ SCOPES
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForDistance($query, $distance)
    {
        return $query->where('distance_from', '<=', $distance)
                    ->where(function($q) use ($distance) {
                        $q->whereNull('distance_to')
                          ->orWhere('distance_to', '>=', $distance);
                    });
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('distance_from');
    }

    // ✅ CALCULATE PRICE - MAIN METHOD
    public static function calculatePrice($distanceKm, $weightTons = 0)
    {
        // Find pricing rule for the given distance
        $pricing = self::active()
            ->forDistance($distanceKm)
            ->orderBy('distance_from', 'desc')
            ->first();

        // Default values if no rule found
        if (!$pricing) {
            $pricePerKm = 15;
            $minimumCharge = 300;
            $tierName = 'default';
        } else {
            $pricePerKm = $pricing->price_per_km;
            $minimumCharge = $pricing->minimum_charge;
            $tierName = $pricing->name;
        }

        // Calculate base price
        $distanceCharge = $distanceKm * $pricePerKm;
        $basePrice = max($distanceCharge, $minimumCharge);

        // Weight surcharge (optional - customize as needed)
        $weightSurcharge = 0;
        $weightThreshold = 10; // tons
        $surchargePerTon = 50; // ₹50 per extra ton

        if ($weightTons > $weightThreshold) {
            $excessWeight = $weightTons - $weightThreshold;
            $weightSurcharge = $excessWeight * $surchargePerTon;
        }

        $totalPrice = $basePrice + $weightSurcharge;

        return [
            'base_price' => round($basePrice, 2),
            'distance_charge' => round($distanceCharge, 2),
            'weight_surcharge' => round($weightSurcharge, 2),
            'total_price' => round($totalPrice, 2),
            'price_per_km' => $pricePerKm,
            'minimum_charge' => $minimumCharge,
            'pricing_tier' => $tierName,
            'distance_km' => $distanceKm,
            'weight_tons' => $weightTons
        ];
    }

    // ✅ ALTERNATIVE: Get pricing rule for specific distance
    public static function getPricingForDistance($distance)
    {
        return self::active()
            ->forDistance($distance)
            ->orderBy('distance_from', 'desc')
            ->first();
    }

    // ✅ Get all active pricing rules
    public static function getAllActivePricing()
    {
        return self::active()->ordered()->get();
    }
}
