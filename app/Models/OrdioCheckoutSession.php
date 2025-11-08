<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrdioCheckoutSession extends Model
{
    use HasApiTokens, HasFactory, Notifiable,SoftDeletes;
    protected $fillable = [
        'uuid',
        'mode',
        'customer_id',
        'client_reference_id',
        'customer_email',
        'metadata',
        'status',
        'amount',
        'currecy_name',
        'subscription_type',
        'validity',
        'payment_reference',
        'region'
    ];
    
}
