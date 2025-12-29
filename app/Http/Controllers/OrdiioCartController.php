<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;
use App\Models\Ordiio_cart;


class OrdiioCartController extends Controller
{
      public function addToCart(Request $request){
        $user_id=Auth::user()->id;
        $validated=$request->validate([
            "email"=>"required",
            "track_id"=>"required",
        ]);
        $is_existing=Ordiio_cart::where('user_id',$user_id)->where('email',$validated['email'])->where('track_id',$validated['track_id'])->exists();
        if($is_existing){
            return response()->json([
                'status'=>200,
                'message'=>'Track already added to the Card!'
            ]);
        }
        $favourites=Ordiio_cart::create([
                'user_id'=>$user_id,
                'email'=>$validated['email'],
                'track_id'=>$validated['track_id'],
        ]);
        return response()->json([
            'status'=>200,
            'message'=>'Added to Cart',
        ]);
    }

public function getCartDetails(Request $request)
{
    try {
        $user_id = Auth::user()->id;
        $trackIds = Ordiio_cart::where('user_id', $user_id)->pluck('track_id');
        dd($trackIds);
        $tracks = collect($trackIds)->map(function ($trackId) {
            $details = $this->getTrackDetails($trackId);
            return [
                'track_id'   => $trackId,
                'track_name' => $details['Title']   ?? null,
                'artist'     => $details['Artist']  ?? null,
                'album'      => $details['Album']   ?? null,
                'duration'   => $details['Duration'] ?? null,
                'keywords'   => $details['Keywords'] ?? null,
                'file_name'  => $details['Filename'] ?? null,
                
            ];
        });

        return response()->json([
            'cart' => $tracks->filter()->values() // remove nulls, reindex
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'error'   => 'Server error',
            'details' => $e->getMessage()
        ], 500);
    }
}


private function getTrackDetails($trackId)
{
    try {
        $token = env('SOURCEAUDIO_API_KEY');
        $baseUrl = "https://ordiio.sourceaudio.com/api/tracks/getById";

        $response = Http::get($baseUrl, [
            'track_id' => $trackId,
            'token'    => $token,
        ]);

        if ($response->failed()) {
            \Log::error("SourceAudio getById failed", ['id' => $trackId, 'body' => $response->body()]);
            return null;
        }

        $data = $response->json();
        return $data ?? null;

    } catch (\Exception $e) {
        \Log::error("Exception in getTrackDetails", ['message' => $e->getMessage()]);
        return null;
    }
}

}
