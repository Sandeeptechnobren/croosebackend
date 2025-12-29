<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Ordiio_transaction extends Model
{
    use SoftDeletes;

    protected $table = 'Ordiio_transactions';

    protected $fillable = [
        'customer_id',
        'license_type',
        'reference_id',
        'amount',
        'payment_origin',
        'currency',
        'payment_method',
        'transaction_status',
        'transaction_id',
        'paid_amount',
        'paid_currency',
        'stripe_session_id',
        'is_manual',
        'meta',
        'invoice_url',
        'receipt_url',
        'paid_at',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            $model->uuid = (string) Str::uuid();
        });
    }
}
