<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\SecretsController;
use App\Http\Controllers\SuperAdminController;
use Illuminate\Support\Facades\Route;

Route::get('/', [SecretsController::class, 'create'])->name('secrets.create');
Route::get('/s/{token}', [SecretsController::class, 'show'])->name('secrets.show');
Route::get('/s/{token}/download', [SecretsController::class, 'download'])->name('secrets.download');

Route::view('/legal', 'legal')->name('legal');

Route::get('/admin', [AdminController::class, 'index'])->name('admin.index');
Route::post('/admin/request-access', [AdminController::class, 'requestAccess'])
    ->middleware('throttle.captcha:3,10') // 3 per 10 minutes
    ->name('admin.requestAccess');
Route::get('/admin/verify/{token}', [AdminController::class, 'verify'])->name('admin.verify');
Route::get('/admin/dashboard', [AdminController::class, 'dashboard'])->name('admin.dashboard');
Route::post('/admin/logout', [AdminController::class, 'logout'])->name('admin.logout');
Route::post('/admin/secrets/{id}/revoke', [AdminController::class, 'revoke'])->name('admin.revoke');
Route::post('/admin/secrets/{id}/extend', [AdminController::class, 'extend'])->name('admin.extend');

Route::get('/superadmin', [SuperAdminController::class, 'index'])->name('superadmin.index');
Route::post('/superadmin/request-access', [SuperAdminController::class, 'requestAccess'])
    ->middleware('throttle.captcha:3,10') // 3 per 10 minutes
    ->name('superadmin.requestAccess');
Route::get('/superadmin/verify/{token}', [SuperAdminController::class, 'verify'])->name('superadmin.verify');
Route::get('/superadmin/dashboard', [SuperAdminController::class, 'dashboard'])->name('superadmin.dashboard');
Route::post('/superadmin/logout', [SuperAdminController::class, 'logout'])->name('superadmin.logout');
