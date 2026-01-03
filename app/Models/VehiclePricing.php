<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class VehiclePricing extends Model
{
    use HasFactory;

    protected $table = 'vehicle_pricings';

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
        'sort_order' => 'integer'
    ];

    /**
     * Get pricing tier for given distance
     */
    public static function getPricingForDistance($distance)
    {
        $pricing = self::where('is_active', true)
            ->where('distance_from', '<=', $distance)
            ->where(function ($query) use ($distance) {
                $query->where('distance_to', '>=', $distance)
                      ->orWhereNull('distance_to');
            })
            ->orderBy('distance_from', 'desc')
            ->first();

        if (!$pricing) {
            // Fallback to first pricing tier
            $pricing = self::where('is_active', true)
                ->orderBy('distance_from')
                ->first();
        }

        return $pricing;
    }

    /**
     * Calculate price for distance
     */
    public static function calculatePrice($distance, $materialWeight = 0)
    {
        $pricing = self::getPricingForDistance($distance);

        if (!$pricing) {
            return [
                'base_price' => 0,
                'distance_charge' => 0,
                'weight_surcharge' => 0,
                'total_price' => 0,
                'pricing_tier' => 'Not Available',
                'breakdown' => []
            ];
        }

        // Base calculation
        $pricePerKm = $pricing->price_per_km;
        $distanceCharge = $distance * $pricePerKm;

        // Weight surcharge (if weight > 10 tons)
        $weightSurcharge = $materialWeight > 10 ? ($materialWeight - 10) * 50 : 0;

        // Apply minimum charge
        $subtotal = $distanceCharge + $weightSurcharge;
        $total = max($pricing->minimum_charge, $subtotal);

        return [
            'base_price' => round($pricing->minimum_charge, 2),
            'distance_charge' => round($distanceCharge, 2),
            'weight_surcharge' => round($weightSurcharge, 2),
            'total_price' => round($total, 2),
            'price_per_km' => $pricePerKm,
            'pricing_tier' => $pricing->name,
            'distance_km' => round($distance, 2),
            'material_weight' => $materialWeight,
            'breakdown' => [
                'calculation' => "{$pricePerKm} × {$distance} km = {$distanceCharge}",
                'weight_info' => $materialWeight > 10 ? "Weight surcharge: ({$materialWeight} - 10) × 50 = {$weightSurcharge}" : null,
                'minimum_applied' => $total == $pricing->minimum_charge
            ]
        ];
    }
}
