<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FeedBackManagement extends Model
{
    protected $table = 'feedback_management';

    protected $fillable = [
        'title',
        'description',
        'attachment',
        'origin',
        'type',
        'priority',
        'severity',
        'tag',
        'status',
        'estimation'
    ];
}
