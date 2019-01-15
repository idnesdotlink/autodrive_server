<?php

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Logic\{Address};

Route::middleware([])->group(
    function () {
        Route::get('/companies/{company}', function (Request $request) {
            return $request->company;
        })->name('companies.index');

        Route::get('/companies/{company}/members', function (Request $request) {
            return [$request->company];
        })->name('companies.members.index');

        Route::get('/provinces', function (Request $request) {
            return response()->json(Address::get_all_provinces());
        });

        Route::get('/regencies/{province}', function (Request $request, $province) {
            return response()->json(Address::get_regency_by_provinceId($province));
        });

        Route::get('/districts/{regency}', function (Request $request, $regency) {
            return response()->json(Address::get_district_by_regencyId($regency));
        });

        Route::get('/villages/{district}', function (Request $request, $district) {
            return response()->json(Address::get_village_by_districtId($district));
        });

        Route::get('/members', function (Request $request) {
            return 'members';
        })->name('members.index');

        Route::post('/members', function (Request $request) {

            return response()->json($request->input('nama'));

            // return response(json_encode($request->input()), 200)
            // ->header('Content-Type', 'application/json');
        })->name('members.index.post');

        Route::get('/members/{member}/token', function (Request $request) {
            return $request->member . '-token';
        })->name('members.token');

        Route::get('/members/{member}', function (Request $request) {
            return $request->member;
        })->name('members.detail');

        Route::get('/members/{member}/payment', function (Request $request) {
            return $request->member;
        })->name('members.payment');

        Route::get('/psg', function (Request $request) {
            return 0;
        })->name('db.api');

        Route::get('/users', function (Request $request) {
            return 0;
        })->name('users.index');

        Route::get('/users/{user}/token', function (Request $request) {
            return [$request->user];
        })->name('users.token');

        Route::get('/products', function (Request $request) {
            return 'products';
        })->name('products.index');

        Route::get('/products/{product}', function (Request $request) {
            return [$request->product];
        })->name('products.detail');

        Route::get('/payments/{payment}', function (Request $request) {
            return [$request->payment];
        })->name('payments.detail');
    }
);
