<?php

namespace App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class MarkUserOffline
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    public function handle($event)
{
    $userId = $event->user->id;

    Cache::forget('user-online-'.$userId);

    broadcast(new UserOnlineStatus($userId, false));
}

}
