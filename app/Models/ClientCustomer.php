<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClientCustomer extends Model
{
    protected $table = 'client_customer';

    protected $fillable = [
        'client_id',
        'customer_id',
        'first_interaction_at',
        'source',
    ];
}
