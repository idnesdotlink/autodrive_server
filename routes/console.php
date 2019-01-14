<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\{DB, Storage, Artisan};
use App\Logic\{Members, Levels, Installer, MemberQualification, Scenario, Tables};

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
