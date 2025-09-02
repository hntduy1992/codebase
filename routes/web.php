<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\SiteController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::middleware('guest')->group(function () {
    Route::get('/', [SiteController::class, 'getHome'])->name('home');
    Route::get('/auth/login', [AuthController::class, 'getLogin'])->name('login');
    Route::post('/auth/checkLogin', [AuthController::class, 'checkLogin'])->name('checkLogin');
});

Route::middleware(['auth:sanctum'])->group(function () {
    Route::prefix('users')->group(function () {
        Route::get('/', function () {
            return Inertia::render('Home', []);
        })->name('users.index');
    });
});
