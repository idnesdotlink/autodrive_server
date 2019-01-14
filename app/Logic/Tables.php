<?php
declare(strict_types=1);

namespace App\Logic;
use Illuminate\Support\Facades\{DB, Storage};
use Illuminate\Support\Collection;

class Tables {
    public static $connection_name = 'autodrive_tip';
    public static $registers = [
        'Members'
    ];

    public static $migrations_register = [];

    public static function db() {
        return DB::connection(self::$connection_name);
    }

    public static function up() {
        $db = self::db();
        $register = collect(self::$registers);
        $register->each(
            function ($value) use($db) {
                $value = 'App\\Logic\\' . $value;
                $value::create_table($db);
            }
        );
    }

    public static function down() {
        $db = self::db();
        $register = collect(self::$registers);
        $register->each(
            function ($value) use($db) {
                $value = 'App\\Logic\\' . $value;
                $value::drop_table($db);
            }
        );
    }
}