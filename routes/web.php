<?php

use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Input;

Route::get('/', function () {

})->name('root');


Route::prefix('member')->middleware([])->group(function () {
    Route::get('/', function () {
        echo 'test';
    });
    Route::get('/', function () {
        echo 'test';
    });
    Route::get('/', function () {
        echo 'test';
    });
});
