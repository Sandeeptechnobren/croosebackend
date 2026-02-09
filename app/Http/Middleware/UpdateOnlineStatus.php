<?php

namespace App\Http\Middleware;

use Closure;
use Cache;
use App\Events\UserOnlineStatus;

class UpdateOnlineStatus
{
    public function handle($request, Closure $next)
    {
        if (auth()->check()) {

            $key = 'user-online-'.auth()->id();

            if (!Cache::has($key)) {
                broadcast(new UserOnlineStatus(auth()->id(), true));
            }

            Cache::put($key, true, now()->addMinutes(2));
        }

        return $next($request);
    }
}

