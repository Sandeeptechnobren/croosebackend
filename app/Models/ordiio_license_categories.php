<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class ordiio_license_categories extends Model
{
    protected $fillable = [
        'name',
        'description',
        'allowed_usage',
        'restrictions',
        'price_model',
    ];

    protected $casts = [
        'allowed_usage' => 'array',
    ];
}
