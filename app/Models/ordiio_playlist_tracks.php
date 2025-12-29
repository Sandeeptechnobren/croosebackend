<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ordiio_playlist_tracks extends Model
{
protected $fillable = [
    'user_id',
    'playlist_id',
    'track_id'
];

}
