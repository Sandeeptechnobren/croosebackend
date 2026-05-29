<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Transaction extends Model
{
    use SoftDeletes;
 
    protected $table = 'transactions';
 
    protected $fillable = ['uuid','client_id','customer_id','type','reference_id','amount','currency','payment_origin','payment_method','transaction_status','transaction_id','stripe_session_id',
        'is_manual','meta','invoice_url','receipt_url','paid_currency','paid_amount','paid_at'];

    
    protected $casts = [
        'amount' => 'decimal:2',
        'is_manual' => 'boolean',
        'meta' => 'array',
        'paid_at' => 'datetime',
    ];

    protected static function booted()
     {
        static::creating(function ($order) {
            $order->uuid = Str::uuid()->toString();
        });
     }
    
    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}

