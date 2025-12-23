<<<<<<< HEAD
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
||||||| parent of b872fe7 (Live code)
=======
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ordiio_license_purchases extends Model
{
    protected $table = 'ordiio_license_purchases';

    protected $fillable = [
        'uuid',
        'customer_id',
        'ordiio_license_id',
        'track_id',
        'amount',
        'currency',
        'status',
        'stripe_session_id',
        'stripe_payment_intent',
        'project_title',
        'project_type',
        'payment_reference',
        'license_category_id',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = Str::uuid()->toString();
            }
        });
    }

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
>>>>>>> b872fe7 (Live code)
