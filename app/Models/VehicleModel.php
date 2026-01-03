<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VehicleModel extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'model_name',
        'vehicle_type_desc',
        'body_length',
        'body_width',
        'body_height',
        'carry_capacity_kgs',
        'carry_capacity_tons',
        'is_active',
        'display_order'
    ];

    protected $casts = [
        'body_length' => 'decimal:2',
        'body_width' => 'decimal:2',
        'body_height' => 'decimal:2',
        'carry_capacity_kgs' => 'integer',
        'carry_capacity_tons' => 'decimal:2',
        'is_active' => 'boolean',
        'display_order' => 'integer'
    ];

    // ✅ Relationships
    public function category()
    {
        return $this->belongsTo(VehicleCategory::class, 'category_id');
    }

    public function vendors()
    {
        return $this->hasMany(Vendor::class, 'vehicle_model_id');
    }

    public function bookings()
    {
        return $this->hasMany(TruckBooking::class, 'vehicle_model_id');
    }

    // ✅ Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByCategory($query, $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('display_order', 'asc');
    }

    // ✅ Accessors
    public function getFullCapacityAttribute()
    {
        return $this->carry_capacity_tons . ' tons (' . $this->carry_capacity_kgs . ' kg)';
    }

    public function getDimensionsAttribute()
    {
        return $this->body_length . ' × ' . $this->body_width . ' × ' . $this->body_height . ' ft';
    }
}
