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

class OrdiioLicensePurchase extends Model
{
    protected $fillable = ['uuid','customer_id','license_category_id','track_id','amount','currency','status','payment_reference','stripe_session_id','stripe_payment_intent','meta'];
    protected $casts = ['meta' => 'array'];
    public function category()
    {
        return $this->belongsTo(OrdiioLicenseCategory::class,'license_category_id');
    }
    public function customer()
    {
        return $this->belongsTo(\App\Models\User::class,'customer_id');
    }
}
>>>>>>> b872fe7 (Live code)
