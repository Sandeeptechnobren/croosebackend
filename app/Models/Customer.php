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
    public function clients()
        {
            return $this->belongsToMany(Client::class, 'client_customer')
                        ->withPivot('first_interaction_at', 'source')
                        ->withTimestamps();
        }


}
