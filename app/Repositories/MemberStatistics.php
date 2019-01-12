<?php

namespace App\Repositories;
use Illuminate\Support\Facades\{DB, Storage};

class MemberStatistics {

    public static $table_name = 'memberStatistics';
    public static $db_connection = 'autodrive_tip';

    public static function create_table(&$db) {
        $db->statement('
            CREATE TABLE memberStatistics (
                memberId MEDIUMINT UNSIGNED,
                name VARCHAR(128) NOT NULL,
                value MEDIUMINT UNSIGNED NOT NULL DEFAULT 0,
                PRIMARY KEY (memberId, name)
            )
        ');
    }

    /**
     *
     *
     * @param [type] $db
     * @return void
     */
    public static function drop_table(&$db): void {
        $db->statement('DROP TABLE IF EXISTS memberStatistics');
    }

    public static function increment_descendant_level(&$db) {
    }

    public static function getAllStat($memberId, &$db) {
        $query = '
            SELECT *
            FROM memberStatistics
            WHERE memberId = ' . $memberId . '
        ';
        $stat = $db->select($query);
        return collection($stat);
    }

    /**
     * Undocumented function
     *
     * @param integer $memberId
     * @param String $key
     * @param Object $db
     * @return integer
     */
    public static function getStat(int $memberId, String $key, Object &$db): int {
        $sql = '
            SELECT value
            FROM memberStatistics
            WHERE memberId = ' . $memberId . ' AND
            name = \'' . $key . '\'
        ';
        $value = $db->select($sql);
        return collect($value)->first()->value;
    }

    /**
     * Undocumented function
     *
     * @param Int $memberId
     * @param String $key
     * @param Object $db
     * @return int
     */
    public static function incrementStat(Int $memberId, String $key, Object &$db): int {
        $sql = '
            INSERT INTO memberStatistics
            (memberId, name, value)
            VALUES
            (' . $memberId . ', \'' . $key . '\', 1)
            ON DUPLICATE KEY UPDATE
            memberId = ' . $memberId . ',
            name = \'' . $key . '\',
            value = value + 1
        ';
        $db->insert($sql);
        $sql = '
            SELECT value
            FROM memberStatistics
            WHERE memberId = ' . $memberId . ' AND
            name = \'' . $key . '\'
        ';
        $value = $db->select($sql);
        return collect($value)->first()->value;
    }

    /**
     * Undocumented function
     *
     * @param Int $memberId
     * @param String $key
     * @param Object $db
     * @return int
     */
    public static function decrementStat(Int $memberId, String $key, Object &$db): int {
        $sql = '
            INSERT INTO memberStatistics
            (memberId, name, value)
            VALUES
            (' . $memberId . ', \'' . $key . '\', 1)
            ON DUPLICATE KEY UPDATE
            memberId = ' . $memberId . ',
            name = \'' . $key . '\',
            value = value - 1
        ';
        $db->insert($sql);
        $sql = '
            SELECT value
            FROM memberStatistics
            WHERE memberId = ' . $memberId . ' AND
            name = \'' . $key . '\'
        ';
        $value = $db->select($sql);
        return collect($value)->first()->value;
    }
}
