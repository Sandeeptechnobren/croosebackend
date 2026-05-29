<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrdiioLicensePurchase extends Model
{
    protected $fillable = ['uuid','customer_id','license_category_id','track_id','amount','currency','status','payment_reference','stripe_session_id','stripe_payment_intent','meta'];
    protected $casts = ['meta' => 'array'];
    public function category()
    {
        return $this->belongsTo(OrdiioLicenseCategory::class,'license_category_id');
    }
    public function customer()
    {
        return $this->belongsTo(\App\Models\User::class,'customer_id');
    }
}
