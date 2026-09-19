<?php

use App\Http\Controllers\SecretsController;
use App\Services\TokenService;
use Illuminate\Support\Facades\Route;

Route::post('/secrets', [SecretsController::class, 'store'])
    ->middleware(['throttle:daily', 'throttle.pow:3,1'])
    ->name('secrets.store');

Route::middleware(['throttle:20,1,secret-api', 'no.cache'])
    ->where(['token' => TokenService::PUBLIC_TOKEN_PATTERN])
    ->group(function () {
        Route::get('/secrets/{token}', [SecretsController::class, 'fetch'])->name('secrets.fetch');
        Route::post('/secrets/{token}/read', [SecretsController::class, 'confirmRead'])->name('secrets.confirmRead');
    });
