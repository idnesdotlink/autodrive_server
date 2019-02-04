<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\{DB, Storage, Artisan};
use App\Logic\{Scenarios, Tables, Addresses};
use App\User;

Artisan::command('scenario:json', function () {
    Scenarios::json_scenario_console();
});

Artisan::command('scenario:load', function () {
    Tables::down();
    Tables::up();
    $console = $this;
    $output  = $this->output;
    Scenarios::load_scenario_console($console, $output);
});

Artisan::command('tables:recreate', function () {
    $command = $this;
    Tables::recreate_command($command);
});

Artisan::command('tables:drop', function () {
    $command = $this;
    Tables::drop_command($command);
});

Artisan::command('tables:seed', function () {
    $command = $this;
    Tables::seed_command($command);
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
    $address = Addresses::get_regency_by_provinceId('12');
    print_r($address);
})->describe('Display table list');

Artisan::command('user:create', function () {
    $name     = $this->ask('What is your name?');
    $email    = $this->ask('What is your email?');
    $password = $this->secret('What is your password?');

    User::create([
        'name'     => $name,
        'email'    => $email,
        'password' => Hash::make($password),
    ]);
});

Artisan::command('user:list', function () {
    $users = User::findAll();
    print_r($users);
});

