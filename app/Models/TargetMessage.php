<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TargetMessage extends Model
{
    protected $table = 'target_messages';

    protected $fillable = [
        'user_id',
        'target_type',
        'recent_days'
    ];
    public function broadcasts()
    {
        return $this->hasMany(BroadcastHeader::class);
    }
}

