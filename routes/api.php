<?php

use App\Http\Controllers\SecretsController;
use Illuminate\Support\Facades\Route;

Route::post('/secrets', [SecretsController::class, 'store']);
Route::get('/secrets/{token}', [SecretsController::class, 'fetch']);
Route::post('/secrets/{token}/read', [SecretsController::class, 'confirmRead']);
