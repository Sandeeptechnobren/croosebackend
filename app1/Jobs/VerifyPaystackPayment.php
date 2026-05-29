<?php

namespace App\Jobs;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class VerifyPaystackPayment implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $reference;

    public function __construct($reference)
    {
        $this->reference = $reference;
    }

public function handle()
{
    $response = Http::withToken(env('PAYSTACK_SECRET_KEY'))
        ->get("https://api.paystack.co/transaction/verify/{$this->reference}");

    if ($response->successful()) {
        $data = $response->json()['data'];
        $order = Order::where('payment_reference', $this->reference)->first();
        if (!$order) {
            \Log::error("Order not found for reference: {$this->reference}");
            return;
        }
        if ($data['status'] === 'success') {
            $order->payment_status = 'paid';
            \Log::info("Payment successful for reference: {$this->reference}");
        } else {
            $order->payment_status = 'failed';
            \Log::warning("Payment failed for reference: {$this->reference}");
        }
        $order->save();
    } else {
        \Log::error("Verification failed for reference: {$this->reference}");
    }
}

}

