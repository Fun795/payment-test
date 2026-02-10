<?php

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Validation\ValidationException;
use KeycloakGuard\Exceptions\ResourceAccessNotAllowedException;
use KeycloakGuard\Exceptions\TokenException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->appendToGroup('api', \App\Http\Middleware\SnakeCaseRequest::class);
        $middleware->appendToGroup('api', \App\Http\Middleware\CamelCaseJsonResponse::class);
        $middleware->alias([
            'verified' => \App\Http\Middleware\EnsureEmailIsVerified::class,
            'check.revoked' => \App\Http\Middleware\CheckRevokedSession::class,
            'rate-limiter' => \App\Http\Middleware\RateLimiter::class
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Keycloak token - access
        $exceptions->renderable(function (TokenException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 401)->withHeaders([
                'X-Auth-Error' => 'invalid_access_token'
            ]);
        });

        // Keycloak token - refresh
        $exceptions->renderable(function (AuthenticationException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 401)->withHeaders([
                'X-Auth-Error' => 'invalid_refresh_token'
            ]);
        });

        $exceptions->renderable(function (ResourceAccessNotAllowedException|AccessDeniedHttpException|AuthorizationException $e) {
            return response()->json([
                'message' => 'Недостаточно прав',
            ], 403);
        });

        // Resource not found
        $exceptions->renderable(function (NotFoundHttpException $e) {
            return response()->json([
                'message' => 'Resource not found',
            ], 404);
        });

        // Too many requests
        $exceptions->renderable(function (ThrottleRequestsException $e) {
            return response()->json([
                'message' => !empty($e->getMessage()) ? $e->getMessage() : 'Too many requests, please try again later.',
            ], 429);
        });

        // Validation
        $exceptions->renderable(function (ValidationException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'errors' => $e->errors(),
            ], $e->status);
        });

        //Other unexpected errors
        $exceptions->renderable(function (Throwable $e) {
            if (!config('app.debug')) {
                return response()->json([
                    'message' => 'Что-то пошло не так, попробуйте позднее',
                ], 500);
            }
        });
    })->create();
