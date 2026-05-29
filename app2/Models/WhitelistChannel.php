<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class WhitelistChannel extends Model
{
   use SoftDeletes;

   protected $table = 'whitelist_channels';

    protected $fillable = ['user_id','channel_id','white_id'];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

}
