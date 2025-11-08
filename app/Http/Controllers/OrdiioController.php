<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use App\Models\OrdiioUser;
use App\Models\Licensed_track;
use App\Models\ordiio_license_categories;
use App\Models\ordiio_license_purchases;
use App\Models\OrdioCheckoutSession;
use App\Models\ordiio_track_download_project_details;
use App\Models\Ordiio_transaction;
use Stripe\Stripe;
use Stripe\Webhook;
use Stripe\Event;
use Stripe\Checkout\Session;
use Laravel\Sanctum\PersonalAccessToken;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Services\SonicSearchService;
class OrdiioController extends Controller
{
public function registerUser(Request $request){
        DB::beginTransaction();
        try {
            $validated = $request->validate([
                'first_name'     => 'required|string',
                'last_name'     => 'required|string',
                'email'    => 'required|email|unique:users,email',
                'password' => 'required|string|min:8'
            ]);
            $is_registered=OrdiioUser::where('email',$validated['email'])->exists();
            if($is_registered){
            return response()->json([
                'status'=>201,
                'message'=>'Email already exists',
                ]);
            }
            $user = OrdiioUser::create([
                'first_name'     => $validated['first_name'] ?? null,
                'last_name'     => $validated['last_name'] ?? null,
                'email'    => $validated['email'],
                'password' => Hash::make($validated['password']),
            ]);
            DB::commit();
            return response()->json([
                'status'  => 201,
                'message' => 'User registered successfully',
                'data'    => $user
            ], 201);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'status'  => 500,
                'message' => 'Unable to register',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

public function loginUser(Request $request)
{
    $request->validate([
        'email' => 'required|email',
        'password' => 'required|string'
    ]);
    $user = OrdiioUser::select('id', 'first_name','last_name', 'email','password','created_at','is_subscriber','company_type')->where('email', $request->email)->first();
    if (!$user || !Hash::check($request->password, $user->password)) {
        return response()->json([
            'status'  => 401,
            'message' => 'Invalid credentials'
        ], 401);
    }
    $is_licensed=ordiio_license_purchases::where('customer_id', $user->id)
        ->where('status', 'complete')
        ->exists();
    $token = $user->createToken('auth_token')->plainTextToken;
    return response()->json([
        'status'       => 200,
        'message'      => 'Login successful',
        'access_token' => $token,
        'token_type'   => 'Bearer',
        'user'         => $user,
        'is_subscriber'=>$user->is_subscriber,
        'member_since'  => $user->created_at->year,
        'company_type'  =>$user->company_type,
    ]);
}
public function forgotPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email'
        ]);
        $status = Password::sendResetLink(
            $request->only('email')
        );
        if ($status === Password::RESET_LINK_SENT) {
            return response()->json([
                'status'  => 200,
                'message' => 'Password reset link sent to your email'
            ]);
        }
        return response()->json([
            'status'  => 500,
            'message' => 'Unable to send reset link'
        ], 500);
    }
    

public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json([
            'status'  => 200,
            'message' => 'Logged out successfully'
        ]);
    }
public function verifyToken(Request $request)
    {
        $authHeader = $request->header('Authorization');
        if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
            return response()->json(['message' => 'Token missing'], 401);
        }
        $fullToken = str_replace('Bearer ', '', $authHeader);
        if (!str_contains($fullToken, '|')) {
            return response()->json(['message' => 'Malformed token'], 401);
        }
        [$id, $plainToken] = explode('|', $fullToken, 2);
        $tokenRecord = PersonalAccessToken::find($id);
        if (
            $tokenRecord &&
            hash_equals($tokenRecord->token, hash('sha256', $plainToken)) 
        ) {
            return response()->json([
                'message' => 'Token is valid',
                'user_id' => $tokenRecord->tokenable_id,
                'user_type' => $tokenRecord->tokenable_type,
            ]);
        }
        return response()->json(['message' => 'Invalid token'], 401);
    }
public function getUserData(Request $request){
    $userEmails=OrdiioUser::select('email')->get();
    return response()->json([
        'User Data'=>$userEmails
    ]);

}
public function create_license_category(Request $request)
    {
        $validated = $request->validate([
            '*.name' => 'required|string|max:100',
            '*.description' => 'required|string',
            '*.allowed_usage' => 'nullable|array',
            '*.restrictions' => 'nullable|string',
            '*.price_model' => 'required|in:pay_per_track,subscription,custom',
        ]);
        $inserted = [];
        foreach ($validated as $licenseData) {
            $inserted[] = ordiio_license_categories::create($licenseData);
        }

        return response()->json([
            'message' => 'License categories created successfully.',
            'data' => $inserted
        ], 201);
    } 
    
