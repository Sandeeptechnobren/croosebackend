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
        'content',
        'user_id',
        'created_by',
        'updated_by',
        'deleted_by'
    ];
}
