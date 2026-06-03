<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BroadcastHeader extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'target_id',
        'frequency',
        'scheduled_at',
        'last_sent_at',
        'content',
        'created_by',
        'updated_by',
        'deleted_by'
    ];
    protected $casts = [
        'scheduled_at' => 'date',
        'last_sent_at' => 'datetime',
    ];

    public function target()
    {
        return $this->belongsTo(TargetMessage::class, 'target_id');
    }
}
