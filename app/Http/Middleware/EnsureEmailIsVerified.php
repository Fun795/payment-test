<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureEmailIsVerified
{
    public function handle(Request $request, Closure $next)
    {
        $user = auth()->user();

        if (!$user || empty($user->token->email_verified) || $user->token->email_verified !== true) {
            return response()->json(['error' => 'Email not verified.'], 403);
        }

        return $next($request);
    }
}
