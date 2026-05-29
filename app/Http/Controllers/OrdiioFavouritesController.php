<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;
use App\Models\Ordiio_favourites;
use Illuminate\Support\Facades\Auth;

class OrdiioFavouritesController extends Controller
{
    public function addToFavourites(Request $request){
        $user_id=Auth::user()->id;
        $validated=$request->validate([
            "email"=>"required",
            "track_id"=>"required",
        ]);
        $favourites=Ordiio_favourites::create([
                'user_id'=>$user_id,
                'email'=>$validated['email'],
                'track_id'=>$validated['track_id'],
        ]);
        return response()->json([
            'status'=>200,
            'message'=>'Added to Favourites',
        ]);
    }

     public function deleteFavourites($id)
      {
          $deleted = Ordiio_favourites::where('track_id', $id)->delete();
            if ($deleted === 0) {
                return response()->json(['status' => 404, 'message' => 'Favourite not found',]);
            }

            return response()->json(['status' => 200, 'message' => 'Favourite deleted successfully',]);
     }

// public function getFavourites(Request $request)
// {
//     try {
//         $user_id = Auth::user()->id;
//         $trackIds = Ordiio_favourites::where('user_id', $user_id)->pluck('track_id');

//         $tracks = collect($trackIds)->map(function ($trackId) {
//             $details = $this->getTrackDetails($trackId);
//             $signedUrl = $this->getSignedUrlInternal($trackId);
            
//             return [
//                 'track_id'   => $trackId,
//                 'track_name' => $details['Title']   ?? null,
//                 'artist'     => $details['Artist']  ?? null,
//                 'album'      => $details['Album']   ?? null,
//                 'duration'   => $details['Duration'] ?? null,
//                 'keywords'   => $details['Keywords'] ?? null,
//                 'file_name'  => $details['Filename'] ?? null,
//                 'Album_Image'  => "https://ordiio.sourceaudio.com/".$details['Album Image'] ?? null,
//                 'signedURL'  => $signedUrl,
//             ];
//         });

//         return response()->json([
//             'tracks' => $tracks->filter()->values() // remove nulls, reindex
//         ]);
//     } catch (\Exception $e) {
//         return response()->json([
//             'error'   => 'Server error',
//             'details' => $e->getMessage()
//         ], 500);
//     }
// }
public function getFavourites(Request $request)
{
    try {
        $user_id = Auth::user()->id;
        $trackIds = Ordiio_favourites::where('user_id', $user_id)->pluck('track_id');

        $tracks = collect($trackIds)->map(function ($trackId) {
            $details = $this->getTrackDetails($trackId);
            $signedUrl = $this->getSignedUrlInternal($trackId);

            return [
                'track_id'    => $trackId,
                'track_name'  => $details['Title']    ?? null,
                'artist'      => $details['Artist']   ?? null,
                'album'       => $details['Album']    ?? null,
                'duration'    => $details['Duration'] ?? null,
                'keywords'    => $details['Keywords'] ?? null,
                'file_name'   => $details['Filename'] ?? null,
                'Album_Image' => isset($details['Album Image'])
                                    ? "https://ordiio.sourceaudio.com/" . $details['Album Image']
                                    : null,
                'signedURL'   => $signedUrl,
            ];
        });

        return response()->json([
            'tracks' => $tracks->filter()->values() // remove nulls, reindex
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'error'   => 'Server error',
            'details' => $e->getMessage()
        ], 500);
    }
}

/**
 * Fetch signed download URL for a track.
 */
private function getSignedUrlInternal($trackId)
{
    try {

        $token = env('SOURCEAUDIO_API_KEY');
        $baseUrl = "https://ordiio.sourceaudio.com/api/tracks/downloadAuth";

        $response = Http::get($baseUrl, [
            'track_id' => $trackId,
            'format'   => 'mp3',
            'duration' => 3600,
            'token'    => $token,
        ]);

        if ($response->failed()) {
            return null;
        }

        $data = $response->json();
        return $data['fullPath'] ?? null;
    } catch (\Exception $e) {
        return null;
    }
}

/**
 * Fetch track metadata details from SourceAudio API.
 */
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
