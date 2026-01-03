<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BroadcastSchedule extends Model
{
    protected $table = 'broadcast_schedules';

    protected $primaryKey = 'id';
    public $timestamps = true;

    protected $fillable = [
        'broadcast_id',
        'target_customer',
        'scheduled_at'
    ];

    protected $casts = [
        'scheduled_at' => 'datetime'
    ];
}
