<?php

use App\Http\Controllers\SecretsController;
use Illuminate\Support\Facades\Route;

Route::post('/secrets', [SecretsController::class, 'store'])
    ->middleware('throttle.captcha:10,1'); // 10 per minute
Route::get('/secrets/{token}', [SecretsController::class, 'fetch']);
Route::post('/secrets/{token}/read', [SecretsController::class, 'confirmRead']);
Route::post('/secrets/{adminToken}/revoke', [SecretsController::class, 'revoke']);
