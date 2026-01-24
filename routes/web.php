<?php

use App\Http\Controllers\SecretsController;
use Illuminate\Support\Facades\Route;

Route::get('/', [SecretsController::class, 'create'])->name('secrets.create');
