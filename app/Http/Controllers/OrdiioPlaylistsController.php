<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\Ordiio_playlists;
use App\Models\ordiio_playlist_tracks;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
class OrdiioPlaylistsController extends Controller
{
public function createPlaylist(Request $request)
{
    $user_id=Auth::user()->id;
    $validated=$request->validate([
        "email"=>"required",
        "playlist_name"=>"required",
        "description"=>"required",
    ]);
    $playlist=Ordiio_playlists::create([
        'user_id'=>$user_id,
        'email'=>$validated['email'],
        'playlist_name'=>$validated['playlist_name'],
        'description'=>$validated['description'],
    ]);
    return response()->json([
        'status'=>200,
        'message'=>"Playlist Created Successfully",
        'data'=>$playlist
    ]);
}

 public function deletefromPlaylist(Request $request)
  {
            
        $deleted = ordiio_playlist_tracks::where('playlist_id', $request->playlist_id)
            ->where('track_id', $request->track_id)
            ->delete();  

        if ($deleted) {
            return response()->json(['status' => 200, 'message' => 'Track removed from playlist successfully']);
        } else {
            return response()->json(['status' => 404, 'message' => 'Track not found in playlist']);
        }  
  }

public function getPlaylist(Request $request){
    $user_id=Auth::user()->id;
    $playlists=Ordiio_playlists::where('user_id', $user_id)->get();
    return response()->json([
        'status'=>200,
        'data'=>$playlists,
        'message'=>'Playlist Data fetched successfully!'
    ]);
}
public function addToPlaylist(Request $request)
{
    $user_id = Auth::user()->id;
    $validated = $request->validate(['playlist_id' => 'required|exists:ordiio_playlists,id','track_id'    => 'required',]);
    $exists = ordiio_playlist_tracks::where('user_id', $user_id)->where('playlist_id', $validated['playlist_id'])->where('track_id', $validated['track_id'])->first();
    if ($exists) {return response()->json(['status'  => 409,'message' => 'Track already exists in this playlist']);}
    $favourite = ordiio_playlist_tracks::create(['user_id'=> $user_id,'playlist_id' => $validated['playlist_id'],'track_id'    => $validated['track_id'],]);
    return response()->json(['status'  => 200,'message'=>'Added to Playlist','data'=> $favourite]);
}
// public function getPlaylisttracks(Request $request)
// {
// try {
//     $user_id = Auth::user()->id;
//     $validated=$request->validate(["playlist_id"=>"required",]);
//     $trackIds = ordiio_playlist_tracks::where('user_id', $user_id)->where('playlist_id',$validated['playlist_id'])->pluck('track_id');
//     $tracks = collect($trackIds)->map(function ($trackId) {
//     $details = $this->getTrackDetails($trackId);
//     return [
//             'track_id'   => $trackId,
//             'track_name' => $details['Title']   ?? null,
//             'artist'     => $details['Artist']  ?? null,
//             'album'      => $details['Album']   ?? null,
//             'duration'   => $details['Duration'] ?? null,
//             'keywords'   => $details['Keywords'] ?? null,
//             'master_id'   => $track['Master ID'] ?? null,
//             'file_name'  => $details['Filename'] ?? null,
//             'Album_Image' => isset($track['Album Image'])? "https://ordiio.sourceaudio.com/" . $track['Album Image']: null,
//         ];
//     });
//     return response()->json(['tracks' => $tracks->filter()->values()]);
// } catch (\Exception $e) {
//     return response()->json(['error'=> 'Server error','details' => $e->getMessage()], 500);
// }
// }
public function getPlaylisttracks(Request $request)
{
    try {
        $user_id = Auth::user()->id;
        $validated = $request->validate([
            "playlist_id" => "required",
        ]);
        $trackIds = ordiio_playlist_tracks::where('user_id', $user_id)
            ->where('playlist_id', $validated['playlist_id'])
            ->pluck('track_id');

        $tracks = collect($trackIds)->map(function ($trackId) {
            // Get raw details
            $details = $this->getTrackDetails($trackId);
            // Call the TrackHelper
            return \App\Helpers\TrackHelper::formatTrackDetails($details ?? []);
        });

        return response()->json(['tracks' => $tracks->filter()->values()]);
    } catch (\Exception $e) {
        return response()->json(['error' => 'Server error', 'details' => $e->getMessage()], 500);
    }
}


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

private function getTrackDetails($trackId)
{  
try {
    $token = env('SOURCEAUDIO_API_KEY');
    $baseUrl = "https://ordiio.sourceaudio.com/api/tracks/getById";
    $response = Http::get($baseUrl, ['track_id' => $trackId,'token'    => $token,]);
    if ($response->failed()) {return null;}
    $data = $response->json();
    return $data ?? null;
    } catch (\Exception $e) {
    \Log::error("Exception in getTrackDetails", ['message' => $e->getMessage()]);
    return null;
    }
}
}
