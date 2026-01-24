<?php

use App\Http\Controllers\SecretsController;
use Illuminate\Support\Facades\Route;

Route::get('/', [SecretsController::class, 'create'])->name('secrets.create');
Route::get('/s/{token}', [SecretsController::class, 'show'])->name('secrets.show');
Route::get('/s/{token}/download', [SecretsController::class, 'download'])->name('secrets.download');