public function get_license_category(Request $request){
    $client_id=Auth::user()->id;
    $client_exists=OrdiioUser::where('id',$client_id)->exists(); 
    if(!$client_exists){
        return response()->json([
            'status'=>200,
            'message'=>'Please login!'
        ]);
    }
    $license_details = ordiio_license_categories::select('id','name', 'description')->get();

    return response()->json([
        'status'  => 200,
        'message' => 'License list fetched successfully',
        'data'    => $license_details
    ]);
}

public function purchase_ordiio_license(Request $request)
{
    $customer_id = Auth::user()->id;

    $validated = $request->validate([
        'license_id' => 'required|exists:ordiio_license_categories,id',
        'track_id'=>'required',
    ]);

    $license_details = ordiio_license_categories::findOrFail($validated['license_id']);
    $active_license = ordiio_license_purchases::where('customer_id', $customer_id)
        ->where('status', 'complete')
        ->where('licensed_track_id',$validated['track_id'])
        ->first();

    if ($active_license) {
        return response()->json([
            'status'       => 200,
            'message'      => 'You already have an active subscription. Please wait until it expires before purchasing another.',
        ]);
    }
    $reference = 'Ordiio_stripe_' . uniqid();

    $new_license = ordiio_license_purchases::create([
        'customer_id'       => $customer_id,
        'ordiio_license_id' => $license_details->id,
        'licensed_track_id' => $validated['track_id'],
        'amount'            => $license_details->license_cost,
        'currency'          => $license_details->currency,
        'status'            => 'pending',
        'payment_reference' => $reference,
        'meta'              => json_encode([
            'license_name' => $license_details->name ?? null,
            'duration'     => $license_details->duration ?? null,
            'reference'    => $reference,
        ]),
    ]);

    $uuid     = $new_license->uuid;
    $currency = $new_license->currency;
    $amount   = $new_license->amount;
    $customer = OrdiioUser::find($new_license->customer_id);

    $customer_email = $customer->email ?? null;

    try {
        $session = $this->createStripeCheckoutSession(
            $uuid,
            $amount,
            $currency,
            $customer_email,
            $license_details->name,
            $reference
        );
    } catch (\Exception $e) {
        Log::error("Stripe session creation failed: " . $e->getMessage());
        return response()->json([
            'status'  => 500,
            'message' => 'Failed to create Stripe checkout session',
        ]);
    }
    ordiio_transaction::updateOrCreate(
        ['reference_id' => $reference],
        [
            'customer_id'  => $customer_id,
            'license_type' => $validated['license_id'],
            'amount'       => $amount,
            'currency'     => $currency,
        ]
    );
    return response()->json([
        'status'       => 201,
        'message'      => 'License purchase created successfully',
        'uuid'         => $uuid,
        'checkout_url' => $session->url,
        'session_id'   => $session->id,
    ]);
}
private function createStripeCheckoutSession($uuid, $amount, $currency, $customer_email, $license_name ,$reference)
    {
        Stripe::setApiKey(env('ORDIIO_STRIPE_SECRET_KEY'));
        return Session::create([
            'mode' => 'payment',
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price_data' => [
                    'currency' => strtolower($currency),
                    'product_data' => [
                        'name' => $license_name,
                    ],
                    'unit_amount' => $amount * 100,
                ],
                'quantity' => 1,
            ]],
            'customer_email' => $customer_email,
            'metadata' => [
                'uuid' => $uuid,
                'reference_id' => $reference,
            ],
            'success_url' => 'https://api.joincroose.com/croose/ordiio_payment_success',
            'cancel_url'  => 'https://api.joincroose.com/croose/ordiio_payment_cancel',
        ]);
    }
