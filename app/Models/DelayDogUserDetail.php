<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class DelayDogUserDetail extends Model
{
    protected $table = 'delay_dog_user_details';

    protected $fillable = [
        'uuid',
        'full_name',
        'email',
        'phone_number',
        'is_monthly_railcard',
        'railcard_image_path',
        'usual_origin',
        'usual_destination',
        'registered_at',
        'last_daily_check',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->uuid = (string) Str::uuid();
        });
    }
}
