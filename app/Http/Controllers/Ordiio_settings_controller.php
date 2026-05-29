<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;
use App\Http\Requests\YouTubeAllowlistRequest;
use App\Services\YouTubeAllowlistService;
use App\Models\WhitelistChannel;

class Ordiio_settings_controller extends Controller
{
    protected YouTubeAllowlistService $allowlistService;

    public function __construct(YouTubeAllowlistService $allowlistService)
    {
        $this->allowlistService = $allowlistService;
    }

    public function youtube_allowlist(YouTubeAllowlistRequest $request)
     {
        $userId = Auth::user()->id;
        
        $result = $this->allowlistService->allowlist($request, $userId);

        if (!$result['success']) {
            return response()->json(['status' => $result['status'], 'error'  => $result['error']], $result['status']);
        }
 
        return response()->json(['status' => 200, 'data' => $result['data']]);
 
     }

    public function whitelist_data(Request $request)
     {
        $userId = Auth::user()->id;
        $result = $this->allowlistService->get_white_list($userId);
 
        if (!$result['success']) {
            return response()->json(['status' => $result['status'], 'error'  => $result['error']], $result['status']);
         }
    
        return response()->json(['status' => 200, 'data' => $result['data']]);

     }

   public function whitelist_data_remove(Request $request)
     {
        $userId = Auth::user()->id;
        $apiKey = env('SOURCEAUDIO_API_KEY');
        
        $channels = $request->input('channel');
        
        $result = $this->allowlistService->removeWhitelistData($userId, $channels);

        return response()->json(['status'  => $result['status'], 'message' => $result['message'] ?? null, 'error'   => $result['error'] ?? null,
            'data' => $result['data'] ?? null, ], $result['status']);
  
     }
}
