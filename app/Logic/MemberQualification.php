<?php
declare(strict_types=1);

namespace App\Logic;
use Illuminate\Support\Facades\{DB, Storage};

class MemberQualification {

    public static $table_name = 'MemberQualification';
    public static $db_connection = 'autodrive_tip';

    public static function create_table(&$db) {
        $db->statement('
            CREATE TABLE MemberQualification (
                memberId MEDIUMINT UNSIGNED,
                name VARCHAR(128) NOT NULL,
                value MEDIUMINT UNSIGNED NOT NULL DEFAULT 0,
                qualification TINYINT UNSIGNED DEFAULT NULL,
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
        $db->statement('DROP TABLE IF EXISTS MemberQualification');
    }

    public static function get_all_qualification($memberId, &$db) {
        $query = '
            SELECT *
            FROM MemberQualification
            WHERE memberId = ' . $memberId . '
        ';
        $stat = $db->select($query);
        return collection($stat);
    }

    /**
     * Undocumented function
     *
     * @param integer $memberId
     * @param string $key
     * @param object $db
     * @return integer
     */
    public static function get_qualification(string $key, int $memberId = null, Object &$db): int {
        $sql = '
            SELECT *
            FROM MemberQualification
            WHERE memberId = ' . $memberId . ' AND
            name = \'' . $key . '\'
        ';
        $stat = $db->select($sql);
        $stat = collect($stat);
        if ($stat->count() < 1) return 0;
        return $stat->first()->value;
    }

    /**
     * Undocumented function
     *
     * @param int $memberId
     * @param string $key
     * @param object $db
     * @return int
     */
    public static function increment(int $memberId, string $key, int $q = null, Object &$db): int {
        $q = ($q === null) ? 0 : $q;
        $sql = '
            INSERT INTO MemberQualification
            (memberId, name, value, qualification)
            VALUES
            (' . $memberId . ', \'' . $key . '\', 1, NULL)
            ON DUPLICATE KEY UPDATE
            memberId = ' . $memberId . ',
            name = \'' . $key . '\',
            value = value + 1,
            qualification = ' . $q . '
        ';
        $db->insert($sql);
        $sql = '
            SELECT value
            FROM MemberQualification
            WHERE memberId = ' . $memberId . ' AND
            name = \'' . $key . '\'
        ';
        $value = $db->select($sql);
        return collect($value)->first()->value;
    }

    /**
     * Undocumented function
     *
     * @param int $memberId
     * @param string $key
     * @param object $db
     * @return int
     */
    public static function decrement(int $memberId, string $key, int $q = null, Object &$db): int {
        $sql = '
            INSERT INTO MemberQualification
            (memberId, name, value, qualification)
            VALUES
            (' . $memberId . ', \'' . $key . '\', 1, NULL)
            ON DUPLICATE KEY UPDATE
            memberId = ' . $memberId . ',
            name = \'' . $key . '\',
            value = value - 1,
            qualification = ' . $q . '
        ';
        $db->insert($sql);
        $sql = '
            SELECT value
            FROM MemberQualification
            WHERE memberId = ' . $memberId . ' AND
            name = \'' . $key . '\'
        ';
        $value = $db->select($sql);
        return collect($value)->first()->value;
    }
}
