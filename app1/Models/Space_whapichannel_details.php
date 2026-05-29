<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Space_whapichannel_details extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'Space_whapichannel_details';
    protected $fillable = [
        'uuid',
        'space_id',
        'client_id',
        'payment_status',
        'payment_method',
        'payment_origin',
        'payment_reference',
        'payment_amount',
        '_isPremium',
        'instance_id',
        'creationTS',
        'ownerId',
        'activeTill',
        'token',
        'server',
        'stopped',
        'status',
        'name',
        'projectId',
    ];
    protected $casts = [
        'creationTS' => 'integer',
        'activeTill' => 'integer',
        'server' => 'integer',
        'stopped' => 'boolean',
    ];
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }
    public function space()
    {
        return $this->belongsTo(Space::class);
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }
}
