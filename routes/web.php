<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\SecretsController;
use App\Http\Controllers\SeoController;
use App\Http\Controllers\SuperAdminController;
use Illuminate\Support\Facades\Route;

// Home
Route::get('/', [SecretsController::class, 'create'])->name('home');

// SEO
Route::get('/robots.txt', [SeoController::class, 'robots']);
Route::get('/sitemap.xml', [SeoController::class, 'sitemap'])->name('sitemap');
Route::get('/sitemap.xsl', [SeoController::class, 'sitemapStylesheet']);

// Secrets
Route::get('/s/{token}', [SecretsController::class, 'show'])
    ->middleware('no.cache')
    ->name('secrets.show');
Route::get('/s/{token}/download', [SecretsController::class, 'download'])
    ->middleware(['throttle:60,1', 'no.cache'])
    ->name('secrets.download');

// Static pages
Route::view('/legal', 'legal')->name('legal');
Route::view('/how-it-works', 'how-it-works')->name('how-it-works');
Route::view('/use-cases', 'use-cases')->name('use-cases');
Route::get('/contact', [ContactController::class, 'email'])->name('contact.email');

// Admin (user secrets management)
Route::get('/admin', [AdminController::class, 'index'])->name('admin.index');
Route::post('/admin/request-access', [AdminController::class, 'requestAccess'])
    ->middleware('throttle.captcha:3,10')
    ->name('admin.requestAccess');
Route::get('/admin/verify/{token}', [AdminController::class, 'verify'])->name('admin.verify');
Route::get('/admin/dashboard', [AdminController::class, 'dashboard'])->name('admin.dashboard');
Route::post('/admin/logout', [AdminController::class, 'logout'])->name('admin.logout');
Route::post('/admin/secrets/{id}/revoke', [AdminController::class, 'revoke'])->name('admin.revoke');
Route::post('/admin/secrets/{id}/extend', [AdminController::class, 'extend'])->name('admin.extend');

// Super Admin (global statistics)
Route::get('/superadmin', [SuperAdminController::class, 'index'])->name('superadmin.index');
Route::post('/superadmin/request-access', [SuperAdminController::class, 'requestAccess'])
    ->middleware('throttle.captcha:3,10')
    ->name('superadmin.requestAccess');
Route::get('/superadmin/verify/{token}', [SuperAdminController::class, 'verify'])->name('superadmin.verify');
Route::get('/superadmin/dashboard', [SuperAdminController::class, 'dashboard'])->name('superadmin.dashboard');
Route::post('/superadmin/logout', [SuperAdminController::class, 'logout'])->name('superadmin.logout');
