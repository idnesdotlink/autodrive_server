<?php
declare(strict_types=1);

namespace App\Logic;
use Illuminate\Support\Facades\{DB, Storage};
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Database\ConnectionInterface;

class Tables {
    public static $connection_name = 'autodrive_tip';
    public static $registers = [
        'Members',
        'Villages',
        ''
    ];

    public static $migrations_register = [];

    /**
     * Undocumented function
     *
     * @return ConnectionInterface
     */
    public static function db(): ConnectionInterface {
        return DB::connection(self::$connection_name);
    }

    /**
     * Undocumented function
     *
     * @return void
     */
    public static function up(): void {
        $db = self::db();
        $register = collect(self::$registers);
        $register->each(
            function ($value) use($db) {
                $value = 'App\\Logic\\' . $value;
                $value::create_table($db);
            }
        );
    }

    /**
     * Undocumented function
     *
     * @return void
     */
    public static function down(): void {
        $db = self::db();
        $register = collect(self::$registers);
        $register->each(
            function ($value) use($db) {
                $value = 'App\\Logic\\' . $value;
                $value::drop_table($db);
            }
        );
    }

    /**
     * Undocumented function
     *
     * @return Collection
     */
    public static function get_registered(): Collection {
        $register = collect(self::$registers);
        return $register;
    }

    /**
     * Undocumented function
     *
     * @param Command $command
     * @return void
     */
    public static function recreate_registered_command(Command &$command): void {
        sleep(1);
        $command->info('Warning: This action will drop and recreate all tables !!');
        sleep(1);
        $agree = $command->choice(
            'Are You Sure ?',
            ['yes', 'no']
        );
        $agree = $agree === 'yes';
        if ($agree) {
            try {
                self::down();
                self::up();
                $command->info('Table Recreate Success');
            } catch(Exception $error) {
                $command->info('Table Recrete Failed');
            }
        } else {
            $command->line('Aborted !!');
        }
    }

    /**
     * Undocumented function
     *
     * @param Command $command
     * @return void
     */
    public static function recreate_command(Command &$command): void {
        sleep(1);
        $command->info('Warning: This action will drop and recreate all tables !!');
        sleep(1);
        $agree = $command->choice(
            'Are You Sure ?',
            ['yes', 'no']
        );
        $agree = $agree === 'yes';
        if ($agree) {
            try {
                self::down();
                self::up();
                $command->info('Table Recreate Success');
            } catch(Exception $error) {
                $command->info('Table Recrete Failed');
            }
        } else {
            $command->line('Aborted !!');
        }
    }

    /**
     * Undocumented function
     *
     * @param Command $command
     * @return void
     */
    public static function drop_command(Command &$command): void {
        $tables = self::all();
        $tables = $tables->prepend('all');
        $tables = $tables->all();
        $table = $command->choice(
            'Select Table to Delete ?',
            $tables
        );
        if ($table === 'all') {
            $command->info('Delete All Table');
        } else {
            try {
                self::drop($table);
                $command->info('Delete Table ' . $table);
            } catch (Exception $error) {}
        }
    }

    /**
     * Undocumented function
     *
     * @param Command $command
     * @return void
     */
    public static function list_command(Command &$command): void {
        $tables = self::all();
        $transform_table = function ($val) {
            return [$val];
        };
        $tables->transform($transform_table);
        $command->table(['name'], $tables);
    }

    /**
     * Undocumented function
     *
     * @param string $name
     * @return void
     */
    public static function drop(string $name): void {
        $db = self::db();
        $db->statement('DROP TABLE IF EXISTS ' . $name);
    }

    /**
     * Undocumented function
     *
     * @return Collection
     */
    public static function all(): Collection {
        $db = self::db();
        $db = $db->select('show tables');
        $db = collect($db);
        $db = $db->values();
        $show_table_reduce = function ($accumulator, $value) use(&$db) {
            $data = array_values(collect($value)->toArray())[0];
            $accumulator->push($data);
            return $accumulator;
        };
        $data = $db->reduce($show_table_reduce, collect([]));
        return $data;
    }
}
