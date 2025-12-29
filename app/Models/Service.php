<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
class Service extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'space_id',
        'name',
        'description',
        'duration_minutes',
        'price',
        'currency',
        'unit',
        'category',
        'image',
        'type',
        'buffer_minutes',
        'available_days',
        'ai_tags',
        'is_active',
        'is_featured',
    ];

    protected $casts = [
        'available_days' => 'array',
        'ai_tags' => 'array',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
    ];
    protected static function booted()
        {
            static::creating(function ($service) {
                if (empty($service->uuid)) {
                    $service->uuid = (string) Str::uuid();
                }
            });
        }
    public function space()
        {
            return $this->belongsTo(Space::class, 'space_id');
        }
}
