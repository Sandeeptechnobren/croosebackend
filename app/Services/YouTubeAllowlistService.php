<<<<<<< HEAD
<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use App\Models\WhitelistChannel;
use Illuminate\Support\Facades\DB;
class YouTubeAllowlistService
{
    protected string $apiUrl = 'https://ordiio.sourceaudio.com/api/contentid/whitelistChannel';
    protected string $apiUrl1 = 'https://ordiio.sourceaudio.com/api/contentid/whitelist';
    protected string $removeUrl = 'https://ordiio.sourceaudio.com/api/contentid/whitelistRemove';
    protected string $apiKey;

    public function __construct()
    {
        $this->apiKey = env('SOURCEAUDIO_API_KEY');
    }

    /**
     * Send allowlist request to SourceAudio API
     */
    public function allowlist($request, $userId): array
    {
        $channelId     = $request->input('channel_id');
        $releaseClaims = $request->input('release_claims', 1);
        $note          = $request->input('note');
      DB::beginTransaction();
       try {

        $alredy_exits = WhitelistChannel::where('user_id', $userId)->where('channel_id',$channelId)->first();
         
        if ($alredy_exits)
         {
            DB::rollBack();
            return ['success' => false, 'status'  => 409, 'error' => "this Channel Already white listed"];
         }

        $payload = [
            'token' => $this->apiKey,
            'channel' => [
                [
                    'channel_id'     => $channelId,
                    'release_claims' => $releaseClaims,
                    'note'           => $note,
                ],
            ],
        ];

        $response = Http::post($this->apiUrl, $payload);

        if (!$response->successful()) {
            DB::rollBack();
            return ['success' => false, 'status'  => $response->status(), 'error' => $response->body()];
        }

        $responseData = $response->json();
        
            $insertData = [];
            if (isset($responseData['channel']) && is_array($responseData['channel'])) {
                
                foreach ($responseData['channel'] as $channel) {
                    
                    if (!isset($channel['channel_id'])) continue;
    
                    $insertData[] = [
                        'user_id'    => $userId,
                        'channel_id' => $channel['channel_id'],
                        'white_id'   => $channel['white_id'],
                        'created_at' => now(),
                    ];
                }

                if (!empty($insertData)) {
                    WhitelistChannel::insert($insertData);
                }
            }
          

            if (isset($responseData['error'])) {
                DB::rollBack();
                return ['success' => false,'status'  => 400,'error'   => $responseData['error'],];
            }
            DB::commit();
            return ['success' => true, 'status'  => 200,
                'data'  => [
                    'channel' => $responseData['channel'] ?? [],
                ],
            ];

        } catch (\Exception $e){
            DB::rollBack();
            return ['success'=> false, 'status'=> 500,'error'=> $e->getMessage()];
         }
    }

    public function get_white_list($userId)
     {

        $whitelist = WhitelistChannel::where('user_id', $userId)->pluck('channel_id')->toArray();

        if (empty($whitelist)) {
            return ['success' => false, 'status' => 404, 'error' => 'No whitelisted channels found.'];
        }
 
        $payload = [
            'token'   => $this->apiKey,
            'channel' => $whitelist,
        ];
 
        $response = Http::post($this->apiUrl1, $payload);
       
        if (!$response->successful()) {
            return ['success' => false, 'status'=> $response->status(), 'error' => $response->body()];
        }

        $data = $response->json();
      
        $filtered = collect($data['whitelist'])->whereIn('channel_id', $whitelist)->unique('channel_id')->values()->toArray();
             
        $formattedResponse =['pg' => $data['pg'] ?? 0, 'show' => $data['show'] ?? count($filtered), 'total' => $data['total'] ?? count($filtered),'whitelist' => $filtered];
        return ['success' => true,  'status'  => 200, 'data' => $formattedResponse];

     }
 

