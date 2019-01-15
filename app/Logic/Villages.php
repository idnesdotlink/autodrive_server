<?php
declare(strict_types=1);

namespace App\Logic;
use Illuminate\Support\Facades\{DB, Storage};
use Illuminate\Support\Collection;
use App\Logic\{Levels, MemberQualification, HasTableInterface};
use Illuminate\Database\ConnectionInterface;

class Villages implements HasTableInterface {

    /**
     * Undocumented function
     *
     * @param ConnectionInterface $db
     * @return void
     */
    public static function create_table(ConnectionInterface &$db = null): void {
        $db = ($db === null) ? DB::connection(self::$db_connection) : $db;
        $sql = '
            CREATE TABLE villages (
                provinceId TINYINT(2) UNSIGNED,
                regencyId TINYINT(2) UNSIGNED ZEROFILL,
                districtId TINYINT(3) UNSIGNED ZEROFILL,
                villageId TINYINT(3) UNSIGNED ZEROFILL,
                name VARCHAR(128) NOT NULL DEFAULT \'\',
                UNIQUE KEY village (provinceId, regencyId, districtId, villageId),
                INDEX district (provinceId, regencyId, districtId),
                INDEX regency (provinceId, regencyId),
                INDEX province (provinceId)
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
        $db->statement('DROP TABLE IF EXISTS villages');
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
