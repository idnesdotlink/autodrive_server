<?php

use Ramsey\Uuid\Uuid;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Faker\Factory;
use App\Logic\{Addresses, Levels, Mock, Authentication};

Route::middleware([])->group(
    function () {

        Route::post('/pusher', function (Request $request) {
            return response()->json($request->all());
        });

        Route::post('/members', function (Request $request) {
            return Mock::members();
        })->name('members.index.post');

        Route::get('/members/{member}/token', function (Request $request) {
            return $request->member . '-token';
        })->name('members.token');

        Route::post('/members/{member}', function (Request $request) {
            return response()->json($request->member);
        })->name('members.detail');

        Route::get('/members/{member}/payment', function (Request $request) {
            return $request->member;
        })->name('members.payment');

        Route::post('/users/authenticate', function (Request $request) {
            return Authentication::process($request);
        })->name('users.authenticate');

        Route::post('/provinces', function (Request $request) {
            return response()->json(Addresses::get_all_provinces());
        });

        Route::post('/regencies', function (Request $request) {
            $regencies = Addresses::get_regency_by_provinceId((string)$request->province);
            return response()->json($regencies);
        });

        Route::post('/districts', function (Request $request) {
            $districts = Addresses::get_district_by_regencyId((string)$request->regency);
            return response()->json($districts);
        });

        Route::post('/villages', function (Request $request) {
            $villages = Addresses::get_village_by_districtId((string)$request->district);
            return response()->json($villages);
        });

        Route::patch('/account', function (Request $request) {
            return response()->json($request->all());
        });

        Route::post('/level', function (Request $request) {
            $levels = Levels::getLevels();
            return response()->json($levels);
        });

        Route::post('/level/detail', function (Request $request) {
            $levels = Levels::getLevels();
            $l = collect($levels)->where('id', $request->id)->first();
            return response()->json($l);
        });

        Route::post('/verify', function (Request $request) {
            print_r($request->key);
        });
    }
);
