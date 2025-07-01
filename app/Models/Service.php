<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'name',
        'slug',
        'description',
        'duration_minutes',
        'price',
        'unit',
        'category',
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
}
