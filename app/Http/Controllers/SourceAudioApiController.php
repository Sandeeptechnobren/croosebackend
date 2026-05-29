<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\ordiio_license_purchases;
use Illuminate\Support\Facades\Http;
use App\Models\OrdiioUser;
use App\Services\Ordiio\SourceAudioApiService;
use App\Services\SourceAudioService;
use App\DTOs\TrackDTO;
class SourceAudioApiController extends Controller
 {
   protected $trackService;
   protected $sourceAudio;

   public function __construct(SourceAudioApiService $trackService,SourceAudioService $sourceAudio)
    {
        $this->trackService = $trackService;
        $this->sourceAudio = $sourceAudio;
    }

   public function listTracks(Request $request)
    {
        try {
            $searchQuery = $request->query('s', '');
            $limit       = $request->query('limit', 100);
            $offset      = $request->query('offset', 0);
            $tracks = $this->trackService->listTracks($searchQuery, $limit, $offset);
            return response()->json(['tracks' => $tracks->values()]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Server error', 'details' => $e->getMessage()], 500);
        }
    }

    public function getTrackData(Request $request)
     {
        try {
            $validated = $request->validate([
                "track_id" => "required"
            ]);

            $track_id = $validated['track_id'];
            $track_data = $this->trackService->individualTrackData($track_id);

            if (!$track_data) {
                return response()->json(['status' => false, 'code'=> 404, 'message' => 'Track not found', 'data'=> null ], 404);
            }

            return response()->json(['status' => true, 'code'=> 200, 'message' => 'Track data retrieved successfully', 'data'=> $track_data], 200);

        } catch (\Exception $e) {
            return response()->json(['status' => false, 'code' => 500, 'message' => 'Server Error', 'error'   => $e->getMessage()], 500);
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
            } 
        catch (\Exception $e) {
            return null;
        }
     }

    public function about_artist(Request $request)
     {
        $user_id = Auth::user()->id;

        $validated = $request->validate([
            "artist_name" => "required",
        ]);

        $artistName = $validated['artist_name'];
        $airtableToken = env('AIRTABLE_TOKEN');

        $url = 'https://api.airtable.com/v0/appGq1qaSHWRt3Z3G/tblLC1ejgmtVxwkXo';

        $response = Http::withToken($airtableToken)->get($url, [
            'filterByFormula' => "SEARCH(\"{$artistName}\", {name})"
        ]);

        if ($response->successful()) {
            $data = $response->json();

            // Extract only the required fields
            $artists = collect($data['records'])->map(function ($record) {
                return [
                    'name' => $record['fields']['Name'] ?? null,
                    'bio' => $record['fields']['Bio'] ?? null,
                    'images' => collect($record['fields']['Attachments'] ?? [])
                        ->pluck('url')
                        ->toArray(),
                ]; 
            });

            return response()->json($artists);
        } else {
            return response()->json(['error' => 'Failed to fetch from Airtable', 'status' => $response->status(), 'message' => $response->body()], $response->status());
        }
     }

public function listTracks_filter(Request $request)
    {
       try {
            $token = env('SOURCEAUDIO_API_KEY');
            $baseUrl = "https://ordiio.sourceaudio.com/api/tracks/search";
            $response = Http::get($baseUrl, [
                's'      => $request->query('filter', ''),  
                'limit'  => $request->query('limit', 100),
                'offset' => $request->query('offset', 0),
                'token'  => $token,
            ]);
            if ($response->failed()) {
                return response()->json(['error' => 'SourceAudio fetch failed', 'details' => $response->body()], 500);
            }
            $data = $response->json();
            $tracks = collect($data['tracks'] ?? []);
            $filters = $request->input('filter', []);
            if (!empty($filters)) {
                $tracks = $tracks->filter(function ($track) use ($filters) {
                    foreach ($filters as $filter) {
                        if ((!empty($filter['track_name']) && stripos($track['Title'] ?? '', $filter['track_name']) === false) ||
                            (!empty($filter['artist']) && stripos($track['Artist'] ?? '', $filter['artist']) === false) ||
                            (!empty($filter['album']) && stripos($track['Album'] ?? '', $filter['album']) === false)
                        ) {
                            return false;
                        }
                    }
                    if (!empty($filter['keywords'])) {
                            $trackKeywords = strtolower($track['Keywords'] ?? '');
                            $matchFound = false;
                            foreach ($filter['keywords'] as $keyword) {
                                if (stripos($trackKeywords, $keyword) !== false) {
                                    $matchFound = true;
                                    break;
                                }
                            }
                            if (!$matchFound) {
                                return false;
                            }
                        }
                    return true;
                });
            }

            $tracksWithUrls = $tracks->map(function ($track) {
                return [
                    'track_id'    => $track['SourceAudio ID'] ?? null,
                    'track_name'  => $track['Title'] ?? null,
                    'artist'      => $track['Artist'] ?? null,
                    'album'       => $track['Album'] ?? null,
                    'duration'    => $track['Duration'] ?? null,
                    'Keywords'    => $track['Keywords'] ?? null,
                    'file_name'   => $track['Filename'] ?? null,
                    'Album_Image' => isset($track['Album Image']) 
                                    ? "https://ordiio.sourceaudio.com/" . $track['Album Image'] 
                                    : null,
                    'signedURL'   => $this->getSignedUrlInternal($track['SourceAudio ID'] ?? null),
                ];
            });
            return response()->json(['tracks' => $tracksWithUrls->values()]);
        } catch (\Exception $e) {
            return response()->json(['error'=> 'Server error', 'details' => $e->getMessage()], 500);
        }
    }


    public function link_search(Request $request)
    {
            $user_id=Auth::user()->id;
            
            $validated = $request->validate([
                'link_url' => 'required|url',
            ]);
            $linkUrl = $validated['link_url'];
            $user    = OrdiioUser::find($user_id);
            if (! $user || $user->is_subscriber == 0) {
                return response()->json(['status'  => 403, 'message' => 'Need to be a subscriber to access Link Search'], 403);
            }
            $apiKey  = env('SOURCEAUDIO_API_KEY');
            $baseUrl = 'https://ordiio.sourceaudio.com';
            $finalUrl = "{$baseUrl}/api/sonicsearch/processUrl?url=" . urlencode($linkUrl);
            $process = Http::withHeaders([
                'Authorization' => "Bearer {$apiKey}",
                ])->post($finalUrl);
            $processJson = $process->json();
            if ($process->failed()) {
                return response()->json(['status'  => $process->status(), 'message' => 'Failed to start SourceAudio processUrl', 'error'   => $process->body(),
                ], $process->status());
            }
            if (! isset($processJson['search_id'])) {
                return response()->json([ 'status'  => 500, 'message' => 'search_id not found in response', 'data' => $processJson], 500);
            }
            $track_details = $this->search_audio($processJson['search_id']); 
            return response()->json(['status'  => 200, 'message' => 'Tracks retrieved successfully', 'data' => $track_details]);
     }

    private function search_audio($id)
     {
        try {
            $apiKey = env('SOURCEAUDIO_API_KEY');
            $searchResponse = Http::withHeaders([
                'Authorization' => "Bearer {$apiKey}",
            ])->post("https://ordiio.sourceaudio.com/api/sonicsearch/search?search_id={$id}");
            if ($searchResponse->failed()) {
                return [
                    'error'   => 'SonicSearch fetch failed',
                    'details' => $searchResponse->body()
                ];
            }
            $data = $searchResponse->json();
            $matches = $data['matches'] ?? [];
            $tracksWithDetails = collect($matches)->flatMap(function ($match) {

                $trackId = $match['track_id'] ?? null;
                if (!$trackId) {
                    return [];
                }

                $token   = env('SOURCEAUDIO_API_KEY');
                $baseUrl = "https://ordiio.sourceaudio.com/api/tracks/search";

                $response = Http::get($baseUrl, [
                    'track_id' => $trackId,
                    'token'    => $token,
                ]);

                if ($response->failed()) {
                    return [];
                }

                $tracks = $response->json()['tracks'] ?? [];

                if (empty($tracks)) {
                    return [];
                }

                // ✅ Return each version as separate item
                return collect($tracks)->map(function ($t) use ($match, $trackId) {
                    return [
                        'track_id'       => $trackId,
                        'file_id'        => $match['file_id'] ?? null,
                        'filename'       => $match['filename'] ?? null,
                        'score'          => $match['score'] ?? null,
                        'track_name'     => $t['Title'] ?? null,
                        'version_type'   => $t['Custom: Version Type'] ?? 'Unknown',
                        'artist'         => $t['Artist'] ?? null,
                        'album'          => $t['Album'] ?? null,
                        'duration'       => $t['Duration'] ?? null,
                        'keywords'       => $t['Keywords'] ?? null,
                        'album_image'    => isset($t['Album Image']) 
                                                ? "https://ordiio.sourceaudio.com/" . ltrim($t['Album Image'], '/')
                                                : null,
                        'signed_url'     => $this->getSignedUrlInternal($trackId),
                    ];
                })->toArray();
            })->values();
            return [
                'search_id' => $id,
                'match_count' => $data['match_count'] ?? count($tracksWithDetails),
                'tracks' => $tracksWithDetails
            ];
        } catch (\Exception $e) {
            return [
                'error'   => 'Server error',
                'details' => $e->getMessage()
            ];
        }
     }
   private function getTrackDetailsById($trackId)
    {
        try {
            $token   = env('SOURCEAUDIO_API_KEY');
            $baseUrl = "https://ordiio.sourceaudio.com/api/tracks/search";

            $response = Http::get($baseUrl, [
                'track_id' => $trackId,
                'token'    => $token,
            ]);

            if ($response->failed()) {
                return [];
            }

            $data = $response->json();
            
            return $data['tracks'][0] ?? [];

        } catch (\Exception $e) {
            return [];
        }
    }

    public function similar_search(Request $request)
    {
        $validated = $request->validate([
            'track_search' => 'required'
        ]);

        $track_search = $validated['track_search'];
        $apiKey  = env('SOURCEAUDIO_API_KEY');
        $baseUrl = 'https://ordiio.sourceaudio.com';
        $finalUrl = "{$baseUrl}/api/sonicsearch/search?track_search=" . urlencode($track_search);

        $process = Http::withHeaders([
            'Authorization' => "Bearer {$apiKey}",
        ])->post($finalUrl);

        if ($process->failed()) {
            return response()->json([
                'status'  => $process->status(),
                'message' => 'Failed to start SourceAudio processUrl',
                'error'   => $process->body(),
            ], $process->status());
        }
        $processJson = $process->json();
        $matches     = $processJson['matches'] ?? [];
        $tracksWithDetails = collect($matches)->map(function ($match) {
            $trackId = $match['track_id'] ?? null;
            if (! $trackId) {
                return null;
            }
            $trackDetails = $this->getTrackDetailsById($trackId);
            return [
                'track_id'     => $trackId,
                'file_id'      => $match['file_id'] ?? null,
                'filename'     => $match['filename'] ?? null,
                'score'        => $match['score'] ?? null,
                'track_name'   => $trackDetails['Title'] ?? null,
                'artist'       => $trackDetails['Artist'] ?? null,
                'album'        => $trackDetails['Album'] ?? null,
                'duration'     => $trackDetails['Duration'] ?? null,
                'Keywords'     => $trackDetails['Keywords'] ?? null,
                'master_id'  => $track['Master ID']??null,
                'Album_Image'  => isset($trackDetails['Album Image']) 
                                    ? "https://ordiio.sourceaudio.com/" . $trackDetails['Album Image']
                                    : null,
                'signedURL'   => $this->getSignedUrlInternal($trackId),
            ];
        })->filter()->values();
        return response()->json(['status'=> 200, 'message'=> 'Similar tracks retrieved successfully', 'match_count' => $processJson['match_count'] ?? count($tracksWithDetails),
            'tracks' => $tracksWithDetails,
        ]);
    }
   public function get_playlists(Request $request)
    {
        $apiKey  = env('SOURCEAUDIO_API_KEY');
        $finalUrl = "https://ordiio.sourceaudio.com/api/playlists/getPublished";
        $process = Http::withHeaders([
            'Authorization' => "Bearer {$apiKey}",
        ])->post($finalUrl);
        $playlists = $process->json();
        
        // $playlists = $processJson['playlists'] ?? [];
        $baseUrl = "https://ordiio.sourceaudio.com/";
        $playlists = collect($playlists) 
          ->filter(function ($playlist) {
           return $playlist['id'] != "1885697";
             })->map(function ($playlist) use ($baseUrl) {
                if (!empty($playlist['image'])) {
                    $playlist['image'] = $baseUrl . ltrim($playlist['image'], '/');
                }
                return $playlist;
            })->values()->toArray();
            return response()->json(['status'=> 200, 'message'   => 'Playlists fetched successfully', 'playlists' => $playlists]);
    }

public function get_playlists_thematic_albums(Request $request)
    {
        $apiKey  = env('SOURCEAUDIO_API_KEY');
        $finalUrl = "https://ordiio.sourceaudio.com/api/playlists/getPublished";
        $process = Http::withHeaders([
            'Authorization' => "Bearer {$apiKey}",
        ])->post($finalUrl);
        $playlists = $process->json();
        $playlist = collect($playlists)->firstWhere('id', 1885697);
        $finalUrl = "https://ordiio.sourceaudio.com/api/playlists/getById";
        $process1 = Http::asForm()->withHeaders(['Authorization' => "Bearer {$apiKey}",])->post($finalUrl, ['playlist_id' => $playlist['id'],]);
        $playlists1 = $process1->json();
        $albums = $playlists1['albums'] ?? [];
        return response()->json(['status'=> 200, 'message'=> 'Playlists Album fetched successfully','albums' => $albums]);
    }

// public function get_playlists_album_track(Request $request)
//     {
//         $playlists = $this->sourceAudio->getPublishedPlaylists();
//         $playlist = collect($playlists)->firstWhere('id', 1885697);
//         if (!$playlist) {
//             return response()->json(['status' => 404, 'message' => 'Playlist not found'], 404);
//         }
//         $albumId = $request->album_id; 
//         $playlistDetails = $this->sourceAudio->getPlaylistById($playlist['id']);
//         $tracks = collect($playlistDetails['tracks'] ?? [])
//             ->filter(fn($track) => $track['Album ID'] == $albumId)
//             ->map(function ($track) {
//                     return TrackDTO::fromArray($track);
//             });
//         return response()->json(['status' => 200,'message' => 'Album Track fetched successfully','tracks' => $tracks->values()]);
//     }
public function get_playlists_album_track(Request $request)
{
    try {
        $playlists = $this->sourceAudio->getPublishedPlaylists();
        $playlist = collect($playlists)->firstWhere('id', 1885697);
        if (!$playlist) {
            return response()->json([
                'status' => 404,
                'message' => 'Playlist not found'
            ], 404);
        }
        $albumId = $request->album_id;
        $playlistDetails = $this->sourceAudio->getPlaylistById($playlist['id']);
        $tracks = collect($playlistDetails['tracks'] ?? [])
            ->filter(fn($track) => $track['Album ID'] == $albumId)
            ->map(function ($track) {
                return \App\Helpers\TrackHelper::formatTrackDetails($track);
            });
        return response()->json([
            'status' => 200,
            'message' => 'Album Track fetched successfully',
            'tracks' => $tracks->values()
        ]);
    } catch (\Throwable $e) {
        \Log::error('Failed to fetch album tracks', ['error' => $e->getMessage()]);
        return response()->json([
            'status' => 500,
            'message' => 'Error fetching album tracks',
            'details' => $e->getMessage()
        ], 500);
    }
}

    public function licensed_tracks(Request $request)
     {
        $user_id = Auth::user()->id;
        $trackPurchases = ordiio_license_purchases::where('customer_id', $user_id)
            ->whereIn('status', ['subscriber', 'complete'])
            ->get(['licensed_track_id', 'created_at','project_title']);

        if ($trackPurchases->isEmpty()) {
            return response()->json([
                'status'  => 200,
                'message' => 'No licensed tracks found',
                'tracks'  => [],
            ]);
        }
        $tracks = $trackPurchases->map(function ($purchase) {
            $trackId = $purchase->licensed_track_id;
            $trackDetails = $this->getTrackDetailsById($trackId);
            if (empty($trackDetails)) {
                return null;
            }
            return [
                'track_id'    => $trackId,
                'track_name'  => $trackDetails['Title'] ?? null,
                'artist'      => $trackDetails['Artist'] ?? null,
                'album'       => $trackDetails['Album'] ?? null,
                'duration'    => $trackDetails['Duration'] ?? null,
                'keywords'    => $trackDetails['Keywords'] ?? null,
                'album_image' => isset($trackDetails['Album Image'])
                    ? "https://ordiio.sourceaudio.com/" . ltrim($trackDetails['Album Image'], '/')
                    : null,
                'issue_date'  => \Carbon\Carbon::parse($purchase->created_at)->format('d-M-Y h:i '),
                'project_title'  =>$purchase->project_title,
                'signed_url'  => $this->getSignedUrlInternal($trackId),
            ];
        })->filter()->values();
        return response()->json([
            'status'  => 200,
            'message' => 'Licensed tracks fetched successfully',
            'tracks'  => $tracks,
        ]);
     }

    public function museAIsearch(Request $request)
     { 
        $user = Auth::user()->id;
        $isSubscriber = OrdiioUser::where('id', $user)->value('is_subscriber');
        if ($isSubscriber != 1) {
            return response()->json(['status' => 200, 'Message' => 'You need to be a subscriber to access this feature']);
        }
        $validated = $request->validate(['prompt' => 'required|string']);
        $apiKey  = env('SOURCEAUDIO_API_KEY');
        $process = Http::post('https://ordiio.sourceaudio.com/api/tracks/search', [
            'token' => $apiKey,
            'ai'    => $validated['prompt'],
        ]);

        $searchResults = $process->json();
        if (!empty($searchResults['tracks'])) {
            foreach ($searchResults['tracks'] as &$track) {
                if (!empty($track['SourceAudio ID'])) {
                    $trackId = $track['SourceAudio ID'];
                    // $signedUrl = $this->getSignedUrlInternal($trackId);
                    // $track['signed_url'] = $signedUrl;
                }
            }
        }

        return response()->json(['status'  => 200, 'message' => 'Muse AI data fetch Successful', 'data' => $searchResults]);
     }
// public function museAIsearch(Request $request)
// {
//     try {
//         $user = Auth::id();
//         $isSubscriber = OrdiioUser::where('id', $user)->value('is_subscriber');
//         if ($isSubscriber != 1) {
//             return response()->json([
//                 'status' => 200,
//                 'message' => 'You need to be a subscriber to access this feature',
//             ]);
//         }

//         $validated = $request->validate([
//             'prompt' => 'required|string',
//         ]);

//         $apiKey = env('SOURCEAUDIO_API_KEY');
//         $response = Http::post('https://ordiio.sourceaudio.com/api/tracks/search', [
//             'token' => $apiKey,
//             'ai'    => $validated['prompt'],
//         ]);

//         $searchResults = $response->json();

//         if (!empty($searchResults['tracks'])) {
//             // Map/format every track using the helper exactly as you defined
//             $searchResults['tracks'] = array_map(function ($track) {
//                 return \App\Helpers\TrackHelper::formatTrackDetails($track);
//             }, $searchResults['tracks']);
//         }

//         return response()->json([
//             'status'  => 200,
//             'message' => 'Muse AI data fetch Successful',
//             'data'    => $searchResults
//         ]);
//     } catch (\Throwable $e) {
//         \Log::error('Muse AI search failed', ['error' => $e->getMessage()]);
//         return response()->json([
//             'status'  => 500,
//             'message' => 'Error fetching Muse AI data',
//             'details' => $e->getMessage(),
//         ], 500);
//     }
// }

// public function curated_playlist_tracks(Request $request)
//     {
//         $validated=$request->validate([
//             'playlist_id'=> 'required',
//         ]);
//         $user=Auth::user()->id;
//         $apiKey  = env('SOURCEAUDIO_API_KEY');
//         $finalUrl = "https://ordiio.sourceaudio.com/api/playlists/getById";
//         $process = Http::asForm()->withHeaders([
//             'Authorization' => "Bearer {$apiKey}",
//         ])->post($finalUrl, [
//             'playlist_id' =>$validated['playlist_id'],
//         ]);
//         $playlists = $process->json();
//         if (!empty($playlists['tracks'])) {
//             foreach ($playlists['tracks'] as &$track) {
//                 if (!empty($track['SourceAudio ID'])) {
//                     $trackId = $track['SourceAudio ID'];
//                     $signedUrl = $this->getSignedUrlInternal($trackId);
//                     $track['signed_url'] = $signedUrl;
//                     return [
//                        'track_id'   => $track['SourceAudio ID']??null,
//                        'track_name' => $track['Title'] ?? null,
//                        'artist'     => $track['Artist'] ?? null,
//                        'album'      => $track['Album'] ?? null,
//                        'duration'   => $track['Duration'] ?? null,
//                        'Keywords'   => $track['Keywords'] ?? null,
//                        'file_name'  => $track['Filename'] ?? null,
//                        'master_id'  => $track['Master ID']??null,
//                        'Album_Image'  => "https://ordiio.sourceaudio.com/".$track['Album Image'] ?? null,
//                        'signedURL'  => $this->getSignedUrlInternal($track['SourceAudio ID'] ?? null),
//                    ];
//                 }
//             }
//         }
//         return response()->json([
//             'status'=>200,
//             'message'=> 'Playlist tracks Fetched Successfully',
//             'data'=>$playlists['tracks']
//         ]);
//     }   

    // public function curated_playlist_tracks(Request $request)
    // {
    //     $validated = $request->validate([
    //         'playlist_id' => 'required',
    //     ]);

    //     $user   = Auth::user()->id;
    //     $apiKey = env('SOURCEAUDIO_API_KEY');
    //     $finalUrl = "https://ordiio.sourceaudio.com/api/playlists/getById";

    //     $process = Http::asForm()->withHeaders([
    //         'Authorization' => "Bearer {$apiKey}",
    //     ])->post($finalUrl, [
    //         'playlist_id' => $validated['playlist_id'],
    //     ]);

    //     $playlists = $process->json();

    //     $formattedTracks = [];

    //     if (!empty($playlists['tracks'])) {
    //         foreach ($playlists['tracks'] as $track) {
    //             if (!empty($track['SourceAudio ID'])) {
    //                 $formattedTracks[] = [
    //                     'track_id'    => $track['SourceAudio ID'] ?? null,
    //                     'track_name'  => $track['Title'] ?? null,
    //                     'artist'      => $track['Artist'] ?? null,
    //                     'album'       => $track['Album'] ?? null,
    //                     'duration'    => $track['Duration'] ?? null,
    //                     'keywords'    => $track['Keywords'] ?? null,
    //                     'file_name'   => $track['Filename'] ?? null,
    //                     'master_id'   => $track['Master ID'] ?? null,
    //                     'album_image' => !empty($track['Album Image']) 
    //                                         ? "https://ordiio.sourceaudio.com/" . $track['Album Image'] 
    //                                         : null,
    //                     // 'signedURL'   => $this->getSignedUrlInternal($track['SourceAudio ID']),
    //                 ];
    //             }
    //         }
    //     }

    //     return response()->json(['status'  => 200, 'message' => 'Playlist tracks fetched successfully', 'data' => $formattedTracks]);
    // }

     public function curated_playlist_tracks(Request $request)
      {
         $validated = $request->validate([
            'playlist_id' => 'required',
         ]);

         $tracks = $this->sourceAudio->getCuratedPlaylistTracks($validated['playlist_id']);

         return response()->json([ 'status'  => 200, 'message' => 'Playlist tracks fetched successfully', 'data' => $tracks,]);
      }

    public function stems(Request $request)
     {
        try{
            $validated=$request->validate([
                'master_id'=>'required',
            ]);
            $master_id=$validated['master_id'];
            $tracks=$this->trackService->getStems($master_id);
            return response()->json([
                'status' => 'success',
                'message' => 'Tracks retrieved successfully',
                'data' => $tracks->values()
            ]);
        }
        catch(\Exception $e){
            return response()->json(['error'=>'Server error','details'=> $e->getMessage()],500);
        }
     }
//     $apiKey = env('SOURCEAUDIO_API_KEY');
//     $validated = $request->validate([
//         'master_id' => 'required',
//     ]);
//     $masterId = $validated['master_id'];
//     $finalUrl = "https://ordiio.sourceaudio.com/api/tracks/search?master_id={$masterId}";
//     // dd($finalUrl);

//     $response = Http::withHeaders([
//         'Authorization' => "Bearer {$apiKey}",
//     ])->post($finalUrl);
    
//     if ($response->failed()) {
//         return response()->json([
//             'status' => 'error',
//             'message' => 'Failed to fetch tracks from SourceAudio API.',
//         ], 500);
//     }

//     $responseData = $response->json();
//     $tracks = $responseData['tracks'] ?? [];

//     $result = [];
//     foreach ($tracks as $track) {
//         $trackId = $track['track_id'] ?? null;
//         if ($trackId) {
//             $signedUrl = $this->getSignedUrlInternal($trackId);
//             $details = $this->getTrackDetailsById($trackId);

//             $result[] = [
//                 'track_id' => $trackId,
//                 'signed_url' => $signedUrl,
//                 'details' => $details,
//             ];
//         }
//     }
//     return response()->json([
//         'status' => 'success',
//         'message' => 'Tracks retrieved successfully',
//         'data' => $result,
//     ]);
// }



}
