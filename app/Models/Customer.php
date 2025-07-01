<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Model;
use app\Models\Clients;


class Customer extends Model
{
    protected $fillable = ['name', 'phone', 'email', 'whatsapp_number', 'meta'];

    
    public function appointments()
        {
            return $this->hasMany(Appointment::class);
        }

}
