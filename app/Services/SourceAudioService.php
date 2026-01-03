<<<<<<< HEAD
<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use App\DTOs\CuratedDTO;

class SourceAudioService
{
    protected string $apiKey;
    protected string $baseUrl = 'https://ordiio.sourceaudio.com/api';

    public function __construct()
    {
        $this->apiKey = env('SOURCEAUDIO_API_KEY');
    }
 
    /**
     * Get all published playlists
     */
   public function getPublishedPlaylists()
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->apiKey}",
            ])->post("{$this->baseUrl}/playlists/getPublished");

            if ($response->failed()) {
                return [];
            }

            return $response->json();

        } catch (\Exception $e) {
            return [];
        }
    }


    /**
     * Get playlist details by ID
     */
   public function getPlaylistById(int $playlistId)
    {
        try {
            $response = Http::asForm()
                ->withHeaders(['Authorization' => "Bearer {$this->apiKey}"])
                ->post("{$this->baseUrl}/playlists/getById", [
                    'playlist_id' => $playlistId,
                ]);

            if ($response->failed()) {
               
                return [];
            }

            return $response->json();

        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Get signed download URL for a specific track
     */
    public function getSignedUrl(int $trackId)
     {
        try {
            $response = Http::timeout(5)->get("{$this->baseUrl}/tracks/downloadAuth", [
                'track_id' => $trackId,
                'format'   => 'mp3',
                'duration' => 3600,
                'token'    => $this->apiKey,
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

    public function getCuratedPlaylistTracks(int $playlistId): array
    {
        try {
            $response = Http::asForm()
                ->withHeaders([
                    'Authorization' => "Bearer {$this->apiKey}",
                ])
                ->post("{$this->baseUrl}/playlists/getById", [
                    'playlist_id' => $playlistId,
                ]);

            if ($response->failed()) {
                return [];
            }

             $data = json_decode($response->body(), true, 512, JSON_INVALID_UTF8_IGNORE);
                if (empty($data['tracks'])) {
                    return [];
                }
 
                return array_map(
                    fn($track) => CuratedDTO::fromArray($track),
                    array_filter($data['tracks'], fn($t) => !empty($t['SourceAudio ID']))
                );
  
        } catch (\Exception $e) {
            return [];
        }
    }
}
||||||| parent of b872fe7 (Live code)
=======
<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use App\DTOs\CuratedDTO;

class SourceAudioService
{
    protected string $apiKey;
    protected string $baseUrl = 'https://ordiio.sourceaudio.com/api';

    public function __construct()
    {
        $this->apiKey = env('SOURCEAUDIO_API_KEY');
    }
// public function getPublishedPlaylists()
//     {
//         try {
//             $response = Http::withHeaders([
//                 'Authorization' => "Bearer {$this->apiKey}",
//             ])->post("{$this->baseUrl}/playlists/getPublished");

//             if ($response->failed()) {
//                 return [];
//             }

//             return $response->json();

//         } catch (\Exception $e) {
//             return [];
//         }
//     }
public function getPublishedPlaylists()
{
    try {
        $payload = ['onlyMasters' => 1];
        $response = Http::withHeaders(['Authorization' => "Bearer {$this->apiKey}",])->post("{$this->baseUrl}/playlists/getPublished", $payload);
        if ($response->failed()) {
            return [];
        }
        return $response->json();
    } catch (\Exception $e) {
        return [];
    }
}

public function getPlaylistById(int $playlistId)
    {
        try {
            $response = Http::asForm()->withHeaders(['Authorization' => "Bearer {$this->apiKey}"])->post("{$this->baseUrl}/playlists/getById", ['playlist_id' => $playlistId,]);
            if ($response->failed()) {
                return [];
            }
            return $response->json();
        } catch (\Exception $e) {
            return [];
        }
    }
public function getSignedUrl(int $trackId)
    {
    try {
        $response = Http::timeout(5)->get("{$this->baseUrl}/tracks/downloadAuth", [
            'track_id' => $trackId,
            'format'   => 'mp3',
            'duration' => 3600,
            'token'    => $this->apiKey,
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

public function getCuratedPlaylistTracks(int $playlistId): array
    {
        try {
            $response = Http::asForm()
                ->withHeaders([
                    'Authorization' => "Bearer {$this->apiKey}",
                ])
                ->post("{$this->baseUrl}/playlists/getById", [
                    'playlist_id' => $playlistId,
                ]);
            if ($response->failed()) {
                return [];
            }
            $data = json_decode($response->body(), true, 512, JSON_INVALID_UTF8_IGNORE);
                if (empty($data['tracks'])) {
                    return [];
                }
                return array_map(
                    fn($track) => CuratedDTO::fromArray($track),
                    array_filter($data['tracks'], fn($t) => !empty($t['SourceAudio ID']))
                );
        } catch (\Exception $e) {
            return [];
        }
    }
}
>>>>>>> b872fe7 (Live code)
