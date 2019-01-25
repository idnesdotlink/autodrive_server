<?php
declare(strict_types=1);

namespace App\Logic;
use Illuminate\Support\Facades\{DB, Storage, Artisan};
use App\Logic\{Members, Levels, Installer, MemberQualification};

class Scenarios {
    public static function load_scenario_console(&$console, &$output) {
        $levels = Levels::$levels;
        $levels = collect($levels);
        $scenario_mapper = function ($scenario) {
            return $scenario['name'];
        };
        $scenarios = $levels->map($scenario_mapper)->toArray();
        $level = $console->choice(
            'scenario level apa yang akan di load',
            $scenarios
        );
        sleep(1);
        $console->line("menggunakan scenario $level");
        $storage = Storage::disk('local');
        $scenario_file = $levels->whereStrict('name', $level) ->first()['id'];
        $scenario_file = 'scenario/' . $scenario_file . '.json';
        $data = $storage->get($scenario_file);
        $data = json_decode($data, true);
        $data = collect($data);
        $dataCount = $data->count();
        sleep(1);
        $console->line($dataCount . ' scenario data');
        $output->progressStart($dataCount);
        $data->each(
            function ($value) use (&$output) {
                $insertData = [
                    'name'     => 'name ' . $value['id'],
                    'parentId' => $value['parentId'],
                    'level'    => $value['level']
                ];
                sleep(1);
                $output->progressAdvance();
            }
        );
        $output->progressFinish();
        $console->info('Memasukan scenario selesai');
    }

    public static function json_scenario_console() {
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
    }
}
