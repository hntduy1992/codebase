<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/test', function (Request $request) {
        return response()->json([
            'data' => [1, 2, 3, 4, 5, 6, 7, 8, 9, 0]
        ]);
    })->name('api.test');
});
