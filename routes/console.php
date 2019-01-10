<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Artisan;
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

Artisan::command('installer:drop', function () {
    Installer::drop_all_table();
})->describe('Display table list');

Artisan::command('installer:seed-scenario', function () {
    $this->comment('Processing Installer');
    $dummies = Members::get_dummy_members();
    // $dummies = $dummies->take(12);
    // print_r($dummies->first());
    // exit();
    $groupSize = 500;
    $chunked = $dummies->chunk($groupSize);
    $chunkCount = 1;
    $this->output->progressStart($chunked->count());
    foreach($chunked as $chunk) {
        $chunk->each(function ($data) {
            $insertData = [
                'name'     => $data['name'],
                'parentId' => $data['parentId'],
                'level'    => $data['level']
            ];
            Members::add($data['parentId'], $insertData);
        });
        $this->output->progressAdvance();
        $chunkCount++;
    }
    $this->output->progressFinish();
    $this->comment('Processing Installer Finish');

})->describe('Display table list');

Artisan::command('data:members {id?} {--action=?}', function ($id = null, $action = null) {
    switch ($action) {
        case 'd':
            $member = Members::get_one($id);
            if ($member === null) {
                $this->comment('no data !');
            } else {
                $member = collect($member)->only(['id', 'parentId', 'level', 'qualification']);
                $header = $member->keys();
                $data = $member->values()->toArray();
                $this->table($header, [$data]);
            }
            break;
        case 'a':
            $this->line('ancestors');
            break;
        case 'd':
            $this->line('descendants');
            break;
        case 's':
            $this->line('siblings');
            break;
        case 'add':
            // $this->line('create dummy');
            try {
                $db = DB::connection(Members::$db_connection);
                $parentId = $id;
                $dummy_data = [
                    'parentId' => $parentId,
                    'name'     => 'member name '
                ];
                $id = Members::add($parentId, $dummy_data);
                $ancestors = Members::query_get_ancestors($id, $db);
                $this->line('new member with id: ' . $id);
                $this->line('ancestors: ' . $ancestors->count());
            } catch (Exception $error) {
                $this->line($error->getMessage());
                // print_r($error);
            }
        default:
            // $this->line('please insert --type=[type]');
            break;
    }
})->describe('Display Members Database By Id');

Artisan::command('data:table {--action=?}', function ($action) {
    switch ($action) {
        case 'create':
            try {
                Artisan::call('migrate');
                Installer::tables();
            } catch (Exception $error) {

            }
            break;
        case 'drop':
            try {
                Installer::drop_all_table();
            } catch (Exception $error) {

            }
            break;
        case 'seed':
            try {
                $data = Members::get_dummy_members();
                Members::batch_insert($data);
            } catch (Exception $error) {

            }
            break;
        default:
        break;
    }
    $this->line('Action Success');
})->describe('Install Tables');

Artisan::command('mdt', function () {
    $this->line('child count: ' . Members::get_descendants_count(1));
    $this->line('child: ' . Members::get_descendants(1)->implode(', '));
});

Artisan::command('maxlevel', function () {
    $this->line('max level :' . Levels::get_max_level());
});
