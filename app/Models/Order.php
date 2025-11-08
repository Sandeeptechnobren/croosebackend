<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
class Order extends Model
{
    use SoftDeletes; // 

    
    protected $fillable = [
        'uuid',
        'client_id',
        'space_id',
        'customer_id',
        'product_id', 
        'order_quantity',
        'order_amount',
        'payment_status',
        'payment_origin',
        'payment_method',
        'status',
        'address',
        'notes',
        'pincode',
    ];

    protected static function booted()
     {
        static::creating(function ($order) {
            $order->uuid = Str::uuid()->toString();
        });
     }
     
    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
    public function space()
    {
        return $this->belongsTo(Space::class, 'space_id');
}
}