<?php
declare(strict_types=1);

namespace App\Logic;
use Illuminate\Support\Facades\{DB, Storage};
use Illuminate\Support\Collection;
use Illuminate\Database\ConnectionInterface;
use App\Logic\{Levels, MemberQualification, HasTableInterface};

class Districts implements HasTableInterface {

    /**
     * Undocumented function
     *
     * @param ConnectionInterface $db
     * @return void
     */
    public static function create_table(ConnectionInterface &$db = null): void {
        $sql = '
            CREATE TABLE districts (
                regencyId CHAR(4) NOT NULL DEFAULT \'\',
                id CHAR(7) NOT NULL DEFAULT \'\',
                name VARCHAR(128) NOT NULL DEFAULT \'\',
                UNIQUE KEY district (id),
                INDEX regency (regencyId, id)
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
        $db = ($db === null) ? DB::connection(self::$db_connection) : $db;
        $sql = 'DROP TABLE IF EXISTS districts';
        $db->statement($sql);
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

}
