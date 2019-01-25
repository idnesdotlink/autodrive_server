<?php

use Ramsey\Uuid\Uuid;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Logic\{Addresses};
use Faker\Factory;

Route::middleware([])->group(
    function () {
        Route::get('/companies/{company}', function (Request $request) {
            return $request->company;
        })->name('companies.index');

        Route::get('/companies/{company}/members', function (Request $request) {
            return [$request->company];
        })->name('companies.members.index');

        Route::get('/provinces', function (Request $request) {
            return response()->json(Addresses::get_all_provinces());
        });

        Route::get('/regencies/{province}', function (Request $request, $province) {
            return response()->json(Addresses::get_regency_by_provinceId($province));
        });

        Route::get('/districts/{regency}', function (Request $request, $regency) {
            return response()->json(Addresses::get_district_by_regencyId($regency));
        });

        Route::get('/villages/{district}', function (Request $request, $district) {
            return response()->json(Addresses::get_village_by_districtId($district));
        });

        Route::get('/members.json', function (Request $request) {
            $faker = Factory::create();
            $mem = [];
            for($i = 1; $i < 100; $i++) {
                $mem[] = [
                    $faker->name(),
                    $faker->uuid(),
                    $faker->randomElement([1,2,3,4,5,6,7,8])
                ];
            }
            $size = sizeof($mem);
            return response()->json([$size, $mem]);
            // echo json_encode($mem);
        })->name('members.index');

        Route::post('/members', function (Request $request) {
            $faker = Factory::create();
            $mem = [];
            for($i = 1; $i < 100; $i++) {
                $mem[] = [
                    $faker->name(),
                    Uuid::uuid4(),
                    $faker->randomElement([1,2,3,4,5,6,7,8])
                ];
            }
            $size = sizeof($mem);
            return response()->json(
                [
                    'list' => $mem,
                    'meta' => [
                        'size' => $size,
                        'column' => ['name', 'id', 'level']
                    ]
                ]
            );

            // return response()->json($request->input());

            // return response(json_encode($request->input()), 200)
            // ->header('Content-Type', 'application/json');
            // $faker = Factory::create();
            // echo $faker->name();
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

        Route::get('/psg', function (Request $request) {
            return 0;
        })->name('db.api');

        Route::post('/users/authenticate', function (Request $request) {
            $credentials = request(['email', 'password']);
            $user = App\User::find(1);
            if (! $token = auth()->claims([
                'aud' => 'admin'
            ])->setTTL(30)->attempt($credentials)) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }
            return response()->json([
                'access_token' => $token,
                'token_type' => 'bearer'
            ], 200);
        })->name('users.authenticate');

        Route::post('/provinces', function (Request $request) {
            return response()->json(Addresses::get_all_provinces());
        });

        Route::post('/regencies', function (Request $request) {
            return response()->json(Addresses::get_regency_by_provinceId((string)$request->province));
        });

        Route::post('/districts', function (Request $request) {
            // return response()->json($request->regency);
            return response()->json(Addresses::get_district_by_regencyId((string)$request->regency));
        });

        Route::post('/villages', function (Request $request) {
            return response()->json(Addresses::get_village_by_districtId((string)$request->district));
        });

        Route::patch('/account', function (Request $request) {
            return response()->json($request->all());
        });

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
