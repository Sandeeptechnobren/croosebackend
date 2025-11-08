<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;
class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'space_id',
        'name',
        'slug',
        'description',
        'price',
        'currency',
        'unit',
        'type',
        'stock',
        'sku',
        'category',
        'image',
        'tags',
        'is_featured',
        'is_active',
        'uuid',
    ];

    protected $casts = [
        'tags' => 'array',
        'is_featured' => 'boolean',
        'is_active' => 'boolean',
    ];

    protected static function boot()
        {
            parent::boot();

            static::creating(function ($model) {
                if (empty($model->uuid)) {
                    $model->uuid = (string) Str::uuid();
                }
            });
        }

    // Product.php
    public function space()
        {
            return $this->belongsTo(Space::class, 'space_id');
        }

    
}
    

