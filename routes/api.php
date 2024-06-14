<?php

use App\Http\Controllers\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::controller(AuthController::class)->prefix('auth')->group(function () {
    Route::post('register', 'register')->middleware('throttle:6,60')->name('auth.register');
    Route::post('login', 'login')->middleware('throttle:10,30')->name('auth.login');
    Route::get('login/google', 'redirectToGoogle')->name('auth.redirectToGoogle');
    Route::get('login/google/callback', 'handleGoogleCallback')->name('auth.handleGoogleCallback');
    Route::get('login/facebook', 'redirectToFacebook')->name('auth.redirectToFacebook');
    Route::get('login/facebook/callback', 'handleFacebookCallback')->name('auth.handleFacebookCallback');
});
