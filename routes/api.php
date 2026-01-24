<?php

use App\Http\Controllers\SecretsController;
use Illuminate\Support\Facades\Route;

Route::post('/secrets', [SecretsController::class, 'store']);
