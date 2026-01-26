<?php

use App\Exceptions\ConflictException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Validation\ValidationException;
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
            'rate-limiter' => \App\Http\Middleware\RateLimiter::class
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
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

        // Conflict
        $exceptions->renderable(function (ConflictException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 409);
        });

        // Other exceptions
        $exceptions->renderable(function (Throwable $e) {
            if (!config('app.debug')) {
                return response()->json([
                    'message' => 'Something went wrong, please try again later.',
                ], 500);
            }
        });
    })->create();
