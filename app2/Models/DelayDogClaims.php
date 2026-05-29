<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class DelayDogClaims extends Model
{
    protected $table = 'delay_dog_claims';
    protected $fillable = [
        'uuid',
        'journey_id',
        'user_id',
        'claim_reference',
        'status',
        'submitted_at',
        'response_data',
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

    public function journey()
    {
        return $this->belongsTo(DelayDogJourney::class);
    }

    public function user()
    {
        return $this->belongsTo(DelayDogUserDetail::class);
    }
}
