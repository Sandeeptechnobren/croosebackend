<?php

namespace App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class MarkUserOnline
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

    Cache::put('user-online-'.$userId, true, now()->addMinutes(5));

    broadcast(new UserOnlineStatus($userId, true));
}

}
