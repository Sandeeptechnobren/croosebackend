<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class SpaceIqDocs extends Model

{
    protected $table = 'spaces_iq_docs';

    protected $fillable=[
        'space_id',
        'file_file',
        'file_name',
        'mime_type',
         'uuid',

    ];
     protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }
}
