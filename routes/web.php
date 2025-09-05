<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\SiteController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', [SiteController::class, 'getHome'])->name('home');
Route::get('/tin-tuc', [SiteController::class, 'getTinTuc'])->name('tinTuc');
Route::prefix('auth')->group(function () {
    Route::get('/login', [AuthController::class, 'getLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'checkLogin'])->name('checkLogin');
});


Route::middleware(['auth:sanctum'])->prefix('manage')->group(function () {
    Route::get('/dashboard', function () {
        return Inertia::render('auths/Dashboard', []);
    })->name('dashboard');
    Route::prefix('users')->group(function () {
        Route::get('/', function () {
            return Inertia::render('Home', []);
        })->name('users.index');
    });
    Route::prefix('auth')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    });
});
