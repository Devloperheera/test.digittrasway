<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TruckSpecification extends Model
{
    use HasFactory;

    protected $table = 'truck_specifications';

    protected $fillable = [
        'truck_type_id',
        'length',
        'length_unit',
        'tyre_count',
        'height',
        'height_unit',
        'max_weight',
        'base_price_per_km',
        'is_active'
    ];

    protected $casts = [
        'length' => 'decimal:2',
        'height' => 'decimal:2',
        'max_weight' => 'decimal:2',
        'base_price_per_km' => 'decimal:2',
        'tyre_count' => 'integer',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * Relationship with TruckType
     */
    public function truckType()
    {
        return $this->belongsTo(TruckType::class);
    }

    /**
     * Scope to get active specifications
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to filter by truck type
     */
    public function scopeByTruckType($query, $truckTypeId)
    {
        return $query->where('truck_type_id', $truckTypeId);
    }

    /**
     * Get formatted length with unit
     */
    public function getLengthTextAttribute()
    {
        return $this->length ? $this->length . ' ' . strtoupper($this->length_unit ?? 'ft') : 'N/A';
    }

    /**
     * Get formatted height with unit
     */
    public function getHeightTextAttribute()
    {
        return $this->height ? $this->height . ' ' . strtoupper($this->height_unit ?? 'ft') : 'N/A';
    }

    /**
     * Get tyre count with text
     */
    public function getTyreTextAttribute()
    {
        return $this->tyre_count ? $this->tyre_count . ' ' . ($this->tyre_count > 1 ? 'tyres' : 'tyre') : 'N/A';
    }

    /**
     * Get formatted max weight
     */
    public function getMaxWeightTextAttribute()
    {
        return $this->max_weight ? $this->max_weight . ' tons' : 'N/A';
    }

    /**
     * Get formatted base price per km
     */
    public function getBasePriceTextAttribute()
    {
        return $this->base_price_per_km ? '₹' . number_format($this->base_price_per_km, 2) . '/km' : 'N/A';
    }

    /**
     * Get complete specification summary
     */
    public function getSpecificationSummaryAttribute()
    {
        return sprintf(
            '%s - Length: %s, Height: %s, Tyres: %d, Max Weight: %s',
            $this->truckType->name ?? 'Unknown',
            $this->length_text,
            $this->height_text,
            $this->tyre_count,
            $this->max_weight_text
        );
    }

    /**
     * Alternative formatted accessors (for compatibility)
     */
    public function getFormattedLengthAttribute()
    {
        return $this->length_text;
    }

    public function getFormattedHeightAttribute()
    {
        return $this->height_text;
    }

    public function getFormattedMaxWeightAttribute()
    {
        return $this->max_weight_text;
    }

    /**
     * Calculate price for distance
     */
    public function calculatePriceForDistance($distance)
    {
        if (!$this->base_price_per_km) {
            return null;
        }

        return $distance * $this->base_price_per_km;
    }

    /**
     * Check if specification matches requirements
     */
    public function matchesRequirements($length = null, $height = null, $weight = null)
    {
        $matches = true;

        if ($length !== null) {
            $matches = $matches && ($this->length >= $length);
        }

        if ($height !== null) {
            $matches = $matches && ($this->height >= $height);
        }

        if ($weight !== null) {
            $matches = $matches && ($this->max_weight >= $weight);
        }

        return $matches;
    }

    /**
     * Get status badge HTML
     */
    public function getStatusBadgeAttribute()
    {
        return $this->is_active
            ? '<span class="badge bg-success"><i class="fas fa-check-circle me-1"></i>Active</span>'
            : '<span class="badge bg-secondary"><i class="fas fa-times-circle me-1"></i>Inactive</span>';
    }
}
