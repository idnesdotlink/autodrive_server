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
                provinceId TINYINT(2) UNSIGNED,
                regencyId TINYINT(2) UNSIGNED ZEROFILL,
                districtId TINYINT(3) UNSIGNED ZEROFILL,
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
    public static function drop_table(ConnectionInterface &$db = null): void {}

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
