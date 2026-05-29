<?php
namespace App\Models;
use Illuminate\Support\Str;

use Illuminate\Database\Eloquent\Model;

class Categories extends Model
{
    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function services()
    {
        return $this->hasMany(Service::class);
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }
    protected $fillable = [
    'name',
    // 'slug',
    'type',
    'client_id',
    'is_active',
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
}

