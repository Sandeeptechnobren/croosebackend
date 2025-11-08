<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ordiio_cart extends Model
{
    protected $fillable = [
    'user_id',
    'email',
    'track_id',
    ];
}
