<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Stripe\Stripe;
use Stripe\PaymentIntent;
class Stripe extends Controller
{ 
    public function create_payment(Request $request){
        Stripe::setApiKey(env('STRIPE_SECRET'));
        $amount=$request->input('amount');
        try{
            $paymentIntent = PaymentIntent::create([
                'amount' => $amount * 100,
                'currency' => 'usd', 
                'metadata' => [
                    'integration_check' => 'accept_a_payment',
                    'customer_id' => $request->input('customer_id'),
                ],
            ]);
            return response()->json([
                'clientSecret' => $paymentIntent->client_secret,
                'message' => 'PaymentIntent (USD) created successfully.',
            ]);
        }
        catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
