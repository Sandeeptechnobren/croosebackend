<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class subscription_items extends Model
{
    protected $table = 'subscription_items';

    protected $fillable = [
        'subscription_id',
        'item_type',
        'item_id',
        'extra_benefit',
    ];

    protected $casts = [
        'extra_benefit' => 'array',
    ];

    public function subscription()
    {
        return $this->belongsTo(Subscription::class);
    }

    public function item()
    {
        return $this->morphTo(null, 'item_type', 'item_id');
    }
}
