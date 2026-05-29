<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrdiioLicenseCategory extends Model
{
    protected $fillable = ['uuid','name','region','stripe_price_id','license_cost','currency','duration'];
    public function purchases()
    {
        return $this->hasMany(OrdiioLicensePurchase::class,'license_category_id');
    }
}
