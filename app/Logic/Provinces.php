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
        $sql = '
            CREATE TABLE provinces (
                provinceId TINYINT(2) UNSIGNED DEFAULT NULL,
                name VARCHAR(128) NOT NULL DEFAULT \'\'
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
