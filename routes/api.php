<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ChatController;
use App\Http\Controllers\Api\MapsController;
use App\Http\Controllers\Api\RouteController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', function (Request $request) {
    return 'Halo';
});
Route::controller(AuthController::class)->group(function () {
    Route::post('register', 'register');
    Route::post('login', 'login');
    Route::post('forgot-password', 'forgotPassword');
    Route::post('reset-password', 'resetPassword');
});

Route::controller(RouteController::class)->group(function () {
    Route::get('halte', 'getAllHalte');
    Route::post('halte', 'getHalteDetail');
    Route::get('bus', 'getAllBus');
    Route::post('bus', 'getBusDetail');
    // Route::get('coba-chat', 'chatAi');
});




Route::middleware('auth:sanctum')->group(function () {
    Route::controller(MapsController::class)->group(function () {
        Route::post('nearest-halte', 'getNearestHalte');
        Route::post('auto-complete', 'autoCompletion');
        Route::get('place-detail/{placeId}', 'placeDetails');
    });
    Route::controller(AuthController::class)->group(function () {
        Route::get('profile', 'profile');
        Route::put('profile', 'profileUpdate');
        Route::put('password', 'changePassword');
    });
    Route::controller(ChatController::class)->group(function () {
        Route::post('chat', 'chatRequest');
        Route::get('chat', 'chatHistory');
        Route::delete('chat', 'chatClear');
        Route::delete('chat/{chat}', 'destroy');
    });
});
