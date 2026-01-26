<?php

use App\Http\Controllers\Payments\PaymentController;
use Illuminate\Support\Facades\Route;

Route::prefix('payments')
    ->whereUuid('paymentUuid')
    ->group(function () {
        Route::post('/', [PaymentController::class, 'create']);
        Route::patch('/{paymentUuid}/process', [PaymentController::class, 'process']);
        Route::middleware('rate-limiter')->get('/{paymentUuid}', [PaymentController::class, 'get']);
    });