    public function removeWhitelistData(int $userId, array $channels): array
     {
        if (empty($channels)) {
            return ['success' => false, 'status'  => 400, 'error'   => 'Channel IDs are required for removal.'];
        }
 
        $payload = [
            'token'   => $this->apiKey,
            'channel' => $channels,
        ];
 
        $response = Http::post($this->removeUrl, $payload);

        if (!$response->successful()) {
            return ['success' => false, 'status'  => $response->status(), 'error' => $response->body()];
        }
 
        $responseData = $response->json();
 
        WhitelistChannel::where('user_id', $userId)->whereIn('channel_id', $channels)->delete();

        return ['success' => true, 'status'  => 200, 'message' => 'Whitelist Data Deleted Successfully',  'data' => $responseData];
     }

}
||||||| parent of b872fe7 (Live code)
=======
<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use App\Models\WhitelistChannel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Models\OrdiioSubscription;

class YouTubeAllowlistService
{
    protected string $apiUrl = 'https://ordiio.sourceaudio.com/api/contentid/whitelistChannel';
    protected string $apiUrl1 = 'https://ordiio.sourceaudio.com/api/contentid/whitelist';
    protected string $removeUrl = 'https://ordiio.sourceaudio.com/api/contentid/whitelistRemove';
    protected string $apiKey;

    public function __construct()
    {
        $this->apiKey = env('SOURCEAUDIO_API_KEY');
    }

    /**
     * Send allowlist request to SourceAudio API
     */
    // public function allowlist($request, $userId): array
    // {
    //     $user=Auth::user();
    //     $subscription_type = OrdiioSubscription::where('customer_email', $user->email)->value('subscription_type');
    //     $white_listed_channel_count = WhitelistChannel::where('user_id', $userId)->count();
    //     if ($subscription_type === 'creator' && $white_listed_channel_count >= 1) {
    //         return response()->json([
    //             "message" => "Whitelist limit reached"
    //         ]);
    //     }
    //     $channelId     = $request->input('channel_id');
    //     $releaseClaims = $request->input('release_claims', 1);
    //     $note          = $request->input('note');

    //     DB::beginTransaction();
    //     try {
    //     $alredy_exits = WhitelistChannel::where('user_id', $userId)->where('channel_id',$channelId)->first();
    //     if ($alredy_exits)
    //     {
    //     DB::rollBack();
    //     return ['success' => false, 'status'  => 409, 'error' => "this Channel Already white listed"];
    //     }
    //     $payload = [
    //         'token' => $this->apiKey,
    //         'channel' => [
    //             [
    //                 'channel_id'     => $channelId,
    //                 'release_claims' => $releaseClaims,
    //                 'note'           => $note,
    //             ],
    //         ],
    //     ];

    //     $response = Http::post($this->apiUrl, $payload);

    //     if (!$response->successful()) {
    //         DB::rollBack();
    //         return ['success' => false, 'status'  => $response->status(), 'error' => $response->body()];
    //     }

    //     $responseData = $response->json();
        
    //         $insertData = [];
    //         if (isset($responseData['channel']) && is_array($responseData['channel'])) {
                
    //             foreach ($responseData['channel'] as $channel) {
                    
    //                 if (!isset($channel['channel_id'])) continue;
    
    //                 $insertData[] = [
    //                     'user_id'    => $userId,
    //                     'channel_id' => $channel['channel_id'],
    //                     'white_id'   => $channel['white_id'],
    //                     'created_at' => now(),
    //                 ];
    //             }

    //             if (!empty($insertData)) {
    //                 WhitelistChannel::insert($insertData);
    //             }
    //         }
          

    //         if (isset($responseData['error'])) {
    //             DB::rollBack();
    //             return ['success' => false,'status'  => 400,'error'   => $responseData['error'],];
    //         }
    //         DB::commit();
    //         return ['success' => true, 'status'  => 200,
    //             'data'  => [
    //                 'channel' => $responseData['channel'] ?? [],
    //             ],
    //         ];

