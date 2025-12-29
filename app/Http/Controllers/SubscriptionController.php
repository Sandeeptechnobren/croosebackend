<?php
namespace App\Http\Controllers;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Client; 
use App\Models\Space; 
use App\Models\subscription; 
use App\Models\Customer;
use App\Models\ClientCustomer; 
use App\Models\customer_subscriptions;
use App\Models\Space_whapichannel_details;
use Illuminate\Support\Facades\DB;
use App\Models\subscription_items;
use Carbon\Carbon;

use Illuminate\Http\Request; 

class SubscriptionController extends Controller
{
public function getSubscriptionlist(Request $request)
    {
        $client_id = Auth::user()->id;
        $subscriptions = Subscription::where('client_id', $client_id)
            ->with(['space:id,name'])
            ->get()
            ->map(function ($subscription) {
                return [
                    'id'                => $subscription->id,
                    'space_name'        => $subscription->space ? $subscription->space->name : null,
                    'name'              => $subscription->name,
                    'subscription_type' => $subscription->subscription_type,
                    'description'    => $subscription->description,
                    'variant'           => $subscription->variant,
                    'price'             => $subscription->price,
                    'status'            => $subscription->status,
                ];
            });
        return response()->json([
            'status'             => 200,
            'message'            => 'Subscriptions List fetched Successfully',
            'subscriptions_list' => $subscriptions
        ]);
    }
public function checkName(Request $request)
    {
        $client_id = Auth::user()->id;
        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);
        $exists = Subscription::where('client_id', $client_id)->where('name', $validated['name'])->exists();
        if ($exists) {
            return response()->json([
                'status'  => 409,
                'message' => 'You already have a subscription with this name , please use a different name.',
                'exists'  => true
            ]);
        } else {
            return response()->json([
                'status'  => 200,
                'message' => 'Name is available.',
                'exists'  => false
            ]);
        }
    }
