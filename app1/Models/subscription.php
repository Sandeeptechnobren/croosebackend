<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use App\Models\subscription_items;
use App\Models\Space;


class Subscription extends Model
{
    protected $fillable = [
        'space_id',
        'client_id',
        'name',
        'description',
        'subscription_type',
        'variant',
        'price',
        'currency',
        'access_type',
        'discount_rate',
        'duration_days',
        'benefits',
        'status',
        'published_at',
    ];
    protected $casts = [
        'benefits'     => 'array',
        'published_at' => 'datetime',
    ];
    public function items()
    {
        return $this->hasMany(subscription_items::class, 'subscription_id', 'id');
    }
    public function space()
    {
        return $this->belongsTo(Space::class, 'space_id');
    }       

    public function service()
    {
        return $this->belongsTo(Service::class, 'service_id');
    }
    public function customerSubscriptions() {
        return $this->hasMany(CustomerSubscription::class);
    }
    public function scopeActive(Builder $q): Builder {
        return $q->where('status', 'active');
    }
    public function scopeForSpace(Builder $q, int $spaceId): Builder {
        return $q->where('space_id', $spaceId);
    }
    public function inferredDurationDays(): int
    {
        if ($this->duration_days) return (int) $this->duration_days;
        return $this->variant === 'yearly' ? 365 : 30;
    }
    public function isDiscountAccess(): bool
    {
        return $this->access_type === 'discount' && $this->discount_rate !== null;
    }
}