    //     } catch (\Exception $e){
    //         DB::rollBack();
    //         return ['success'=> false, 'status'=> 500,'error'=> $e->getMessage()];
    //      }
    // }
public function allowlist($request, $userId): array
{
    $user = Auth::user();

    $subscription_type = OrdiioSubscription::where('customer_email', $user->email)
        ->value('subscription_type');

    $white_listed_channel_count = WhitelistChannel::where('user_id', $userId)->count();

    if ($subscription_type === 'creator' && $white_listed_channel_count >= 1) {
        return [
            'success' => false,
            'status'  => 403,
            'message' => 'Whitelist limit reached'
        ];
    }

    $channelId     = $request->input('channel_id');
    $releaseClaims = $request->input('release_claims', 1);
    $note          = $request->input('note');

    DB::beginTransaction();

    try {
        $alreadyExists = WhitelistChannel::where('user_id', $userId)
            ->where('channel_id', $channelId)
            ->first();

        if ($alreadyExists) {
            DB::rollBack();
            return [
                'success' => false,
                'status'  => 409,
                'message' => 'This channel is already whitelisted'
            ];
        }

        $payload = [
            'token'   => $this->apiKey,
            'channel' => [
                [
                    'channel_id'     => $channelId,
                    'release_claims' => $releaseClaims,
                    'note'           => $note,
                ],
            ],
        ];

        $response = Http::post($this->apiUrl, $payload);

        if (!$response->successful()) {
            DB::rollBack();
            return [
                'success' => false,
                'status'  => $response->status(),
                'message' => $response->body()
            ];
        }

        $responseData = $response->json();
        $insertData   = [];

        if (isset($responseData['channel']) && is_array($responseData['channel'])) {
            foreach ($responseData['channel'] as $channel) {
                if (!isset($channel['channel_id'])) {
                    continue;
                }

                $insertData[] = [
                    'user_id'    => $userId,
                    'channel_id' => $channel['channel_id'],
                    'white_id'   => $channel['white_id'] ?? null,
                    'created_at' => now()
                ];
            }

            if (!empty($insertData)) {
                WhitelistChannel::insert($insertData);
            }
        }

        if (isset($responseData['error'])) {
            DB::rollBack();
            return [
                'success' => false,
                'status'  => 400,
                'message' => $responseData['error']
            ];
        }

        DB::commit();

        return [
            'success' => true,
            'status'  => 200,
            'message' => 'Channel whitelisted successfully',
            'data'    => [
                'channel' => $responseData['channel'] ?? []
            ]
        ];

    } catch (\Exception $e) {
        DB::rollBack();
        return [
            'success' => false,
            'status'  => 500,
            'message' => $e->getMessage()
        ];
    }
}

    public function get_white_list($userId)
     {

        $whitelist = WhitelistChannel::where('user_id', $userId)->pluck('channel_id')->toArray();

        if (empty($whitelist)) {
            return ['success' => false, 'status' => 404, 'error' => 'No whitelisted channels found.'];
        }
 
        $payload = [
            'token'   => $this->apiKey,
            'channel' => $whitelist,
        ];
 
        $response = Http::post($this->apiUrl1, $payload);
       
        if (!$response->successful()) {
            return ['success' => false, 'status'=> $response->status(), 'error' => $response->body()];
        }

        $data = $response->json();
      
        $filtered = collect($data['whitelist'])->whereIn('channel_id', $whitelist)->unique('channel_id')->values()->toArray();
             
        $formattedResponse =['pg' => $data['pg'] ?? 0, 'show' => $data['show'] ?? count($filtered), 'total' => $data['total'] ?? count($filtered),'whitelist' => $filtered];
        return ['success' => true,  'status'  => 200, 'data' => $formattedResponse];

     }
 

    public function removeWhitelistData(int $userId, array $channels): array
     {
        if (empty($channels)) {
            return ['success' => false, 'status'  => 400, 'error'   => 'Channel IDs are required for removal.'];
        }
 
        $payload = [
            'token'   => $this->apiKey,
            'channel' => $channels,
        ];
 
        $response = Http::post($this->removeUrl, $payload);

        if (!$response->successful()) {
            return ['success' => false, 'status'  => $response->status(), 'error' => $response->body()];
        }
 
        $responseData = $response->json();
 
        WhitelistChannel::where('user_id', $userId)->whereIn('channel_id', $channels)->delete();

        return ['success' => true, 'status'  => 200, 'message' => 'Whitelist Data Deleted Successfully',  'data' => $responseData];
     }

}
>>>>>>> b872fe7 (Live code)
