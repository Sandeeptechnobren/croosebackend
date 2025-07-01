<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'name',
        'slug',
        'description',
        'price',
        'unit',
        'type',
        'stock',
        'sku',
        'category',
        'image',
        'tags',
        'is_featured',
        'is_active',
    ];

    protected $casts = [
        'tags' => 'array',
        'is_featured' => 'boolean',
        'is_active' => 'boolean',
    ];
}
