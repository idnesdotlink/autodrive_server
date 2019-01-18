<?php

use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Input;

Route::get('/', function () {

})->name('root');


Route::prefix('members')->group(function () {
    Route::get('/', function () {
        echo 'test';
    });

    Route::post('/', function () {
        return response('{"test": "pusing"}')
        ->header('Content-Type', 'application/json')
        ->header('X-Header-One', 'Header Value')
        ->header('X-Header-Two', 'Header Value');
    });
    Route::get('/', function () {
        echo 'test';
    });
    Route::get('/', function () {
        echo 'test';
    });
});

Auth::routes();

Route::get('/home', 'HomeController@index')->name('home');
