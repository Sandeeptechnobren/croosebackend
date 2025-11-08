<?php
namespace App\Helpers;
use Illuminate\Support\Facades\Http;
class TrackHelper
{
public static function getSignedUrlInternal($trackId)
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
public static function formatTrackDetails($track)
    {
        return [
            'track_id'    => $track['SourceAudio ID'] ?? null,
            'track_name'  => $track['Title'] ?? null,
            'artist'      => $track['Artist'] ?? null,
            'album'       => $track['Album'] ?? null,
            'duration'    => $track['Duration'] ?? null,
            'keywords'    => $track['Keywords'] ?? null,
            // 'file_name'   => $track['Filename'] ?? null,
            'master_id'   => $track['Master ID'] ?? null,
            // 'description' => $track['Description']?? null,
            // 'mood'        => $track['Mood']?? null,
            // 'genre'       => $track['Genre']??null,
            // 'BPM'         => $track['BPM'] ?? null,
            // 'key'         => $track['Key']??null,
            // 'release_date'=> $track['Release Date']??null,
            // 'perfect_for'=> $track['Custom: Perfect For']??null,
            // 'region'      => $track['Custom: Region']??null,
            'Album_Image' => isset($track['Album Image'])? "https://ordiio.sourceaudio.com/" . $track['Album Image']: null,
            // 'signedURL'   => self::getSignedUrlInternal($track['SourceAudio ID'] ?? null),
        ];
    }
public static function formatSingleTrackDetails($track)
    {
        return [
            'track_id'    => $track['SourceAudio ID'] ?? null,
            'track_name'  => $track['Title'] ?? null,
            'artist'      => $track['Artist'] ?? null,
            'album'       => $track['Album'] ?? null,
            'duration'    => $track['Duration'] ?? null,
            'keywords'    => $track['Keywords'] ?? null,
            'file_name'   => $track['Filename'] ?? null,
            'master_id'   => $track['Master ID'] ?? null,
            'description' => $track['Description']?? null,
            'mood'        => $track['Mood']?? null,
            'genre'       => $track['Genre']??null,
            'BPM'         => $track['BPM'] ?? null,
            'key'         => $track['Key']??null,
            'release_date'=> $track['Release Date']??null,
            'perfect_for'=> $track['Custom: Perfect For']??null,
            'region'      => $track['Custom: Region']??null,
            'Album_Image' => isset($track['Album Image'])? "https://ordiio.sourceaudio.com/" . $track['Album Image']: null,
            'signedURL'   => self::getSignedUrlInternal($track['SourceAudio ID'] ?? null),
        ];
    }
// public static function formatTrackDetails($track)
// {
//     return [
//         'track_id'    => $track['SourceAudio ID'] ?? null,
//         'track_name'  => $track['Title'] ?? null,
//         'artist'      => $track['Artist'] ?? null,
//         'album'       => $track['Album'] ?? null,
//         'duration'    => $track['Duration'] ?? null,
//         'keywords'    => $track['Keywords'] ?? null,
//         'file_name'   => $track['Filename'] ?? null,
//         'master_id'   => $track['Master ID'] ?? null,
//         'description' => $track['Description'] ?? null,
//         'mood'        => $track['Mood'] ?? null,
//         'genre'       => $track['Genre'] ?? null,
//         'BPM'         => $track['BPM'] ?? null,
//         'key'         => $track['Key'] ?? null,
//         'release_date'=> $track['Release Date'] ?? null,
//         'perfect_for' => $track['Custom: Perfect For'] ?? null,
//         'region'      => $track['Custom: Region'] ?? null,
//         'Album_Image' => isset($track['Album Image']) 
//                             ? "https://ordiio.sourceaudio.com/" . $track['Album Image'] 
//                             : null,
//         'signedURL'   => self::getSignedUrlInternal($track['SourceAudio ID'] ?? null),
//     ];
// }
  
}
