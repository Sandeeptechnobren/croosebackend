<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ApiCacheMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if (app()->environment('local')) {
            return $next($request);
        }
        $key = $this->makeCacheKey($request);
        if (Cache::has($key)) {
            return response(Cache::get($key))
                ->header('X-Cache', 'HIT');
        }
        $response = $next($request);
        if (in_array($response->getStatusCode(), [200, 201])) {
            Cache::put($key, $response->getContent(), now()->addMinutes(10));
        }
        return $response->header('X-Cache', 'MISS');
    }
    protected function makeCacheKey(Request $request)
    {
        return 'api_cache:' . md5($request->fullUrl() . json_encode($request->all()));
    }
}
