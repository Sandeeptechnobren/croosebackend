<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrdiioTransaction extends Model
{
    protected $fillable = ['reference_id','customer_id','type','amount','currency','status'];
}
