<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DelayDogTickets extends Model
{
    use HasFactory;
    protected $table = 'delay_dog_tickets';

    protected $fillable = [
        'uuid',
        'journey_id',
        'ticket_image_path',
    ];

    public function journey()
        {
            return $this->belongsTo(DelayDogJourney::class, 'journey_id');
        }
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
