<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Payments\PaymentController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::post('/login-url', [AuthController::class, 'getLoginUrl']);  // Начало авторизации (получить redirect URL)
    Route::get('/callback', [AuthController::class, 'callback'])  // Callback от Keycloak
    ->name('auth.callback');
    Route::post('/token', [AuthController::class, 'getToken']); // Обмен временного кода на токены (web / mobile)
    Route::post('/refresh-token', [AuthController::class, 'refreshToken']);  // Обновление access token
    Route::post('/logout', [AuthController::class, 'logout'])   ; // Logout
    Route::post('/back-channel-logout', [AuthController::class, 'backChannelLogout']); // Back channel logout от Keycloak
});

Route::middleware(['auth:api', 'verified', 'check.revoked'])->group(function () {
    Route::get('/protected/test', [\App\Http\Controllers\Api\Auth\AuthController::class, 'test']);
});

Route::prefix('payments')
    ->whereUuid('paymentUuid')
    ->group(function () {
        Route::post('/', [PaymentController::class, 'create']);
        Route::patch('/{paymentUuid}/process', [PaymentController::class, 'process']);
        Route::middleware('rate-limiter')->get('/{paymentUuid}', [PaymentController::class, 'get']);
    });
