<?php
// app/Services/TruckPricingService.php

namespace App\Services;

use App\Models\PricingRule;
use App\Models\TruckSpecification;
use Illuminate\Support\Facades\Log;

class TruckPricingService
{
    /**
     * Calculate price using ONLY pricing_rules table
     */
    public function calculatePrice($distance, $weight, $truckSpecId, $materialId = null)
    {
        try {
            // ✅ GET PRICE FROM PRICING_RULES TABLE ONLY
            $pricingRule = PricingRule::active()
                ->forDistance($distance)
                ->orderBy('distance_from', 'desc')
                ->first();

            if (!$pricingRule) {
                // Fallback to default pricing
                $pricePerKm = 10.0; // Default ₹10/km
                $minimumCharge = 500.0;
                $ruleName = 'Default Rate';
            } else {
                $pricePerKm = $pricingRule->price_per_km;
                $minimumCharge = $pricingRule->minimum_charge;
                $ruleName = $pricingRule->name;
            }

            // ✅ SIMPLE CALCULATION: Distance × Rate Per KM
            $calculatedPrice = $distance * $pricePerKm;

            // Apply minimum charge
            $finalPrice = max($calculatedPrice, $minimumCharge);

            // Get truck specification for info only (not for pricing)
            $truckSpec = TruckSpecification::with('truckType')->find($truckSpecId);

            // Price breakdown for transparency
            $breakdown = [
                'pricing_source' => 'pricing_rules_table_only',
                'base_calculation' => [
                    'distance_km' => $distance,
                    'price_per_km' => $pricePerKm,
                    'calculated_price' => round($calculatedPrice, 2),
                    'pricing_rule_used' => $ruleName
                ],
                'minimum_charge_check' => [
                    'minimum_charge' => $minimumCharge,
                    'calculated_price' => round($calculatedPrice, 2),
                    'minimum_applied' => $calculatedPrice < $minimumCharge
                ],
                'final_calculation' => [
                    'final_price' => round($finalPrice, 2),
                    'currency' => 'INR'
                ],
                'truck_info' => [
                    'truck_type' => $truckSpec->truckType->name ?? 'Unknown',
                    'length' => $truckSpec->length . ' ft',
                    'tyre_count' => $truckSpec->tyre_count . ' tyre',
                    'height' => $truckSpec->height ? $truckSpec->height . ' ft' : null,
                    'note' => 'Truck specifications for info only - not affecting price'
                ],
                'weight_info' => [
                    'material_weight' => $weight . ' tons',
                    'note' => 'Weight for info only - not affecting price currently'
                ]
            ];

            return [
                'success' => true,
                'price_per_km' => $pricePerKm,
                'estimated_price' => round($finalPrice, 2),
                'breakdown' => $breakdown,
                'currency' => 'INR',
                'pricing_rule_id' => $pricingRule->id ?? null
            ];

        } catch (\Exception $e) {
            Log::error('Truck Pricing Calculation Error', [
                'error' => $e->getMessage(),
                'distance' => $distance,
                'weight' => $weight,
                'truck_spec_id' => $truckSpecId
            ]);

            return [
                'success' => false,
                'message' => 'Price calculation failed: ' . $e->getMessage()
            ];
        }
    }
}
