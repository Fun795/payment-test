<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Support\Facades\RateLimiter as RateLimiterFacade;

class RateLimiter
{
    /**
     * Handle an incoming request.
     *
     * @param \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response) $next
     */
    public function handle($request, Closure $next)
    {
        $limiterKey = $request->ip();
        if (!RateLimiterFacade::attempt($limiterKey, 120, fn() => null, 120)) {
            $seconds = RateLimiterFacade::availableIn($limiterKey);
            $humanTimeDiff = now()->addSeconds($seconds)->diffForHumans([
                'parts' => 1,
            ]);

            throw new ThrottleRequestsException("Request limit reached. The next request can be sent $humanTimeDiff");
        }
        return $next($request);
    }
}
