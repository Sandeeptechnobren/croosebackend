<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class SonicSearchService
{
     private $baseUrl;  

    public function __construct()
     {
         $this->baseUrl = 'https://api.sourceaudio.com/api/sonicsearch/search';  
     }

    public function search(?int $trackSearch = null, ?string $searchId = null): array
     {
        $payload = [];
        if ($searchId !== null) {
            $payload['search_id'] = $searchId;
        } elseif ($trackSearch !== null) {
            $payload['track_search'] = $trackSearch;
        } else {
            throw new \InvalidArgumentException('Either trackSearch or searchId must be provided');
        }

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . env('SOURCEAUDIO_API_KEY'),
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ])->post($this->baseUrl, $payload);

        if ($response->failed()) {
            throw new \Exception('SonicSearch API error: ' . $response->body());
        }

        return $response->json();
     }

    // private $baseUrl = 'https://api.sourceaudio.com/v2/sonicsearch';
    // private $baseUrl = 'https://api.sourceaudio.com/sonicsearch';
    // public function search($query, $filters = [], $page = 1, $perPage = 10)
    // {
    //      $payload = [
    //         'query' => $query,
    //         'filters' => $filters,
    //         'pagination' => [
    //             'page' => $page,
    //             'per_page' => $perPage,
    //         ],
    //     ];

    //     $response = Http::withHeaders([
    //         'Authorization' => 'Bearer ' . env('SOURCEAUDIO_API_KEY'),
    //         'Content-Type'  => 'application/json',
    //     ])->post($this->baseUrl, $payload);
    //      dd($response->status(), $response->body()); 
    //     if ($response->failed()) {
    //         throw new \Exception("SonicSearch API failed: " . $response->body());
    //     }

    //     return $response->json();
    // }
}
