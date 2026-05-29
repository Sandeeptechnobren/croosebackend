<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Stripe\Stripe;
use Stripe\Checkout\Session as StripeSession;
use Stripe\Webhook;
use Illuminate\Support\Facades\Log;

use App\Models\subscription;
use App\Models\Customer;
use App\Models\customer_subscriptions;

class SubscriptionsController extends Controller
{
    public function showPaymentPage($uuid, $phone)
    {
        $subscription = subscription::where('uuid', $uuid)->firstOrFail();
        $customer = Customer::where('whatsapp_number', $phone)->firstOrFail();
        return view('payments.subscribe', compact('subscription', 'customer'));
    }

    public function redirectToStripe($uuid, $phone)
    {
        $subscription = subscription::where('uuid', $uuid)->firstOrFail();
        $customer = Customer::where('whatsapp_number', $phone)->firstOrFail();
        $row = customer_subscriptions::firstOrCreate(
            [
                'customer_id' => $customer->id,
                'subscription_id' => $subscription->id
            ],
            [
                'client_id' => $subscription->client_id,
                'space_id'  => $subscription->space_id,
                'start_date' => now()->toDateString(),
                'end_date'   => now()->addMonth()->toDateString(),
                'status'     => 'active',
                'renewal_type' => 'manual',
                'payment_origin' => 'stripe',
                'payment_status' => 'unpaid' 
            ]
        );

        if (!$row->payment_status) {
            $row->update(['payment_status' => 'unpaid']);
        }
        Stripe::setApiKey(config('services.stripe.secret'));
        $session = StripeSession::create([
            'payment_method_types' => ['card'],
            'mode' => 'payment',
            'line_items' => [[
                'price_data' => [
                    'currency' => strtolower($subscription->currency ?? 'usd'),
                    'product_data' => [
                        'name' => $subscription->name ?? 'Subscription Payment',
                    ],
                    'unit_amount' => (int) ($subscription->price * 100),
                ],
                'quantity' => 1,
            ]],
            'success_url' => url('/api/stripe/success?session_id={CHECKOUT_SESSION_ID}'),
            'cancel_url'  => url('/payment-cancel'),
            'metadata' => [
                'row_id' => (string) $row->id
            ]
        ]);
        $row->update([
            'payment_reference' => $session->id
        ]);
        return redirect($session->url);
    }

    public function handle(Request $request)
    {
        Log::info('Stripe webhook hit');
        $payload   = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        $secret    = config('services.stripe.webhook_secret');
        try {
            $event = Webhook::constructEvent($payload, $sigHeader, $secret);
        } catch (\Exception $e) {
            Log::error('Stripe webhook signature error: ' . $e->getMessage());
            return response('Invalid payload', 400);
        }
        if ($event->type === 'checkout.session.completed') {
            $this->storePayment($event->data->object);
        }
        return response('Webhook OK', 200);
    }

    public function successFallback(Request $request)
    {
        Stripe::setApiKey(config('services.stripe.secret'));
        $session = StripeSession::retrieve($request->session_id);
        $this->storePayment($session);
        return view('payments.success');
    }

    private function storePayment($session)
    {
        Log::info('Processing payment store', ['id' => $session->id]);
        $rowId = $session->metadata->row_id ?? null;
        if (!$rowId) return;
        $row = customer_subscriptions::find($rowId);
        if (!$row || $row->payment_status === 'paid') return;
        if (($session->payment_status ?? '') !== 'paid') {
            Log::warning('Stripe payment not confirmed yet', [
                'status' => $session->payment_status ?? null
            ]);
            return;
        }
        $row->update([
            'payment_method' => $session->payment_method_types[0] ?? null,
            'payment_status' => 'paid', 
            'currency'       => $session->currency ?? 'usd',
            'meta'           => json_encode($session)
        ]);
        Log::info('Payment stored successfully', [
            'row_id' => $row->id,
        ]);
    }
}