public function webhook(Request $request)
    {
           
        $endpoint_secret = env('ORDIIO_STRIPE_WEBHOOK_SECRET');
        $payload = $request->getContent();
        $sig_header = $request->server('HTTP_STRIPE_SIGNATURE');
        $user=Auth::user();
        try {
            $event = Webhook::constructEvent(
                $payload,
                $sig_header,
                $endpoint_secret
            );
        } catch (\UnexpectedValueException $e) {
            return response('Invalid payload', 400);
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            return response('Invalid signature', 400);
        }
        $object = $event->data->object;
        $reference_id= $object->metadata->reference_id;
        try {
    if ($reference_id) {
        ordiio_transaction::where('reference_id', $reference_id)
            ->update([
                'payment_origin'    => 'stripe',
                'payment_method'    => $object->payment_method ?? ($object->payment_method_types[0] ?? null),
                'transaction_status'=> $object->status ?? 'pending',
                'transaction_id'    => $object->id ?? null,
                'paid_amount'       => isset($object->amount_total) 
                                            ? $object->amount_total / 100 
                                            : (isset($object->amount) ? $object->amount / 100 : null),
                'paid_currency'     => $object->currency ?? null,
                'stripe_session_id' => $object->id ?? null,
                // 'amount'            =>isset($object->amount_total) ? $object->amount_total / 100 : (isset($object->amount) ? $object->amount / 100 : null),
                'is_manual'         => 0,
                'meta'              => json_encode($object),
                'invoice_url'       => $object->invoice_pdf ?? null,
                'receipt_url'       => $object->charges->data[0]->receipt_url ?? null,
                'paid_at'           => isset($object->created)? \Carbon\Carbon::createFromTimestamp($object->created): null,
            ]);
            ordiio_license_purchases::where('payment_reference', $reference_id)->update([
                'status'=> $object->status ?? 'pending',
            ]);
             $uuid = $object->metadata->uuid ?? null;
             OrdioCheckoutSession::where('payment_reference', $reference_id)->where('uuid',$uuid)->update([
                    'status'    => $object->status ?? 'pending',
                    'metadata'  => json_encode($object),
                    'amount'    =>isset($object->amount_total) ? $object->amount_total / 100 : (isset($object->amount) ? $object->amount / 100 : null)
                ]);
            //   OrdiioUser::where('id',$user->id)->update(['is_subscriber'=>1]);
            }
        } catch (\Exception $e) {
            Log::error("Failed to store Stripe transaction: " . $e->getMessage());
        }
        // if ($event->type === 'payment_intent.succeeded') {
        //     $uuid = $object->metadata->uuid ?? null;
        //     $payment_status = $object->status;

        //     if ($uuid) {
        //         $license_details = ordiio_license_purchases::where('uuid', $uuid)->first();
        //         if ($license_details && $license_details->payment_status !== 'success') {
        //             $license_details->status = $payment_status;
        //             $license_details->save();
        //         }
        //     }
        // } else {
        //     Log::info("Ignored Stripe event: " . $event->type);
        // }

        return response('Webhook handled', 200);
    }
public function isSubscriber(Request $request){
    $user_id = Auth::user()->id; // shortcut for Auth::user()->id
    $is_subscriber = OrdiioUser::where('id', $user_id)->value('is_subscriber');
    // if($is_subscriber){
    //     $is_downloadable=1;
    // }
    // $is_licensed=ordiio_license_purchases::where('customer_id',$user_id)->where('licensed_track_id',$validated['track_id'])->exists();
    // if($is_licensed){
    //     $is_downloadable=1;
    // }
    return response()->json([
        'status'=>200,
        'isSubscriber'=>$is_subscriber,
        'message'=>'Subscription status fetched successfully'
    ]);
}


public function isdownloadable(Request $request){
    $validated=$request->validate([
        'track_id'=>'nullable',
    ]);
    $user_id = Auth::user()->id; // shortcut for Auth::user()->id
    $is_downloadable=0;
    $is_subscriber = OrdiioUser::where('id', $user_id)->value('is_subscriber');
    if($is_subscriber){
        $is_downloadable=1;
    }
    $is_licensed=ordiio_license_purchases::where('customer_id',$user_id)->where('licensed_track_id',$validated['track_id'])->exists();
    if($is_licensed){
        $is_downloadable=1;
    }
    return response()->json([
        'status'=>200,
        'is_downloadable'=>$is_downloadable,
        'message'=>'Subscription status fetched successfully'
    ]);
}

public function make_as_subscriber(Request $request){
    $user_id=Auth::user()->id;
    $validated = $request->validate([
        "email"      => "required|email",
        "valid_from" => "required|date",
        "valid_to"   => "required|date|after_or_equal:valid_from",
    ]);
    $user = OrdiioUser::where('email', $validated['email'])
    ->where('id',$user_id)
    ->first();
    if (!$user) {
        return response()->json([
            'status'  => 404,
            'message' => 'User not found',
        ], 404);
    }
    if($user->is_subscriber==1){
        return response()->json([
            'status'  => 201,
            'message' => 'The following User is already a subscriber.',
        ], 404);
    }

    $user->is_subscriber = 1;
    $user->valid_from    = $validated['valid_from'];
    $user->valid_to      = $validated['valid_to'];
    $user->save();

    return response()->json([
        'status'  => 200,
        'message' => 'The user is now a Subscriber',
    ]);
}


