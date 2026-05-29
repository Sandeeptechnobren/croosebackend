<?php
namespace App\Http\Controllers;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Stripe\Stripe;
use Stripe\PaymentIntent;
use App\Models\Transaction;
use App\Models\Space;
use App\Models\Client;
use Illuminate\Support\Facades\Auth;
use Stripe\Webhook;
use App\Models\Order;
use App\Models\Appointment;
use App\Models\Customer;
use App\Models\subscription;
use App\Models\customer_subscriptions;
use App\Models\Space_whapichannel_details;
use Stripe\Checkout\Session;
use App\Models\SpaceWhapiPaymentDetail;
use Stripe\Checkout\Session as StripeSession;
use App\Services\StripeWebhookHandler;
use Illuminate\Support\Facades\Http;
class TransactionController extends Controller
{
protected $handler;
public function __construct(StripeWebhookHandler $handler)
    {
    $this->handler = $handler;
    }
public function showOrderPaymentOptions($uuid)
    {
        $order = Order::with('product')->where('uuid', $uuid)->firstOrFail();
        $space=Space::where('id',$order->space_id)->firstOrFail();
        return view('payment.order-options', compact('uuid', 'order','space')); 
    }
public function showAppointmentPaymentOptions($uuid)
    {
        $appointment = Appointment::with('service')->where('uuid', $uuid)->firstOrFail();
        $space=Space::where('id',$appointment->space_id)->firstOrFail();
        return view('payment.appointment-options', compact('uuid', 'appointment','space'));
    }
public function showWhapiPaymentOptions($uuid)
    {
        $space = Space::where('uuid', $uuid)->firstOrFail();
        return view('payment.whapi-options', compact('uuid', 'space'));
    }
public function showSubscriptionPaymentOptions($uuid)
{
    $subscription = customer_subscriptions::with('service')->where('uuid', $uuid)->firstOrFail();
    $subscription_detail=subscription::where('id',$subscription->subscription_id)->first();
    $subscription_name=$subscription_detail->name;
    $subscription_amount=$subscription_detail->price;
    $currency=$subscription_detail->currency;
    $space = Space::findOrFail($subscription->space_id);
    return view('payment.subscriptions-option', compact('uuid', 'subscription','subscription_name','subscription_amount','currency','space'));
}

 
public function handleWebhook(Request $request)
{   
    Stripe::setApiKey(config('services.stripe.secret'));
    $endpoint_secret = config('services.stripe.webhook_secret');
    $payload = $request->getContent();
    $sig_header = $request->header('Stripe-Signature');
    try {
        $event =Webhook::constructEvent($payload, $sig_header, $endpoint_secret);
    } catch (\UnexpectedValueException $e) {
        return response('Invalid payload', 400);
    } catch (SignatureVerificationException $e) {
        return response('Invalid signature', 400);
    }

    if ($event->type === 'payment_intent.succeeded') {
        $intent = $event->data->object;
        $uuid = $intent->metadata->uuid ?? null;
        $type = $intent->metadata->type ?? null;
        $referenceId = $intent->metadata->reference_id ?? null;
        $payment_reference = $intent->id;
        $payment_method = $intent->payment_method ?? null;
        $payment_status = $intent->status;
        $currency = $intent->currency;
        $charge = $intent->charges->data[0] ?? null;
        $receipt_url = $charge->receipt_url ?? null;
        $transaction_id = $charge->id ?? null;
        $transaction_status = $charge->status ?? null;
        if ($type === 'order' && $uuid) {
            $order =Order::where('uuid', $uuid)->first();
            if ($order && $order->payment_status !== 'success') {
                $order->payment_status = $payment_status;
                $order->payment_reference = $payment_reference;
                $order->payment_origin = 'Stripe';
                $order->payment_method = $payment_method;
                $order->currency = $currency;
                $order->receipt_url = $receipt_url;
                $order->save();
                Transaction::where('reference_id', $payment_reference)->update([
                    'payment_origin' => 'Stripe',
                    'transaction_status' => $transaction_status,
                    'transaction_id' => $transaction_id,
                    'payment_method' => $payment_method,
                    'currency' => $currency,
                    'receipt_url' => $receipt_url,
                ]);
            }
        }
        if ($type === 'appointment' && $uuid) {
            $appointment =Appointment::where('uuid', $uuid)->first();
            if ($appointment && $appointment->payment_status !== 'success') {
                $appointment->payment_status = $payment_status;
                $appointment->payment_reference = $payment_reference;
                $appointment->payment_origin = 'Stripe';
                $appointment->payment_method = $payment_method;
                $appointment->currency = $currency;
                $appointment->receipt_url = $receipt_url;
                $appointment->save();
                Transaction::where('reference_id', $payment_reference)->update([
                    'payment_origin' => 'Stripe',
                    'transaction_status' => $transaction_status,
                    'transaction_id' => $transaction_id,
                    'payment_method' => $payment_method,
                    'currency' => $currency,
                    'receipt_url' => $receipt_url,
                ]);
            }
        }
        if($type==='instance' && $uuid){
            $instance=Space_whapichannel_details::where('uuid',$uuid)->first();
            if($instance && $instance->transaction_status!=='success'){
                $instance->payment_status = $payment_status;
                $instance->payment_reference = $payment_reference;
                $instance->payment_origin = 'Stripe';
                $instance->payment_method = $payment_method;
                $instance->save();
                SpaceWhapiPaymentDetail::where('reference_id', $payment_reference)->update([
                    'payment_origin' => 'Stripe',
                    'transaction_status' => $transaction_status,
                    'transaction_id' => $transaction_id,
                    'payment_method' => $payment_method,
                    'currency' => $currency,
                    'receipt_url' => $receipt_url,
                ]);
            }
        }
    } else {
        \Log::info("Ignored Stripe event: " . $event->type);
    }

    return response('Webhook handled', 200);
}


public function success(Request $request)
    {
        $sessionId = $request->get('session_id');
        if (!$sessionId) {
            return 'Session ID missing.';
        }
        Stripe::setApiKey(env('STRIPE_SECRET'));
        DB::beginTransaction();
        try {
            $session = StripeSession::retrieve($sessionId);
            $paymentIntent = PaymentIntent::retrieve($session->payment_intent);
              $charge = null;
                if ($paymentIntent->latest_charge) {
                    $charge = \Stripe\Charge::retrieve($paymentIntent->latest_charge);
                }

                 $currency = strtolower($paymentIntent->currency); // extract currency
 
                if ($currency !== 'ghs') {
                    DB::rollBack();
                    return redirect()->route('payment.success')->with('error', 'Payment is only allowed for Ghana (GHS) currency.');
                }

                $meta = [
                    'payment_intent_id'   => $paymentIntent->id,
                    'payment_method_id'   => $paymentIntent->payment_method,
                    'payment_method_type' => $paymentIntent->payment_method_types[0] ?? null,
                    'status'              => $paymentIntent->status,
                    'amount_received'     => $paymentIntent->amount_received / 100,
                    'currency'            => $paymentIntent->currency,
                    'charge_id'           => $paymentIntent->latest_charge,
                    'gateway'             => 'stripe',
                ];
        
                if ($charge) {
                    $details = $charge->payment_method_details;
                    $type = $details->type; 

                    switch ($type) {
                        case 'card':
                            $meta['card_brand'] = $details->card->brand ?? null;
                            $meta['last4'] = $details->card->last4 ?? null;
                            break;

                        case 'upi':
                            $meta['upi'] = [
                                'vpa' => $details->upi->vpa ?? null,
                            ];
                            break;

                        case 'mobile_money':
                            $meta['mobile_money'] = [
                                'network' => $details->mobile_money->network ?? null,
                                'phone_number' => $details->mobile_money->phone_number ?? null,
                            ];
                            break;

                        case 'wallet':
                            $meta['wallet'] = $details->wallet->type ?? null;
                            break;

                        case 'us_bank_account':
                            $meta['bank'] = [
                                'bank_name' => $details->us_bank_account->bank_name ?? null,
                                'last4'     => $details->us_bank_account->last4 ?? null,
                                'routing_number' => $details->us_bank_account->routing_number ?? null,
                            ];
                            break;
                            
                        default:
                            $meta['raw_details'] = $details;  
                    }

                    $meta['receipt_url'] = $charge->receipt_url ?? null;
                    $meta['invoice_url'] = $charge->invoice ?? null;
                }
              
                $order_details=$session->metadata;
                $reference = 'croose_' . uniqid();
                Transaction::create([
                    'client_id'          => $order_details->client_id ?? null,
                    'customer_id'        => $order_details->customer_id ?? null,
                    'type'               => $order_details->type,
                    'reference_id'       => $reference,
                    'amount'             => $session->amount_total / 100,
                    'currency'           => $session->currency,
                    'payment_method'     => $meta['payment_method_type'] ?? 'unknown',
                    'transaction_status' => $session->payment_status,
                    'transaction_id'     => $paymentIntent->id ?? null,
                    'stripe_session_id'  => $session->id,
                    'meta'               => json_encode($meta),
                    'invoice_url'        => $meta['invoice_url'] ?? null,
                    'receipt_url'        => $meta['receipt_url'] ?? null,
                    'paid_at'            => now(),
                    'payment_origin'     => "Stripe",
                ]);
            $customer_name=Customer::where('id',$order_details->customer_id)->first();
            DB::commit();
            return view('payment.success',compact('order_details','customer_name'));  
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->route('payment.success')->with('error', 'Payment already processed or something went wrong.',$e->getMessage());
        }
    }

public function cancel()
    {
        return view('payment.cancel');  
    }
   

public function payNow($uuid)
{
    $order = Order::with(['product:id,name,slug,description,price,unit,type,category,image'])->where('uuid', $uuid)->firstOrFail();
    $space = Space::findOrFail($order->space_id);
    $currency = strtoupper($space->currency); // e.g., 'GHS' or 'USD'
    $paymentStatus = $order->payment_status;
    if ($paymentStatus === 'success') {
        return response()->json([
            'status' => 200,
            'message' => 'Payment Already Done',
        ]);
    }
    $reference = 'croosestripe_' . uniqid();
    $order->payment_reference = $reference;
    $order->payment_origin = "Stripe";
    $baseAmount = $order->order_amount; 
    $exchangeRate = 1;
    $convertedAmount = $baseAmount;

    if ($currency !== 'USD') {
        try {
            $apiKey = 'd20fa341e260799a2339a543';
            $response = Http::get("https://v6.exchangerate-api.com/v6/{$apiKey}/latest/{$currency}");

            if ($response->successful()) {
                $rateData = $response->json();
                
                $exchangeRate = $rateData['conversion_rates']['USD'] ?? 1;
                $convertedAmount = round($baseAmount * $exchangeRate, 2); // now in USD
            } else {
                return response()->json([
                    'status' => 500,
                    'message' => 'Currency conversion failed. Please try again later.',
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Exchange API Error: ' . $e->getMessage(),
            ]);
        }
    }
    $order->save();
    $transaction=Transaction::create([
        'client_id' => $order->client_id,
        'customer_id' => $order->customer_id,
        'type' => 'Order',
        'reference_id' => $reference,
        'payment_origin' => 'Stripe',
        'amount' => $convertedAmount, // in USD
        'currency' => $currency,
        'amount' => $baseAmount,
        'paid_currency' => 'USD',
        'paid_amount' => $convertedAmount,
        'fx_rate' => $exchangeRate,
    ]); 
    Stripe::setApiKey(config('services.stripe.secret'));
    $checkoutSession = Session::create([
        'payment_method_types' => ['card'],
        'line_items' => [[
            'price_data' => [
                'currency' => 'usd',
                'unit_amount' => intval($convertedAmount * 100), // cents
                'product_data' => [
                    'name' => $order->product->name,
                    'description' => $order->product->description ?? 'No Description',
                ],
            ],
            'quantity' => 1,
        ]],
        'mode' => 'payment',
        'success_url' => route('payment.success', [], true) . '?session_id={CHECKOUT_SESSION_ID}',
        'cancel_url' => route('payment.cancel', [], true),
        'metadata' => [
            'uuid' => $uuid,
            'reference_id' => $reference,
            'customer_id' => $order->customer_id,
            'client_id' => $order->client_id,
            'type' => "order",
            'original_amount' => $baseAmount,
            'original_currency' => $currency,
        ],
    ]);

    return view('payment.checkout', compact('order', 'checkoutSession'));
}

public function payNow1($uuid)
{
    $appointment = Appointment::with(['service:id,price,name,duration_minutes,category,description'])
        ->where('uuid', $uuid)
        ->firstOrFail();

    $space = Space::findOrFail($appointment->space_id);
    $currency = strtoupper($space->currency); // e.g., GHS or USD
    $paymentStatus = $appointment->payment_status;

    if ($paymentStatus === 'success') {
        return response()->json([
            'status' => 200,
            'message' => 'Payment Already Done'
        ]);
    }
    $reference = 'croosestripe_' . uniqid();
    $appointment->payment_reference = $reference;
    $appointment->payment_origin = "Stripe";

    $baseAmount = $appointment->amount;
    $exchangeRate = 1;
    $convertedAmount = $baseAmount;
    if ($currency !== 'USD') {
        try {
            $apiKey = 'd20fa341e260799a2339a543';
            $response = Http::get("https://v6.exchangerate-api.com/v6/{$apiKey}/latest/{$currency}");

            if ($response->successful()) {
                $rateData = $response->json();
                $exchangeRate = $rateData['conversion_rates']['USD'] ?? 1;
                $convertedAmount = round($baseAmount * $exchangeRate, 2); // Convert to USD
            } else {
                return response()->json([
                    'status' => 500,
                    'message' => 'Currency conversion failed. Please try again later.',
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Exchange API Error: ' . $e->getMessage(),
            ]);
        }
    }
    $appointment->save();
    Transaction::create([
        'client_id' => $appointment->client_id,
        'customer_id' => $appointment->customer_id,
        'type' => 'Appointment',
        'reference_id' => $reference,
        'payment_origin' => 'Stripe',
        'amount' => $convertedAmount,
        'currency' => $currency,
        'amount' => $baseAmount,
        'paid_currency' => 'USD',
        'paid_amount' => $convertedAmount,
        'fx_rate' => $exchangeRate,
    ]);
    Stripe::setApiKey(config('services.stripe.secret'));
    $checkoutSession = Session::create([
        'payment_method_types' => ['card'],
        'line_items' => [[
            'price_data' => [
                'currency' => 'usd',
                'unit_amount' => intval($convertedAmount * 100),
                'product_data' => [
                    'name' => $appointment->service->name ?? 'Service Payment',
                    'description' => $appointment->service->description ?? 'Appointment Payment',
                ],
            ],
            'quantity' => 1,
        ]],
        'mode' => 'payment',
        'success_url' => route('payment.success', [], true),
        'cancel_url'  => route('payment.cancel', [], true),
        'metadata' => [
            'uuid' => $uuid,
            'reference_id' => $reference,
            'customer_id' => $appointment->customer_id,
            'client_id' => $appointment->client_id,
            'type' => "appointment",
            'original_amount' => $baseAmount,
            'original_currency' => $currency,
        ],
    ]);

    return view('payment.checkout', compact('appointment', 'checkoutSession'));
}


public function store_transaction(Request $request, $client_phone, $customer_phone)
    {
    $client = DB::table('clients')->where('phone_number', $client_phone)->first();
    $customer = DB::table('customers')->where('whatsapp_number', $customer_phone)->first();

    if (!$client || !$customer) {
        return response()->json([
            'message' => 'Client or Customer not found.'
        ], 404);
    }

    $clientid = $client->id;
    $customerid = $customer->id;
    $validated = $request->validate([
        'type'               => 'required|in:order,appointment,subscription',
        'reference_id'       => 'required|integer',
        'amount'             => 'required|numeric|min:0',
        'currency'           => 'nullable|string|max:10',
        'payment_method'     => 'nullable|string|max:50',
        'transaction_status' => 'required|in:pending,success,failed,refunded,cancelled',
        'transaction_id'     => 'required|string|max:100',
        'is_manual'          => 'nullable|boolean',
        'meta'               => 'nullable|array',
        'notes'              => 'nullable|string',
        'invoice_url'        => 'nullable|string',
        'whatsapp_msg_id'    => 'nullable|string|max:100',
        'paid_at'            => 'nullable|date',
    ]);
    $transaction = Transaction::create([
        'client_id'          => $clientid,
        'customer_id'        => $customerid,
        'type'               => $validated['type'],
        'reference_id'       => $validated['reference_id'],
        'amount'             => $validated['amount'],
        'currency'           => $validated['currency'] ?? 'USD',
        'payment_method'     => $validated['payment_method'] ?? null,
        'transaction_status' => $validated['transaction_status'],
        'transaction_id'     => $validated['transaction_id'],
        'is_manual'          => $validated['is_manual'] ?? false,
        'meta'               => $validated['meta'] ?? null,
        'notes'              => $validated['notes'] ?? null,
        'invoice_url'        => $validated['invoice_url'] ?? null,
        'whatsapp_msg_id'    => $validated['whatsapp_msg_id'] ?? null,
        'paid_at'            => $validated['paid_at'] ?? now(),
    ]);

    return response()->json([
        'message' => 'Transaction stored successfully.',
        'data'    => $transaction
    ], 201);
}

public function get_transaction(Request $request ,$client_phone, $customer_phone)
{
    $client = DB::table('clients')->where('phone_number', $client_phone)->first();
    $customer = DB::table('customers')->where('whatsapp_number', $customer_phone)->first();

    if (!$client || !$customer) {
        return response()->json([
            'message' => 'You must login first!',
            'success' => false
        ], 404);
    }

    $transactions = Transaction::where('client_id', $client->id)
        ->where('customer_id', $customer->id)
        ->get();

    return response()->json([
        'success' => true,
        'data' => $transactions,
        'message' => 'Transaction list fetched successfully.'
    ]);
}

public function payNowInstance($uuid)
{
    $whapiInstance = Space_whapichannel_details::where('uuid', $uuid)->firstOrFail();
    $space = Space::findOrFail($whapiInstance->space_id);
    $currency = strtoupper($space->currency); // e.g., GHS or USD
    $paymentStatus = $whapiInstance->payment_status;

    if ($paymentStatus === 'success') {
        return response()->json([
            'status' => 200,
            'message' => 'Payment Already Done'
        ]);
    }
    $reference = 'croosestripe_' . uniqid();
    $whapiInstance->payment_reference = $reference;
    $whapiInstance->payment_origin = "Stripe";

    $baseAmount = $whapiInstance->payment_amount; 
    $exchangeRate = 1;
    $convertedAmount = $baseAmount;

    if ($currency !== 'USD') {
        try {
            $apiKey = 'd20fa341e260799a2339a543';
            $response = Http::get("https://v6.exchangerate-api.com/v6/{$apiKey}/latest/{$currency}");

            if ($response->successful()) {
                $rateData = $response->json();
                $exchangeRate = $rateData['conversion_rates']['USD'] ?? 1;
                $convertedAmount = round($baseAmount * $exchangeRate, 2); // Convert to USD
            } else {
                return response()->json([
                    'status' => 500,
                    'message' => 'Currency conversion failed. Please try again later.',
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Exchange API Error: ' . $e->getMessage(),
            ]);
        }
    }
    $whapiInstance->save();
    $payment_details=SpaceWhapiPaymentDetail::create([
        'client_id'       => $whapiInstance->client_id,
        'space_id'        =>$whapiInstance->space_id,
        'type'            => 'WhapiInstance',
        'reference_id'    => $reference,
        'payment_origin'  => 'Stripe',
        'amount'          => $convertedAmount,
        'currency'        => 'USD',
    ]);
    Stripe::setApiKey(config('services.stripe.secret'));
    $checkoutSession = Session::create([
        'payment_method_types' => ['card'],
        'line_items' => [[
            'price_data' => [
                'currency' => 'usd',
                'unit_amount' => intval($convertedAmount * 100),
                'product_data' => [
                    'name' => $whapiInstance->service->name ?? 'Instance Payment',
                    'description' => $whapiInstance->service->description ?? 'Pay to get you Agent Active',
                ],
            ],
            'quantity' => 1,
        ]],
        'mode' => 'payment',
        'success_url' => route('payment.success', [], true),
        'cancel_url'  => route('payment.cancel', [], true),
        'metadata' => [
            'uuid'              => $uuid,
            'reference_id'      => $reference,
            'customer_id'       => $whapiInstance->customer_id,
            'client_id'         => $whapiInstance->client_id,
            'type'              => "instance",
            'original_amount'   => $baseAmount,
            'original_currency' => $currency,
        ],
    ]);

    return view('payment.checkout', [
        'instance' => $whapiInstance,
        'checkoutSession' => $checkoutSession
    ]);
}




 
}