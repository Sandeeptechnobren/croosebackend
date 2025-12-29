<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SpaceWhapiPaymentDetail extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'Space_whapi_payment_details';

    protected $fillable = [
        'uuid',
        'client_id',
        'space_id',
        'type',
        'reference_id',
        'amount',
        'payment_origin',
        'currency',
        'payment_method',
        'transaction_status',
        'transaction_id',
        'stripe_session_id',
        'is_manual',
        'meta',
        'invoice_url',
        'receipt_url',
        'paid_at',
    ];

    protected $casts = [
        'is_manual' => 'boolean',
        'paid_at' => 'datetime',
        'meta' => 'array',
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

    public function client()
    {
        return $this->belongsTo(Client::class, 'client_id');
    }

    public function space()
    {
        return $this->belongsTo(Space::class, 'space_id');
    }
}
