<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

class UserTyping implements ShouldBroadcast
{
    public $from;
    public $to;
    public $typing;

    public function __construct($from, $to, $typing)
    {
        $this->from = $from;
        $this->to = $to;
        $this->typing = $typing;
    }

    public function broadcastOn()
    {
        return new Channel('typing-status');
    }
}
