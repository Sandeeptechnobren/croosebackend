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
