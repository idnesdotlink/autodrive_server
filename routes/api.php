<?php

use Illuminate\Http\Request;

Route::middleware('auth:api')->get('/user', function (Request $request) {
    // return $request->user();
});

Route::middleware('auth:api')->get('/test', function (Request $request) {
    // return $request->user();
    return 0;
});
