<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use App\Models\subscription;

class customer_subscriptions extends Model
{
    protected $table = 'customer_subscriptions';

    protected $fillable = [
        'customer_id',
        'client_id',
        'subscription_id',
        'service_id',
        'space_id',
        'start_date',
        'end_date',
        'status',
        'renewal_type',
        'payment_origin',
        'payment_method',
        'payment_status',
        'payment_reference',
        'amount',
        'currency',
        'meta',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date'   => 'date',
        'meta'       => 'array',
    ];

    protected static function booted()
    {
        static::creating(function ($model) {
            if ((!isset($model->amount) || $model->amount <= 0) && $model->subscription_id) {

                $subscription = subscription::find($model->subscription_id);

                if ($subscription) {
                    $model->amount   = $subscription->price;
                    $model->currency = strtolower($subscription->currency ?? 'usd');
                }
            }
        });
    }

    public function subscription()
    {
        return $this->belongsTo(Subscription::class, 'subscription_id', 'id');
    }
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
    public function service()
    {
        return $this->belongsTo(Service::class, 'service_id');
    }
    public function transactions()
    {
        return $this->hasMany(SubscriptionTransaction::class);
    }
    /* Scopes */
    public function scopeActive(Builder $q): Builder
    {
        return $q->where('status', 'active')
                 ->whereDate('end_date', '>=', Carbon::today());
    }
    public function scopeForCustomerInSpace(Builder $q, int $customerId, int $spaceId): Builder
    {
        return $q->where('customer_id', $customerId)
                 ->where('space_id', $spaceId);
    }
    /* Helpers */
    public function isActive(): bool
    {
        return $this->status === 'active' && $this->end_date->isFuture();
    }
}
