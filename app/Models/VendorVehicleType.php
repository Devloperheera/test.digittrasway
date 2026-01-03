<?php
// app/Models/VendorVehicleType.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VendorVehicleType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'description', 'available_lengths', 'available_capacities',
        'tyre_variants', 'is_active', 'sort_order'
    ];

    protected $casts = [
        'available_lengths' => 'array',
        'available_capacities' => 'array',
        'tyre_variants' => 'array',
        'is_active' => 'boolean'
    ];

    public function vendors()
    {
        return $this->hasMany(Vendor::class, 'vehicle_type', 'name');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }
}