public function reset_password(Request $request)
{
    $user = Auth::user();
    $validated = $request->validate([
        'current_password' => 'required',
        'new_password'     => 'required', 
    ]);
    if (!Hash::check($validated['current_password'], $user->password)) {
        return response()->json(['error' => 'Current password is incorrect'], 400);
    }
    $user->password = Hash::make($validated['new_password']);
    $user->save();

    return response()->json(['message' => 'Password updated successfully']);
}

public function update_company_type(Request $request)
    {
    $user_id=Auth::user()->id;
    $validated=$request->validate([
        'company_type'=>'required',
    ]);
    $user = OrdiioUser::findOrFail($user_id);
    $user->company_type = $validated['company_type'];
    $user->save();
    return response()->json([
        'status'=>200,
        'message'=>'Company type updated Successfully!'
    ]);
    }

public function download_track(Request $request)
    {
    $user_id = Auth::user()->id;
    $validated = $request->validate([
        'project_title' => 'required|string',
        'track_id'     => 'required|integer',
        'project_type' => 'required|string',
    ]);
    $license_exists=ordiio_license_purchases::where('customer_id',$user_id)->where('licensed_track_id',$validated['track_id'])->exists();
    if(!$license_exists){
        $new_license_purchase=ordiio_license_purchases::create([
            'customer_id'=>$user_id,
            'licensed_track_id'=>$validated['track_id'],
            'project_title'=>$validated['project_title'],
            'status'    =>"subscriber",
            'project_type'=>$validated['project_type'],
        ]);
        return response()->json([
            'status'=>200,
            'message'=>"License created successfully."
        ]);
    }
    // $project_detail = ordiio_track_download_project_details::create([
    //     'user_id'=>$user_id, 
    //     'project_title' => $validated['project_title'],
    //     'track_id'     => $validated['track_id'],
    //     'project_type' => $validated['project_type'],
    // ]);
    return response()->json([
        'status'  => 201,
        'message' => 'License already exists',
        // 'data'    => $project_detail,
    ]);
    }

    public function purchase_ordiio_subscription(Request $request)
     {
        $user = Auth::user();
        // if ($user->is_subscriber==1) {
        //    return response()->json(['status'=> 200,'message'=> 'You already have an active subscription. Please wait until it expires before purchasing another.',]);
        // }
        $reference = 'Ordiio_subscriber_stripe_' . uniqid();
        $amount    = $request->amount / 100;
        $uuid1 = Str::uuid()->toString();
        
        $new_subscriber = OrdioCheckoutSession::create([
            'uuid'                => $uuid1,
            'customer_id'         => $user->id,
            'customer_email'      => $user->email,
            'region'              => $request->region,
            'amount'              => 0.00,
            'currecy_name'        => "usd",
            'subscription_type'   => $request->subscription_type,
            'validity'            => $request->validity,
            'payment_reference'   => $reference,
            'status'              => 'pending',
            'client_reference_id' => $user->id,
            'mode'                => 'subscription',
          ]);

        $uuid               = $new_subscriber->uuid;
        $currency           = $new_subscriber->currecy_name;
        $subscription_type  = $new_subscriber->subscription_type;
        $region             = $new_subscriber->region;
        $customer           = OrdiioUser::find($new_subscriber->customer_id);

        $customer_email = $customer->email ?? null;
        $validity       = $request->validity;
        try {
            $session = $this->createStripeCheckoutSessionsubs(
                $uuid,
                $currency,
                $customer_email,
                $subscription_type,
                $region,
                $reference,
                $validity
            );
             
        } catch (\Exception $e) {
            Log::error("Stripe session creation failed: " . $e->getMessage());
            return response()->json(['status'  => 500, 'message' => 'Failed to create Stripe checkout session']);
        }
        ordiio_transaction::updateOrCreate(
            ['reference_id' => $reference],
            [
                'customer_id'     => $user->id,
                'subscriber_type' => $subscription_type,
                'amount'          => 0.00,
                'currency'        => $currency,
            ]
        );
        OrdiioUser::where('id',$user->id)->update(['is_subscriber'=>1]);

        return response()->json([
            'status'       => 201,
            'message'      => 'Subscriber purchase created successfully',
            'uuid'         => $uuid,
            'checkout_url' => $session->url,
            'session_id'   => $session->id,
        ]);
     }
 
    private function createStripeCheckoutSessionsubs($uuid, $currency, $customer_email, $subscriber_type,$region ,$reference,$validity)
     {
        Stripe::setApiKey(env('ORDIIO_STRIPE_SECRET_KEY'));
         
        $priceId = $this->getStripePriceId($subscriber_type, $validity, $region);
        if (!$priceId) {
            throw new \Exception("No Stripe price found for your selection");
        }
        
        $successUrl = 'https://api.joincroose.com/croose/ordiio_subscriber_success';
        $cancelUrl  = 'https://api.joincroose.com/croose/ordiio_subscriber_cancel';

         return Session::create([
                'mode' => 'subscription',
                'payment_method_types' => ['card'],
                'line_items' => [[
                    'price' => $priceId,  
                    'quantity' => 1,
                ]],
                'customer_email' => $customer_email,
                'metadata' => [
                    'uuid' => $uuid,
                    'reference_id' => $reference,
                    'subscription_type' => $subscriber_type,
                    'region' => $region,
                ],
                'success_url' => $successUrl,
                'cancel_url'  => $cancelUrl,

            ]);
     }

     private function getStripePriceId($subscriber_type, $validity, $region)
      {
            
           $productMap = [
                'creator' => [
                    'monthly' => [
                        'africa' => 'prod_T44uqkzK99R2uI',
                        'general' => 'prod_T44uqkzK99R2uI',
                    ],
                    'yearly' => [
                        'africa' => 'prod_T44zbfiyAl16fn',
                        'general' => 'prod_T44zbfiyAl16fn',
                    ]
                ],
                'commercial' => [
                    'yearly' => [
                        'africa' => 'prod_T44zbfiyAl16fn',
                        'general' => 'prod_T44zbfiyAl16fn',
                    ]
                ]
            ];

            $subscriber_type = strtolower($subscriber_type);
            $region = strtolower($region);
            $region = ($region === 'african') ? 'africa' : $region;

            $productId = $productMap[$subscriber_type][$validity][$region] 
                        ?? $productMap[$subscriber_type][$validity]['general'] 
                        ?? null;
 
            if (!$productId) {
                \Log::error("No productId found for subscriber_type: $subscriber_type, validity: $validity, region: $region");
                return null;
            }
 
            try {
                $prices = \Stripe\Price::all([
                    'product' => $productId,
                    'active'  => true,
                ]);
                
            } catch (\Exception $e) {
                \Log::error("Stripe API error: " . $e->getMessage());
                return null;
            }
 
            return $prices->data[0]->id ?? null;
      }
 
    public function sonicSearch(Request $request, SonicSearchService $sonicSearch)
     {
        
        // $query   = $request->input('query', 'rock');
        // $page    = $request->input('page', 1);
        // $perPage = $request->input('per_page', 10);
        // $filters = [
        //     'duration' => [
        //         'min' => $request->input('min_duration', 30),
        //         'max' => $request->input('max_duration', 180),
        //     ]
        // ];
        
        // $results = $sonicSearch->search($query, $filters, $page, $perPage);

        // return response()->json($results);
        $trackId = $request->input('track_search'); // integer or null
        $searchId = $request->input('search_id');   // string or null

        $results = $sonicSearch->search($trackId, $searchId);

        return response()->json($results);
     }

public function get_playlists_album_track(Request $request)
    {
    $apiKey  = env('SOURCEAUDIO_API_KEY');
    $finalUrl = "https://ordiio.sourceaudio.com/api/playlists/getPublished";
    $process = Http::withHeaders([
        'Authorization' => "Bearer {$apiKey}",
    ])->post($finalUrl);
    $playlists = $process->json();
    $playlist = collect($playlists)->firstWhere('id', 1885697);
    
    $finalUrl = "https://ordiio.sourceaudio.com/api/playlists/getById";

    $process1 = Http::asForm()->withHeaders([
        'Authorization' => "Bearer {$apiKey}",
    ])->post($finalUrl, [
        'playlist_id' => $playlist['id'],
    ]);

    $playlists1 = $process1->json();
    
    $albumId = $request->album_id;
    $tracks = array_filter($playlists1['tracks'], function ($track) use ($albumId) {
            return $track['Album ID'] == $albumId;
    });

        $tracks = array_values($tracks);
    return response()->json(['status'=> 200, 'message'=> 'Album Track fetched successfully','tracks' => $tracks]);
    }



}

