<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\NoteController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\StarController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::controller(AuthController::class)->prefix('auth')->group(function () {
    Route::post('register', 'register')->middleware('throttle:register')->name('auth.register');
    Route::post('login', 'login')->middleware('throttle:login')->name('auth.login');
    Route::get('login/google', 'redirectToGoogle')->name('auth.redirectToGoogle');
    Route::get('login/google/callback', 'handleGoogleCallback')->name('auth.handleGoogleCallback');
    Route::get('login/facebook', 'redirectToFacebook')->name('auth.redirectToFacebook');
    Route::get('login/facebook/callback', 'handleFacebookCallback')->name('auth.handleFacebookCallback');
    Route::post('refresh', 'refresh')->name('auth.refresh');
    Route::post('logout', 'logout')->middleware('auth:api')->name('auth.logout');
});

Route::controller(NoteController::class)->prefix('note')->middleware('auth:api')->group(function () {
    Route::get('{category?}', 'notes')->name('note.notes');
    Route::post('', 'create')->middleware("throttle:note")->name('note.create');
    Route::get('show/{id}', 'show')->name('note.show');
    Route::put('{note_id}', 'update')->name('note.update');
    Route::delete('softdelete/{note_id}', 'softdelete')->name('note.softdelete');
    Route::delete('forcedelete/{note_id}', 'forcedestroy')->name('note.forcedestroy');
    Route::delete('forcedelete/all/permanent', 'forcedestroyall')->name('note.forcedestroyall');
    Route::get('restore/all', 'restoreall')->name('note.restoreall');
    Route::post('restore/{note_id}', 'restore')->name('note.restore');
    Route::get('soft/delete', 'showdelete')->name('note.showdelete');
    Route::post('upload/image', 'uploadImage')->name('note.uploadImage');
    Route::get('list/category', 'category')->name('note.category');
    Route::get('share/{node_id}', 'share')->name('note.share');
    Route::get('share/show/{share_id}', 'showshare')->withoutMiddleware("auth:api")->middleware('share')->name('note.showshare');
    Route::post('share/duplicate/{share_id}', 'duplicate')->name('note.duplicate');
});

Route::controller(StarController::class)->prefix('star')
    ->middleware('auth:api')->group(function () {
        Route::get('', 'stars')->name('star.index');
        Route::patch('{id}', 'update')->name('star.update');
    });

Route::get('search/note/{title}', SearchController::class)
    ->middleware('auth:api')->name('search.index');
