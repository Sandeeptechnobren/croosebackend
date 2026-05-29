<?php

namespace App\DTOs;

class TrackDTO
{
    public static function fromArray(array $track): array
    {
        return [
            'track_id'    => $track['SourceAudio ID'] ?? null,
            'track_name'  => $track['Title'] ?? null,
            'artist'      => $track['Artist'] ?? null,
            'album'       => $track['Album'] ?? null,
            'duration'    => $track['Duration'] ?? null,
            'keywords'    => $track['Keywords'] ?? null,
            'file_name'   => $track['Filename'] ?? null,
            'album_image' => isset($track['Album Image'])? "https://ordiio.sourceaudio.com" . $track['Album Image']: null,
            
        ];
    }
}

 
