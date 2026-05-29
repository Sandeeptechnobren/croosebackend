<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class subscription_transaction extends Model
{
    protected $table = 'subscription_transactions';

    protected $fillable = [
        'customer_subscription_id',
        'reference_id',
        'client_id',
        'space_id',
        'amount',
        'currency',
        'payment_gateway',
        'payment_method',
        'transaction_status',
        'transaction_date',
        'transaction_id',
        'paid_currency',
        'paid_amount',
        'fx_rate',
        'raw_payload',
        'raw_payload',
    ];
    protected $casts = [
        'transaction_date' => 'datetime',
        'raw_payload'      => 'array',
    ];
    public function customerSubscription() {
        return $this->belongsTo(CustomerSubscription::class);
    }
}
 