<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use KeycloakGuard\Exceptions\TokenException;

class CheckRevokedSession
{
    public function handle(Request $request, Closure $next)
    {
        $token = Auth::user()?->token;
        $sid = $token->sid;

        // Проверяем, не отозвана ли сессия
        if (($sid && Cache::has("revoked_session:$sid"))) {
            throw new TokenException('Session revoked');
        }

        return $next($request);
    }
}
