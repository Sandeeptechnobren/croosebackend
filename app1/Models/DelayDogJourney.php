<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class DelayDogJourney extends Model
{
    use HasFactory;
    
    protected $table = 'delay_dog_journeys';

    protected $fillable = [
        'uuid',
        'user_id',
        'origin_station',
        'destination_station',
        'journey_date',
        'was_delayed',
        'delay_minutes',
    ];

    public function user()
        {
            return $this->belongsTo(DelayDogUserDetail::class, 'user_id');
        }
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
