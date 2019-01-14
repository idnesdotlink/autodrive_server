<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\{DB, Storage, Artisan};
use App\Logic\{Members, Levels, Installer, MemberQualification};

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
    $dummies = Installer::get_dummy_members();
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

Artisan::command('gc', function () {
    // $db = DB::connection('autodrive_tip');
    $db = null;
    $this->line('gc :' . Members::get_descendants_count(1, $db));
});

Artisan::command('mupd', function () {
    $db = DB::connection('autodrive_tip');
    $db->statement('
        DROP TABLE IF EXISTS mupd
    ');
    $db->statement('
        CREATE TABLE mupd (
            id TINYINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            int TEXT
        )
    ');
    $table = $db->table('mupd');
    $table->insert([
        ['text' => 1],
        ['text' => 2],
        ['text' => 3]
    ]);
});

Artisan::command('mupd2', function () {
    $db = DB::connection('autodrive_tip');
    $db->update('
        UPDATE mupd s
        JOIN (
            SELECT 1 as id, 3 as new_text
            UNION ALL
            SELECT 2, 2
            UNION ALL
            SELECT 3, 1
        ) vals ON s.id = vals.id
        SET text = new_text
    ');
});

Artisan::command('a', function () {
    $db = DB::connection('autodrive_tip');
    MemberQualification::drop_table($db);
    MemberQualification::create_table($db);
});

Artisan::command('b', function () {
    $db = DB::connection('autodrive_tip');
    MemberQualification::incrementStat(0, 'q1', $db);
});

Artisan::command('scenario:json', function () {
    $pathToScenarios = 'scenario';
    $storage = Storage::disk('local');
    if (!$storage->exists($pathToScenarios)) {
        $storage->makeDirectory($pathToScenarios);
    } else {
        $storage->deleteDirectory($pathToScenarios);
        $storage->makeDirectory($pathToScenarios);
    }
    $levels = Levels::$levels;
    $levels = collect($levels);
    $levels->each(
        function ($level) use($pathToScenarios, $storage) {
            $id = $level['id'];
            $scenario = Levels::create_scenario($id);
            $pathToScenarios = $pathToScenarios . '/' . $id . '.json';
            $storage->put($pathToScenarios, $scenario->toJson());
        }
    );
});

Artisan::command('scenario:load', function () {
    $levels = Levels::$levels;
    $levels = collect($levels);
    $scenario_mapper = function ($scenario) {
        return $scenario['name'];
    };
    $scenarios = $levels->map($scenario_mapper)->toArray();   
    $level = $this->choice(
        'scenario level apa yang akan di load',
        $scenarios
    );
    sleep(1);
    $this->line("menggunakan scenario $level");
    $storage = Storage::disk('local');
    $scenario_file = $levels->whereStrict('name', $level) ->first()['id'];
    $scenario_file = 'scenario/' . $scenario_file . '.json';
    $data = $storage->get($scenario_file);
    $data = json_decode($data, true);
    $data = collect($data);
    $dataCount = $data->count();
    sleep(1);
    $this->line($dataCount . ' scenario data');
    $this->output->progressStart($dataCount);
    $command = $this;
    $data->each(
        function ($value) use (&$command) {
            $insertData = [
                'name'     => 'name ' . $value['id'],
                'parentId' => $value['parentId'],
                'level'    => $value['level']
            ];
            time_nanosleep(0, 100000);
            $command->output->progressAdvance();
        }
    );
    $this->output->progressFinish();
    $this->info('Memasukan scenario selesai');
});
