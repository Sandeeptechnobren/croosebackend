<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Customer;

class TargetMessage extends Model
{
    protected $table = 'target_messages';

    protected $fillable = [
        'name',
        'target_type',
        'description',
    ];
    public function Customers()
    {
        return match ($this->target_type) {
            'new' => Customer::whereDate('created_at', today())->get(),
            'recent' => Customer::where('created_at', '>=', now()->subDays(7))->get(),
            'active' => Customer::whereIn('id', function ($q) {
                $q->select('customer_id')
                  ->from('orders')
                  ->where('created_at', '>=', now()->subMonth())
                  ->groupBy('customer_id');
            })->get(),
            'all' => Customer::all(),
            default  => collect(),
        };
    }
}
