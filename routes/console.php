<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\{DB, Storage, Artisan};
use App\Logic\{Members, Levels, Installer, MemberQualification, Scenario, Tables, Address};
use App\User;

Artisan::command('scenario:json', function () {
    Scenario::json_scenario_console();
});

Artisan::command('scenario:load', function () {
    Tables::down();
    Tables::up();
    $console = $this;
    $output  = $this->output;
    Scenario::load_scenario_console($console, $output);
});

Artisan::command('tables:recreate', function () {
    $command = $this;
    Tables::recreate_command($command);
});

Artisan::command('tables:drop', function () {
    $command = $this;
    Tables::drop_command($command);
});

Artisan::command('tables:list', function () {
    $command = $this;
    Tables::list_command($command);
});

Artisan::command('security:ask', function () {
    $name = $this->ask('What is your name?');

    $this->info("Hello, $name");
})->describe('Display table list');

Artisan::command('security:password', function () {
    $password = $this->secret('What is your password?');
    $this->info("This is really secure. Your password is $password");
})->describe('Display table list');

Artisan::command('abc', function () {
    $address = Address::get_regency_by_provinceId('12');
    print_r($address);
})->describe('Display table list');

Artisan::command('user:create', function () {
    $name = $this->ask('What is your name?');
    $email = $this->ask('What is your email?');
    $password = $this->secret('What is your password?');

    User::create([
        'name' => $name,
        'email' => $email,
        'password' => Hash::make($password),
    ]);
});

Artisan::command('user:jwt', function () {
    // $name = $this->ask('What is your name?');
    $email = $this->ask('What is your email?');
    $password = $this->secret('What is your password?');

    $credentials = ['email' => $email, 'password' => $password];
    print_r(auth()->attempt($credentials));
    // if (! $token = auth()->attempt($credentials)) {
        // return response()->json(['error' => 'Unauthorized'], 401);
    // }

    // return $this->respondWithToken($token);
});

Artisan::command('user:get', function () {
    $db = DB::connection('autodrive_tip');
    $data = $db->select('
        SELECT id, name, password
        FROM users;
    ');
    // print_r($data);
    // $id = $data[0]->id;
    // $token = auth()->tokenById(123);
    // print_r(auth()->validate(['email' => 'idnes.link@gmail.com', 'password' => '12345678']));

    $token = auth()->login(User::first());
    auth()->logout();
    // $token = auth()->claims(['foo' => 'bar'])->attempt($data);
    print_r(auth()->user());
});
