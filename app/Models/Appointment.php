<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
            'customer_id',
            'service_id',
            'scheduled_at',
            'status',
            'notes'
        ];
}
