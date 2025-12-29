<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Conversation extends Model
{
    use HasFactory;
    protected $table = 'conversations';
    protected $fillable = [
        'client_id',
        'space_id',
        'customer_id',
        'whatsapp_number',
        'user_message',
        'bot_response',
        'session_id',
        'current_step',
        'intent_detected',
        'context_data',
        'message_timestamp',
    ];
    protected $casts = [
        'context_data' => 'array',
        'message_timestamp' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
    public function client()
    {
        return $this->belongsTo(Client::class);
    }
    public function space()
    {
        return $this->belongsTo(Space::class);
    }
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}
