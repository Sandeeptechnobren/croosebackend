<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ordiio_license_purchases extends Model
{
    protected $table = 'ordiio_license_purchases';

    protected $fillable = [
        'uuid',
        'customer_id',
        'ordiio_license_id',
        'licensed_track_id',
        'amount',
        'currency',
        'status',
        'payment_reference',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function license()
    {
        return $this->belongsTo(OrdiioLicense::class, 'ordiio_license_id');
    }

    public function track()
    {
        return $this->belongsTo(Track::class);
    }
}
