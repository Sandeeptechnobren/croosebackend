<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ordiio_playlists extends Model
{
    protected $fillable = [
    'user_id',
    'email',
    'playlist_name',
    'description',
    ];
}
