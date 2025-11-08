<?php

namespace App\Services\Ordiio;

use Illuminate\Support\Facades\Http;
use App\Helpers\TrackHelper;

class SourceAudioApiService
{
    protected $apiToken;

    public function __construct()
    {
        $this->apiToken = env('SOURCEAUDIO_API_KEY');
    }
public function searchTracks(array $filters = [], $limit = 50, $offset = 0)
    {
        $baseUrl = "https://ordiio.sourceaudio.com/api/tracks/search";
        $searchTerms = [];
        foreach ($filters as $key => $value) {
            if (is_array($value)) {
                $searchTerms[] = implode(' ', $value);
            } else {
                $searchTerms[] = $value;
            }
        }
        $searchString = trim(implode(' ', $searchTerms));
        $query = [
            'token'  => $this->apiToken,
            'show'   => $limit,
            'pg'     => (int)($offset / $limit),
            'orderby'=> 'release_date',
            'dir'    => 'desc',
            'raw'    => 1,
            's'      => $searchString
        ];
        $response = Http::post($baseUrl, $query);
        if ($response->failed()) {
            throw new \Exception("SourceAudio API error: " . $response->body());
        }
        $data = $response->json();
        $tracks = collect($data['tracks'] ?? []);
        return $tracks->map(function ($track) {
            return [
                'track_id'    => $track['SourceAudio ID'] ?? null,
                'track_name'  => $track['Title'] ?? null,
                'artist'      => $track['Artist'] ?? null,
                'album'       => $track['Album'] ?? null,
                'label'       => $track['Label'] ?? null,
                'genre'       => $track['Genre'] ?? null,
                'mood'        => $track['Mood'] ?? null,
                'composer'    => $track['Composer'] ?? null,
                'length'      => $track['Duration'] ?? null,
                'year'        => $track['Year'] ?? null,
                'description' => $track['Description'] ?? null,
                'Album_Image' => isset($track['Album Image'])
                    ? "https://ordiio.sourceaudio.com" . $track['Album Image']
                    : null,
                'signedURL'   => TrackHelper::getSignedUrlInternal($track['SourceAudio ID'] ?? null),
            ];
        });
    }
public function listTracks($searchQuery = '', $limit = 100, $offset = 0)
    {
        $baseUrl = "https://ordiio.sourceaudio.com/api/tracks/search";
        $response = Http::get($baseUrl, [
            's'      => $searchQuery,
            'limit'  => $limit,
            'offset' => $offset,
            'token'  => $this->apiToken,
            'onlyMasters' => 1,
        ]);
        if ($response->failed()) {
            throw new \Exception($response->body());
        }
        $data = $response->json();
        return collect($data['tracks'] ?? [])
            ->map(fn($track) => TrackHelper::formatTrackDetails($track));
    }


public function getStems($master_id)
    {
        $baseUrl="https://ordiio.sourceaudio.com/api/tracks/search?master_id={$master_id}";
        $response = Http::post($baseUrl, [
                'token'  => $this->apiToken,
            ]); 
        $data = $response->json();
        return collect($data['tracks'] ?? [])->map(fn($track) => TrackHelper::formatTrackDetails($track));
    }
public function individualTrackData($track_id)
{
    $baseUrl = "https://ordiio.sourceaudio.com/api/tracks/getById";
    $response = Http::post($baseUrl, [
        'token'     => $this->apiToken,
        'track_id'  => $track_id,
    ]);
    if ($response->failed()) {
        return null;
    }
    $track = $response->json();
    return TrackHelper::formatSingleTrackDetails($track);
}


}
