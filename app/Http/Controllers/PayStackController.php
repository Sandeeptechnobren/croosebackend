<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\Order;
use App\Models\Space;
use App\Models\Transaction;
use App\Models\Appointment;
use App\Models\Customer;
use App\Models\subscription;
use App\Models\subscription_transaction;
use App\Models\customer_subscriptions;
use App\Models\Client;
use App\Jobs\VerifyPaystackPayment;
use App\Models\Space_whapichannel_details;
use App\Models\SpaceWhapiPaymentDetail;
class PayStackController extends Controller
{

public function initializeOrder($uuid)
{
    $order = Order::with(['product:id,name,slug,description,price,unit,type,category,image'])
        ->where('uuid', $uuid)
        ->firstOrFail();
    $space = Space::findOrFail($order->space_id);
    $currency = strtoupper($space->currency);
    $paymentStatus = $order->payment_status;
    if ($paymentStatus === 'success') {
        return response()->json([
            'status' => 200,
            'message' => 'Payment Already Done',
        ]);
    }
    $reference = 'croosepaystack_' . uniqid();
    $order->payment_reference = $reference;
    $order->payment_origin = "Paystack";
    $baseAmount = $order->order_amount; 
    $exchangeRate = 1;
    $convertedAmount = $baseAmount; 
    if ($currency !== 'GHS') {
        try {
            $apiKey = 'd20fa341e260799a2339a543';
            $response = Http::get("https://v6.exchangerate-api.com/v6/{$apiKey}/latest/{$currency}");
            if ($response->successful()) {
                $rateData = $response->json();
                $exchangeRate = $rateData['conversion_rates']['GHS'] ?? null;
                if (!$exchangeRate) {
                    return response()->json(['error' => 'Currency conversion unavailable'], 500);
                }
                $convertedAmount = round($baseAmount * $exchangeRate, 2);
            } else {
                return response()->json(['error' => 'Currency API call failed'], 500);
            }
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Currency API Error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
    $order->save();
    $customer = Customer::findOrFail($order->customer_id);
    $customer_email = $customer->email; 
    $transaction=Transaction::create([
        'client_id' => $order->client_id,
        'customer_id' => $order->customer_id,
        'type' => 'Order',
        'reference_id' => $reference,
        'payment_origin' => 'Pay Stack',
        'amount' => $convertedAmount,
        'currency' => $currency,
        'amount' => $baseAmount,
        'paid_currency' => 'GHS',
        'paid_amount' => $convertedAmount,
        'fx_rate' => $exchangeRate,
    ]);
    $paystackData = [
        'email' => $customer_email,
        'amount' => intval($convertedAmount * 100),
        'reference' => $reference,
        'currency' => 'GHS',
        'metadata' => [
            'order_uuid' => $uuid,
            'original_amount' => $baseAmount,
            'original_currency' => $currency,
        ],
    ];
    try {
        $response = Http::withToken(env('PAYSTACK_SECRET_KEY'))
            ->post('https://api.paystack.co/transaction/initialize', $paystackData);
        if ($response->successful()) {
            $authorizationUrl = $response->json()['data']['authorization_url'];
            return redirect()->away($authorizationUrl);
        }
        return response()->json(['error' => 'Payment initialization failed'], 500);
    } catch (\Exception $e) {
        return response()->json([
            'error' => 'Server error',
            'message' => $e->getMessage()
        ], 500);
    }
}

public function initializeAppointment($uuid)
{
    $appointment = Appointment::with(['service:id,price,name,duration_minutes,category,description'])
        ->where('uuid', $uuid)
        ->firstOrFail();
        
    $space = Space::findOrFail($appointment->space_id);
    $currency = strtoupper($space->currency);
    $paymentStatus = $appointment->payment_status;

    if ($paymentStatus === 'success') {
        return response()->json([
            'status' => 200,
            'message' => 'Payment Already Done',
        ]);
    }

    $reference = 'croosepaystack_' . uniqid();
    $appointment->payment_reference = $reference;
    $appointment->payment_origin = "Paystack";
    $baseAmount = $appointment->amount;
    $exchangeRate = 1;
    $convertedAmount = $baseAmount;
    if ($currency !== 'GHS') {
        try {
            $apiKey = 'd20fa341e260799a2339a543';
            $response = Http::get("https://v6.exchangerate-api.com/v6/{$apiKey}/latest/{$currency}");

            if ($response->successful()) {
                $rateData = $response->json();
                $exchangeRate = $rateData['conversion_rates']['GHS'] ?? null;

                if (!$exchangeRate) {
                    return response()->json(['error' => 'Currency conversion unavailable'], 500);
                }

                $convertedAmount = round($baseAmount * $exchangeRate, 2);
            } else {
                return response()->json(['error' => 'Currency API call failed'], 500);
            }
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Currency API Error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
    $appointment->save();
    $customer = Customer::findOrFail($appointment->customer_id);
    $customer_email = $customer->email;
    Transaction::create([
        'client_id' => $appointment->client_id,
        'customer_id' => $appointment->customer_id,
        'type' => 'Appointment',
        'reference_id' => $reference,
        'payment_origin' => 'Paystack',
        'amount' => $convertedAmount,
        'currency' => $currency,
        'amount' => $baseAmount,
        'paid_currency' => 'GHS',
        'paid_amount' => $convertedAmount, 
        'fx_rate' => $exchangeRate,
    ]);
    $paystackData = [
        'email' => $customer_email,
        'amount' => intval($convertedAmount * 100),
        'reference' => $reference,
        'currency' => 'GHS',
        'metadata' => [
            'appointment_uuid' => $uuid,
            'original_amount' => $baseAmount,
            'original_currency' => $currency,
        ],
    ];

    try {
        $response = Http::withToken(env('PAYSTACK_SECRET_KEY'))
            ->post('https://api.paystack.co/transaction/initialize', $paystackData);

        if ($response->successful()) {
            $authorizationUrl = $response->json()['data']['authorization_url'];
            return redirect()->away($authorizationUrl);
        }

        return response()->json(['error' => 'Payment initialization failed'], 500);
    } catch (\Exception $e) {
        return response()->json([
            'error' => 'Server error',
            'message' => $e->getMessage()
        ], 500);
    }
}

// public function initializeWhapi($uuid)
// {
//     $space = Space::where('uuid', $uuid)->firstOrFail();
//     $space_id = $space->id;
//     $whapiInstance = Space_whapichannel_details::where('space_id', $space_id)->firstOrFail();
//     $currency = strtoupper($space->currency);
//     $baseAmountUSD = 199;
//     if ($whapiInstance->payment_status === 'success') {
//         return response()->json([
//             'status' => 200,
//             'message' => 'Payment Already Done',
//         ]);
//     }
//     $reference = 'croosepaystack_' . uniqid();
//     $whapiInstance->payment_reference = $reference;
//     $whapiInstance->payment_origin = "Pay Stack";
    
//     $convertedAmount = $baseAmountUSD;
//     $finalCurrency = 'USD';
    
//     if ($currency === 'GHS') {
//         try {
//             $apiKey = 'd20fa341e260799a2339a543';
//             $response = Http::get("https://v6.exchangerate-api.com/v6/{$apiKey}/latest/USD");
//             if ($response->successful()) {
//                 $rateData = $response->json();
//                 $exchangeRate = $rateData['conversion_rates']['GHS'] ?? null;
//                 if (!$exchangeRate) {
//                     return response()->json([
//                         'status' => 500,
//                         'message' => 'Currency conversion unavailable',
//                     ]);
//                 }
//                 $convertedAmount = round($baseAmountUSD * $exchangeRate, 2);
//                 $finalCurrency = 'GHS';
//             } else {
//                 return response()->json([
//                     'status' => 500,
//                     'message' => 'Currency API call failed',
//                 ]);
//             }
//         } catch (\Exception $e) {
//             return response()->json([
//                 'status' => 500,
//                 'message' => 'Currency API Error: ' . $e->getMessage(),
//             ]);
//         }
//     }
//     $whapiInstance->payment_amount=$convertedAmount;
//     $whapiInstance->save();
//     $client = Client::findOrFail($whapiInstance->client_id);
//     $clientEmail = $client->email;
//     $spaceWhapi=SpaceWhapiPaymentDetail::create([
//         'client_id'     => $whapiInstance->client_id,
//         'space_id'      => $whapiInstance->space_id,
//         'type'          => 'Whapi_instance',
//         'reference_id'  => $reference,
//         'payment_origin'=> 'Pay Stack',
//         'amount'        => $convertedAmount,
//         'currency'      => $finalCurrency,
//     ]);
//     $paystackData = [
//         'email'     => $clientEmail,
//         'amount'    => intval($convertedAmount * 100),
//         'reference' => $reference,
//         'currency'  => $finalCurrency,
//         'metadata'  => [
//             'whapi_uuid'        => $uuid,
//             'original_amount'   => $baseAmountUSD,
//             'original_currency' => 'USD',
//         ],
//     ];
//     try {
//         $response = Http::withToken(env('PAYSTACK_SECRET_KEY'))
//             ->post('https://api.paystack.co/transaction/initialize', $paystackData);
//         if ($response->successful()) {
//             $authorizationUrl = $response->json()['data']['authorization_url'];
//             return redirect()->away($authorizationUrl);
//         }
//         return response()->json([
//             'status' => 500,
//             'message' => 'Payment initialization failed',
//         ]);
//     } catch (\Exception $e) {
//         return response()->json([
//             'status' => 500,
//             'message' => 'Server error: ' . $e->getMessage(),
//         ]);
//     }
// }
public function initializeWhapi($uuid)
{
    $space = Space::where('uuid', $uuid)->firstOrFail();
    $space_id = $space->id;
    $whapiInstance = Space_whapichannel_details::where('space_id', $space_id)->firstOrFail();
    $currency = strtoupper($space->currency);
    
    if ($whapiInstance->payment_status === 'success') {
        return response()->json([
            'status' => 200,
            'message' => 'Payment Already Done',
        ]);
    }
    $reference = 'croosepaystack_' . uniqid();
    $whapiInstance->payment_reference = $reference;
    $whapiInstance->payment_origin = "Pay Stack";
    $convertedAmount = 199;
    $finalCurrency   = 'USD';
    if ($currency === 'GHS') {
        $convertedAmount = 2000;
        $finalCurrency   = 'GHS';
    }
    if($currency==='INR'){
        $convertedAmount = 15500;
        $finalCurrency   = 'GHS';
    }
    $whapiInstance->payment_amount = $convertedAmount;
    $whapiInstance->save();
    $client = Client::findOrFail($whapiInstance->client_id);
    $clientEmail = $client->email;
    $spaceWhapi = SpaceWhapiPaymentDetail::create([
        'client_id'     => $whapiInstance->client_id,
        'space_id'      => $whapiInstance->space_id,
        'type'          => 'Whapi_instance',
        'reference_id'  => $reference,
        'payment_origin'=> 'Pay Stack',
        'amount'        => $convertedAmount,
        'currency'      => $finalCurrency,
    ]);
    $paystackData = [
        'email'     => $clientEmail,
        'amount'    => intval($convertedAmount * 100),
        'reference' => $reference,
        'currency'  => $finalCurrency,
        'metadata'  => [
            'whapi_uuid'        => $uuid,
            'original_amount'   => ($currency === 'GHS') ? 2000 : 199,
            'original_currency' => $finalCurrency,
        ],
    ];
    try {
        $response = Http::withToken(env('PAYSTACK_SECRET_KEY'))
            ->post('https://api.paystack.co/transaction/initialize', $paystackData);
        if ($response->successful()) {
            $authorizationUrl = $response->json()['data']['authorization_url'];
            return redirect()->away($authorizationUrl);
        }
        return response()->json([
            'status' => 500,
            'message' => 'Payment initialization failed',
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 500,
            'message' => 'Server error: ' . $e->getMessage(),
        ]);
    }
}


public function initializeSubscription($uuid)
{
    $customer_subscriptions = customer_subscriptions::where('uuid', $uuid)->firstOrFail();
    $subscription=subscription::where('id',$customer_subscriptions->subscription_id)->first();
    $space = Space::findOrFail($customer_subscriptions->space_id);
    $currency = strtoupper($subscription->currency);
    $paidCurrency=$currency;
    $baseAmount = $subscription->price;
    $convertedAmount=$baseAmount;
    $existingTransaction = subscription_transaction::where('customer_subscription_id', $customer_subscriptions->id)
        ->where('transaction_status', 'success')
        ->first();
    if ($existingTransaction) {
        return response()->json([
            'status'  => 200,
            'message' => 'Payment Already Done',
        ]);
    }
    $reference = 'croosepaystack_' . uniqid();
    $customer_subscriptions->payment_reference = $reference;
    $customer_subscriptions->payment_origin = "Paystack";
    $customer_subscriptions->save();
    $exchangeRate = 1;
    if ($currency !== 'GHS') {
        try {
            $apiKey = 'd20fa341e260799a2339a543';
            $response = Http::get("https://v6.exchangerate-api.com/v6/{$apiKey}/latest/{$currency}");

            if ($response->successful()) {
                $rateData = $response->json();
                $exchangeRate = $rateData['conversion_rates']['GHS'] ?? null;
                if (!$exchangeRate) {
                    return response()->json(['error' => 'Currency conversion unavailable'], 500);
                }
                $convertedAmount = round($baseAmount * $exchangeRate, 2);
                $paidCurrency = 'GHS';
            } else {
                return response()->json(['error' => 'Currency API call failed'], 500);
            }
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Currency API Error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
    $customer = Customer::where('id',$customer_subscriptions->customer_id)->first();
    $customer_email=$customer->email;
    $transaction = subscription_transaction::create([
        'customer_subscription_id' => $customer_subscriptions->id,
        'client_id'                => $customer_subscription->client_id,
        'space_id'                 => $customer_subscription->space_id,
        'reference_id'             => $reference,
        'amount'                   => $baseAmount,
        'currency'                 => $currency,
        'payment_gateway'          => 'Paystack',
        'transaction_status'       => 'pending',
        'transaction_date'         => now(),
        'paid_currency'            => $paidCurrency,
        'paid_amount'              => $convertedAmount,
        'fx_rate'                  => $exchangeRate,
        'raw_payload'              => null,
    ]);
    $paystackData = [
        'email'     => $customer_email,
        'amount'    => intval($convertedAmount * 100),
        'reference' => $reference,
        'currency'  => $paidCurrency,
        'metadata'  => [
            'subscription_uuid' => $uuid,
            'transaction_id'    => $transaction->id,
            'original_amount'   => $baseAmount,
            'original_currency' => $currency,
        ],
    ];
    try {
        $response = Http::withToken(env('PAYSTACK_SECRET_KEY_test'))
            ->post('https://api.paystack.co/transaction/initialize', $paystackData);

        if ($response->successful()) {
            $authorizationUrl = $response->json()['data']['authorization_url'];
            return redirect()->away($authorizationUrl);
        }

        return response()->json(['error' => 'Payment initialization failed'], 500);

    } catch (\Exception $e) {
        return response()->json([
            'error' => 'Server error',
            'message' => $e->getMessage()
        ], 500);
    }
}


public function handleCallback(Request $request)
{
    $reference = $request->query('reference');
    $response = Http::withToken(env('PAYSTACK_SECRET_KEY'))
        ->get('https://api.paystack.co/transaction/verify/' . $reference);
    if ($response->successful() && $response['data']['status'] === 'success') {
        $checkoutSession = $response['data'];
        return view('payment.checkout', compact('order', 'checkoutSession'));
    }
    return response()->json(['error' => 'Payment verification failed.'], 400);
}
public function webhook(Request $request)
    {
        $payload = $request->getContent();
        $signature = $request->header('x-paystack-signature');
        $secret = env('PAYSTACK_SECRET_KEY');    
        if (hash_hmac('sha512', $payload, $secret) !== $signature) {
            return response()->json(['error' => 'Invalid signature'], 400);
        }
        $event = $request->input('event');
        $data = $request->input('data');
        $transaction_id=$data['id']??'unknown';
        $status = $data['status'] ?? 'unknown';
        $payment_method = $data['channel'] ?? 'unknown';
        $reference = $data['reference'];
        $currency = $data['currency'] ?? 'NGN';
        if ($reference) {
            $order = Order::where('payment_reference', $reference)->first();
            if ($order) {
                $order->payment_status = $status;
                $order->payment_method = $payment_method;
                $order->save();
            }
            $appointment=Appointment::where('payment_reference',$reference)->first();
            if($appointment){
                $appointment->payment_status = $status;
                $appointment->payment_method = $payment_method;
                $appointment->save();
            }
            $whapi_instant=Space_whapichannel_details::where('payment_reference',$reference)->first();
            if($whapi_instant){
                $whapi_instant->payment_status=$status;
                $whapi_instant->payment_method=$payment_method;
                $whapi_instant->save();
            }
            $transaction = Transaction::where('reference_id',$reference)->first();
            if($transaction){
                $transaction->transaction_status=$status;
                $transaction->payment_method=$payment_method;
                $transaction->currency=$currency;
                $transaction->transaction_id=$transaction_id;
                $transaction->receipt_url = "https://api.paystack.co/transaction/verify/{$reference}";
                $transaction->save();
            }
            $SpaceWhapiPaymentDetail=SpaceWhapiPaymentDetail::where('reference_id',$reference)->first();
            if($SpaceWhapiPaymentDetail){
                $SpaceWhapiPaymentDetail->transaction_status=$status;
                $SpaceWhapiPaymentDetail->payment_method=$payment_method;
                $SpaceWhapiPaymentDetail->currency=$currency;
                $SpaceWhapiPaymentDetail->transaction_id=$transaction_id;
                $SpaceWhapiPaymentDetail->receipt_url = "https://api.paystack.co/transaction/verify/{$reference}";
                $SpaceWhapiPaymentDetail->save(); 
            }
            $customer_subscription=customer_subscriptions::where('payment_reference',$reference)->first();
            if($customer_subscription){
                $customer_subscription->payment_status=$status;
                $customer_subscription->payment_method=$payment_method;
                $customer_subscription->save(); 
            }
            $subscription_transaction=subscription_transaction::where('reference_id',$reference)->first();
            if($subscription_transaction){
                $subscription_transaction->transaction_status=$status;
                $subscription_transaction->payment_method=$payment_method;
                $subscription_transaction->paid_currency=$currency;
                $subscription_transaction->transaction_id=$transaction_id;
                $subscription_transaction->save();

            }
        }
        return response()->json(['message' => 'Webhook processed']);
    }
public function webhook_test(Request $request)
    {
        $payload = $request->getContent();
        $signature = $request->header('x-paystack-signature');
        if (hash_hmac('sha512', $payload, env('PAYSTACK_SECRET_KEY_test')) !== $signature) {
            return response()->json(['error' => 'Invalid signature'], 400);
        }
        $event = $request->input('event');
        $data = $request->input('data');
        $transaction_id=$data['id']??'unknown';
        $status = $data['status'] ?? 'unknown';
        $payment_method = $data['channel'] ?? 'unknown';
        $reference = $data['reference'];
        $currency = $data['currency'] ?? 'NGN';
        if ($reference) {
            $order = Order::where('payment_reference', $reference)->first();
            if ($order) {
                $order->payment_status = $status;
                $order->payment_method = $payment_method;
                $order->save();
            }
            $appointment=Appointment::where('payment_reference',$reference)->first();
            if($appointment){
                $appointment->payment_status = $status;
                $appointment->payment_method = $payment_method;
                $appointment->save();
            }
            $whapi_instant=Space_whapichannel_details::where('payment_reference',$reference)->first();
            if($whapi_instant){
                $whapi_instant->payment_status=$status;
                $whapi_instant->payment_method=$payment_method;
                $whapi_instant->save();
            }
            $transaction = Transaction::where('reference_id',$reference)->first();
            if($transaction){
                $transaction->transaction_status=$status;
                $transaction->payment_method=$payment_method;
                $transaction->currency=$currency;
                $transaction->transaction_id=$transaction_id;
                $transaction->receipt_url = "https://api.paystack.co/transaction/verify/{$reference}";
                $transaction->save();
            }
            $SpaceWhapiPaymentDetail=SpaceWhapiPaymentDetail::where('reference_id',$reference)->first();
            if($SpaceWhapiPaymentDetail){
                $SpaceWhapiPaymentDetail->transaction_status=$status;
                $SpaceWhapiPaymentDetail->payment_method=$payment_method;
                $SpaceWhapiPaymentDetail->currency=$currency;
                $SpaceWhapiPaymentDetail->transaction_id=$transaction_id;
                $SpaceWhapiPaymentDetail->receipt_url = "https://api.paystack.co/transaction/verify/{$reference}";
                $SpaceWhapiPaymentDetail->save(); 
            }
            $customer_subscription=customer_subscriptions::where('payment_reference',$reference)->first();
            if($customer_subscription){
                $customer_subscription->payment_status=$status;
                $customer_subscription->payment_method=$payment_method;
                $customer_subscription->save(); 
            }
            $subscription_transaction=subscription_transaction::where('reference_id',$reference)->first();
            if($subscription_transaction){
                $subscription_transaction->transaction_status=$status;
                $subscription_transaction->payment_method=$payment_method;
                $subscription_transaction->paid_currency=$currency;
                $subscription_transaction->transaction_id=$transaction_id;
                $subscription_transaction->save();

            }
        }
        return response()->json(['message' => 'Webhook processed']);
    }
}
