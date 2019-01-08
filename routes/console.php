<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Repositories\Members;
use App\Repositories\Levels;
use App\Repositories\Installer;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->describe('Display an inspiring quote');

Artisan::command('installer:table', function () {
    $this->output->progressStart(10);

    for ($i = 0; $i < 10; $i++) {
        sleep(1);

        $this->output->progressAdvance();
    }

    $this->output->progressFinish();
})->describe('Display table list');

Artisan::command('installer:create-table', function () {
    $headers = ['Name', 'Awesomeness Level'];

    $data = [
        [
            'name' => 'Jim',
            'awesomeness_level' => 'Meh',
        ],
        [
            'name' => 'Conchita',
            'awesomeness_level' => 'Fabulous',
        ],
    ];

    $this->table($headers, $data);
})->describe('Display table list');

Artisan::command('installer:ask', function () {
    $name = $this->ask('What is your name?');

    $this->info("Hello, $name");
})->describe('Display table list');

Artisan::command('installer:password', function () {
    $password = $this->secret('What is your password?');

    $this->info("This is really secure. Your password is $password");
})->describe('Display table list');

Artisan::command('installer:anticipate', function () {
    $name = $this->anticipate(
        'What is your name?',
        ['Jim', 'Conchita']
    );

    $this->info("Your name is $name");

    $source = $this->choice(
        'Which source would you like to use?',
        ['master', 'develop']
    );

    $this->info("Source chosen is $source");
})->describe('Display table list');

Artisan::command('installer:table-name', function () {
    $tables = Installer::show_table();
    $this->table(['name'], $tables);
})->describe('Display table list');

Artisan::command('installer:table', function () {
    Installer::tables();
})->describe('Display table list');

Artisan::command('installer:seed', function () {
    $data = Members::get_dummy_members();
    Members::batch_insert($data);
})->describe('Display table list');

Artisan::command('installer:drop', function () {
    Installer::drop_all_table();
})->describe('Display table list');

Artisan::command('installer:seed-scenario', function () {
    $this->comment('Processing Installer');
    $dummies = Members::get_dummy_members();
    $part = 1000;
    $part = $dummies->take($part);
    $total = $part->count();

    $this->output->progressStart($total);

    for ($i = 0; $i < $total; $i++) {
        usleep(10000);

        $this->output->progressAdvance();
    }

    $this->output->progressFinish();
    $this->comment('Processing Installer Finish');

})->describe('Display table list');
