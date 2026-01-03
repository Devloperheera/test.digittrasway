<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class Material extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'is_active',
        'sort_order'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    protected $attributes = [
        'is_active' => true,
        'sort_order' => 1
    ];

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    /**
     * Accessors
     */
    public function getStatusBadgeAttribute()
    {
        return $this->is_active
            ? '<span class="badge bg-success"><i class="fas fa-check-circle me-1"></i>Active</span>'
            : '<span class="badge bg-secondary"><i class="fas fa-times-circle me-1"></i>Inactive</span>';
    }

    /**
     * Boot method
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (!$model->sort_order) {
                $model->sort_order = static::max('sort_order') + 1;
            }

            Log::info('Creating material', ['name' => $model->name]);
        });

        static::created(function ($model) {
            Log::info('Material created', ['id' => $model->id]);
        });

        static::updating(function ($model) {
            if ($model->isDirty('is_active')) {
                Log::info('Material status changing', [
                    'id' => $model->id,
                    'from' => $model->getOriginal('is_active'),
                    'to' => $model->is_active
                ]);
            }
        });
    }
}
