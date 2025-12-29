<?php

namespace App\DTOs;

class CuratedDTO
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
            'master_id'   => $track['Master ID'] ?? null,
            // 'album_image' => isset($track['Album Image']) ? "https://ordiio.sourceaudio.com" . $track['Album Image']: null,
            'album_image' => !empty($track['Album Image']) ? "https://ordiio.sourceaudio.com/" . $track['Album Image'] : null,
            
        ];
    }
}

 
