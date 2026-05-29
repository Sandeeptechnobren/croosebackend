<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Licensed_track extends Model
{
    protected $fillable= [
        'purchase_id',
        'track_id',
        'user_id',
        'created_at',
    ]
}
