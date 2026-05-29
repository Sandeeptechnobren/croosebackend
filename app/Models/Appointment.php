<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\SoftDeletes;

class Appointment extends Model
{
   public function client()
{
    return $this->belongsTo(Client::class);
}

public function customer()
{
    return $this->belongsTo(Customer::class);
}

protected static function booted()
{
    static::created(function ($appointment) {
        ClientCustomer::updateOrCreate(
            [
                'client_id' => $appointment->client_id,
                'customer_id' => $appointment->customer_id,
            ],
            [
                'first_interaction_at' => now(),
                'source' => 'appointment',
            ]
        );
    });
}

public function service()
{
    return $this->belongsTo(\App\Models\Service::class);
}
        protected $fillable = [
            'client_id',
            'space_id',
            'customer_id',
            'service_id',
            'appointment_date',
            'start_time',
            'end_time',
            'amount',
            'images',
            'status',
            'notes',
            'uuid',
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
