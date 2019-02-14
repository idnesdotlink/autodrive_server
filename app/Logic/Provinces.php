<?php
declare(strict_types=1);

namespace App\Logic;
use Illuminate\Support\Facades\{DB, Storage};
use Illuminate\Support\Collection;
use App\Logic\{Levels, MemberQualification, HasTableInterface};
use Illuminate\Database\ConnectionInterface;

class Provinces implements HasTableInterface {

    /**
     * Undocumented function
     *
     * @param ConnectionInterface $db
     * @return void
     */
    public static function create_table(ConnectionInterface &$db = null): void {
        $db = ($db === null) ? DB::connection(self::$db_connection) : $db;
        $sql = '
            CREATE TABLE provinces (
                id CHAR(2),
                name VARCHAR(128) NOT NULL DEFAULT \'\',
                UNIQUE KEY province (id)
            )
        ';
        $db->statement($sql);
    }

    /**
     * Undocumented function
     *
     * @param ConnectionInterface $db
     * @return void
     */
    public static function drop_table(ConnectionInterface &$db = null): void {
        $sql = 'DROP TABLE IF EXISTS provinces';
        $db->statement($sql);
    }

    public static function seed() {

    }

    public static function get_all() {
        return
            [
                [
                    'value' => 1,
                    'name' => 'test name'
                ]
            ]
        ;
    }

    public static function get_one() {

    }

}
