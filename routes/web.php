<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;

Route::domain('api.trial205.tiptech.network')->group(function () {
    Route::get('/', function () {
        return view('api.welcome');
    });
});

Route::get('/login', function () {
    return view('main.welcome');
})->name('login');

Route::get('/', function () {
    return view('main.welcome');
});

Route::get('/db', function () {
    return view('main.db');
});

Route::get('/create', function () {
    $db = DB::connection('autodrive_tip');
    $tables = $db->statement('DROP TABLE IF EXISTS members');
    $tables = $db->statement('
        CREATE TABLE members (
            id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            parentId INT(6) NOT NULL,
            name VARCHAR(30) NOT NULL
        )
    ');
    // echo 'created';
    return redirect('/db')->with('status', 'created!');
});

Route::get('/delete', function () {
    $db = DB::connection('autodrive_tip');
    $tables = $db->statement('DROP TABLE IF EXISTS members');
    return redirect('/db')->with('status', 'deleted!');
});

Route::get('/count', function () {
    $db = DB::connection('autodrive_tip');
    $tables = $db->select('show tables');
    $tables = sizeof($tables);
    return redirect('/db')->with('status', 'count: ' . $tables);
});

Route::get('/list', function () {
    $dbName = 'autodrive_1';
    $key = 'Tables_in_' . $dbName;
    $db = DB::connection('autodrive_tip');
    $tables = $db->select('show tables');
    $size = sizeof($tables);
    $tableName = [];
    if($size > 0) {
        foreach($tables as $table) {
            $tableName[] = $table->$key;
        }
    }
    return redirect('/db')->with('tables', $tableName);
});

Route::get('/sessions', function () {
    Artisan::call('session:table');
    Artisan::call('migrate');
});

Route::get('/migrate', function () {
    Artisan::call('session:table');
    Artisan::call('migrate');
});

Route::get('/sessions', function () {
    Artisan::call('session:table');
    Artisan::call('migrate');
});
