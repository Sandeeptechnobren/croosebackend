<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrdiioSubscription extends Model
{
    protected $fillable = [
        'uuid',
        'subscription_type',
        'validity',
        'region',
        'customer_email',
        'stripe_session_id',
        'stripe_subscription_id',
        'stripe_payment_intent_id',
        'payment_status',
        'metadata'
    ];

    protected $casts = [
        'metadata' => 'array'
    ];
}
