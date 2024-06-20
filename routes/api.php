<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\NoteController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::controller(AuthController::class)->prefix('auth')->group(function () {
    Route::post('register', 'register')->middleware('throttle:register')->name('auth.register');
    Route::post('login', 'login')->middleware('throttle:login')->name('auth.login');
    Route::get('login/google', 'redirectToGoogle')->name('auth.redirectToGoogle');
    Route::get('login/google/callback', 'handleGoogleCallback')->name('auth.handleGoogleCallback');
    Route::get('login/facebook', 'redirectToFacebook')->name('auth.redirectToFacebook');
    Route::get('login/facebook/callback', 'handleFacebookCallback')->name('auth.handleFacebookCallback');
});

Route::controller(NoteController::class)->prefix('note')->group(function () {
    Route::get('', 'index')->name('notes.index');
    Route::post('', 'create')->name('notes.create');
    Route::get('{note}', 'show')->name('notes.show');
    Route::put('{note}', 'update')->name('notes.update');
    Route::delete('{note}', 'destroy')->name('notes.destroy');
});