public function createsubscription(Request $request)
{
    try {
        $client_id = Auth::user()->id;
        
        $validated = $request->validate([
            'space_id'          => 'required|exists:spaces,id',
            'subscription_name' => 'required|string|max:255',
            'subscription_type' => 'required|in:General,Product,Service',
            'description'       => 'required|string',
            'variant'           => 'required|in:monthly,yearly',
            'currency'          => 'nullable|string|max:10',
            'price_per_month'   => 'required|numeric|min:0',
            'access_type'       => 'nullable|in:discount,free_access,pay_individually',
            'discount_rate'     => 'nullable|numeric|min:0|max:100',
            'product_ids'       => 'array',
            'product_ids.*'     => 'integer|exists:products,id',
            'service_ids'       => 'array',
            'service_ids.*'     => 'integer|exists:services,id',
        ]);
        $validated['access_type']   = $validated['access_type'] ?? 'discount';
        $validated['discount_rate'] = $validated['discount_rate'] ?? 0;
        $exists = Subscription::where('client_id', $client_id)->where('name', $validated['subscription_name'])->exists();
        if ($exists) {
            return response()->json([
                'status'  => 409,
                'message' => 'You already have a subscription with this name , please use a different name.',
                'exists'  => true
            ]);
        }
        $space = Space::where('client_id', $client_id)
            ->where('id', $validated['space_id'])
            ->first();
        if (!$space) {
            return response()->json([
                'message' => 'This space does not belong to the logged-in user.'
            ], 403);
        }
        $subscription_items = [];
        DB::beginTransaction();
        try {
            $subscription = Subscription::create([
                'space_id'          => $validated['space_id'],
                'client_id'         => $client_id,
                'name'              => $validated['subscription_name'],
                'description'       => $validated['description'],
                'subscription_type' => $validated['subscription_type'],
                'variant'           => $validated['variant'],
                'price'             => $validated['price_per_month'],
                'currency'          => $validated['currency'] ?? 'USD',
                'access_type'       => $validated['access_type'],
                'discount_rate'     => $validated['discount_rate'],
            ]);
            if ($validated['subscription_type'] === 'product' && !empty($validated['product_ids'])) {
                foreach ($validated['product_ids'] as $pid) {
                    $subscription_items[] = subscription_items::create([
                        'subscription_id' => $subscription->id,
                        'item_id'         => $pid,
                        'item_type'       => 'product',
                    ]);
                }
            }
            if ($validated['subscription_type'] === 'service' && !empty($validated['service_ids'])) {
                foreach ($validated['service_ids'] as $sid) {
                    $subscription_items[] = subscription_items::create([
                        'subscription_id' => $subscription->id,
                        'item_id'         => $sid,
                        'item_type'       => 'service',
                    ]);
                }
            }
            DB::commit();

            //sending messages to the customers whatsapp
            $customers_list = ClientCustomer::where('client_id', $client_id)->where('space_id', $validated['space_id'])->pluck('customer_id');
            $space_whapi_token=Space_whapichannel_details::where('client_id', $client_id)->where('space_id', $validated['space_id'])->value('token');
            $client = new \GuzzleHttp\Client();
            foreach ($customers_list as $customer_id) {
            $customer = Customer::find($customer_id);
            if ($customer && $customer->whatsapp_number) {
            try {
            $phonenumber = preg_replace('/\D/', '', $customer->whatsapp_number);
            $response = $client->request('POST', 'https://gate.whapi.cloud/messages/text', [
                'body' => json_encode([
                    "typing_time" => 0,
                    "to"          => "{$phonenumber}@s.whatsapp.net",
                    "body"        => "NEW UPDATE🚨 {$customer->name}! Our brand new {$validated['subscription_name']}  Subscription is now available to you. Be among the first to become a member to enjoy exclusive benefits🚀",
                ]),
                'headers' => [
                    'accept'        => 'application/json',
                    'authorization' => "Bearer {$space_whapi_token}",
                    'content-type'  => 'application/json',
                ],
            ]);
            \Log::info("Message sent to {$customer->whatsapp_number}", [
                'response' => $response->getBody()->getContents(),
            ]);

        } catch (\Exception $e) {
            \Log::error("Failed to send message to {$customer->whatsapp_number}", [
                'error' => $e->getMessage(),
            ]);
            continue;
        }
    }   
    }
            return response()->json([
                'message'             => 'Subscription created successfully!',
                'subscription_type'   => $validated['subscription_type'],
                'subscription_items'  => $subscription_items,
            ], 201);


        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    } catch (\Illuminate\Validation\ValidationException $e) {
        return response()->json([
            'message' => 'Validation failed',
            'errors'  => $e->errors(),
        ], 422);
    } catch (\Exception $e) {
        return response()->json([
            'message' => 'Something went wrong while creating subscription',
            'error'   => $e->getMessage(),
        ], 500);
    }
}

public function subscribers_list(Request $response)
    {
    $client_id = Auth::user()->id;
    $subscribers = customer_subscriptions::with([
            'customer:id,name',
            'subscription:id,price,currency,name'
        ])
        ->where('client_id', $client_id)
        ->get()
        ->map(function ($subscription) {
            return [
                'id'                  => $subscription->id,
                'status'              => $subscription->status,
                'start_date' => $subscription->start_date? \Carbon\Carbon::parse($subscription->start_date)->format('d M Y h:i A') : null,
                'end_date'   => $subscription->end_date? \Carbon\Carbon::parse($subscription->end_date)->format('d M Y h:i A') : null,
                'customer_name'       => $subscription->customer?->name,
                'subscription_name'   => $subscription->subscription?->name,
                'subscription_amount' => $subscription->subscription 
                                            ? $subscription->subscription->price.' '.$subscription->subscription->currency 
                                            : null,
            ];
        });

    return response()->json([
        'status'  => 200,
        'subscribers' => $subscribers,
        'message' => 'Subscribers List fetched successfully'
    ]);
}

public function subscriber_statistics(Request $request){
    $client_id=Auth::user()->id;
    $total_subscription=Subscription::where('client_id',$client_id)->count();
    $active_subscription=Subscription::where('client_id',$client_id)->where('status','active')->count();
    $expired_subscription=Subscription::where('client_id',$client_id)->where('status','inactive')->count();
    $total_subscribers=customer_subscriptions::where('client_id',$client_id)->count();
    return response()->json([
        'status'=>200,
        'total_subscription'=>$total_subscription,
        'active_subscription'=>$active_subscription,
        'expired_subscription'=>$expired_subscription,
        'total_subscribers'=>$total_subscribers,
    ]);
}

}
