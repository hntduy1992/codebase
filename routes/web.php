<?php

use App\Http\Controllers\SiteController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/', [SiteController::class, 'getHome'])->name('home');
});

Route::middleware(['auth:sanctum'])->group(function () {

});
