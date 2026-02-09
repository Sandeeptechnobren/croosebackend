<?php

namespace App\Http\Controllers;
use App\Models\Product;
use App\Models\Client;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

use App\Models\Country;
use CountryFlag;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\PersonalAccessToken;
use App\Models\Space;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\SpaceController;
use Illuminate\Support\Facades\Storage;
class ClientsController extends Controller
{
   public function countries()
{
    $countries = Country::all()->map(function ($country) {
        $code = strtolower($country->country_code);

        return [
            'id'   => $country->id,
            'name' => $country->country_name,
            'code' => strtoupper($country->country_code),
            'flag' => "https://flagcdn.com/w20/{$code}.png",
        ];
    });

    return response()->json([
        'status'  => 200,
        'data'    => $countries,
        'message' => 'Countries List',
    ]);
}

    public function getClientWithProducts($phone_number)
        {
            $client = DB::table('clients')->where('phone_number', $phone_number)->first();
            if (!$client) {
                return response()->json([
                    'success' => false,
                    'message' => 'Client not found',
                ], 404);
            }
            $products = Product::where('client_id', $client->id)->get();
            return response()->json([
                'success' => true,
                'products' => $products,
                'count' => $products->count(),
                'message' => 'Client and their Products List'
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
    public function downloadTemplate($type){
           
            $fileName = match ($type) {
                    'product' => 'Bulk_upload_templates/New_products_template.xlsx',
                    'service' => 'Bulk_upload_templates/new_services_template.xlsx',
                    default => null,
                };
            
            if (!$fileName || !Storage::disk('public')->exists($fileName)) {
                    return response()->json([
                        'status' => false,
                        'message' => 'Template not found.',
                    ], Response::HTTP_NOT_FOUND);
                }
            $url = Storage::disk('public')->url($fileName);
            
            return response()->json([
                'status' => true,
                'message' => 'Template URL generated successfully.',
                'download_url' => $url,
            ]);
        }

        public function account_profile(Request $request){
            $client_id=Auth::user()->id;
            $account_detail=Client::where('id',$client_id)->get();
            return response()->json([
                'status'=>true,
                'message'=>"Account Data Fetched Successfully",
                'data'=>$account_detail,
            ]);

        }

        public function updateAccountProfile(Request $request)
        {
            $client = Auth::user(); // token se logged-in client
        
            // ✅ Validation (photo OPTIONAL)
            $request->validate([
                'name'              => 'required|string|max:255',
                'business_name'     => 'required|string|max:255',
                'business_location' => 'required|string|max:255',
                'photo'             => 'nullable|image|mimes:jpg,jpeg,png|max:1024',
            ]);
        
            // ✅ Update basic fields
            $client->name              = $request->name;
            $client->business_name     = $request->business_name;
            $client->business_location = $request->business_location;
        
            // ✅ Photo update ONLY if new photo sent
            if ($request->hasFile('photo')) {
        
                // old photo delete
                if ($client->profile_photo && Storage::disk('public')->exists($client->profile_photo)) {
                    Storage::disk('public')->delete($client->profile_photo);
                }
        
                // store new photo
                $path = $request->file('photo')->store('profile_photo', 'public');
                $client->profile_photo = $path;
            }
        
            $client->save();
        
            return response()->json([
                'status'  => true,
                'message' => 'Account profile updated successfully',
                'data'    => [
                    'id'                => $client->id,
                    'name'              => $client->name,
                    'business_name'     => $client->business_name,
                    'business_location' => $client->business_location,
                    'email'             => $client->email, // sirf show
                    'profile_photo'     => $client->profile_photo,
                ]
            ]);
        }
        
public function update_password(Request $request){
    $user=Auth::user();   
    $validated=$request->validate([
        'current_password' => 'required',
        'new_password' => 'required|min:8',
    ]);
    if(!Hash::check($request->current_password,$user->password)){
        return response()->json([
            'status'=>true,
            'message'=>'Current Password is not Valid',
        ],422);
    }
    $user->update([
        'password'=>Hash::make($validated['new_password']),
    ]);
    return response()->json([
        'status' => true,
        'message' => 'Password updated successfully',
    ]);
    }
}