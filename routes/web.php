<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// Authentication routes
Route::post('/login',  [App\Http\Controllers\Auth\AuthController::class, 'login']);
Route::post('/logout', [App\Http\Controllers\Auth\AuthController::class, 'logout'])->middleware('auth:sanctum');

// SPA catch-all
Route::get('/{any}', function () {
    return view('app');
})->where('any', '.*');
