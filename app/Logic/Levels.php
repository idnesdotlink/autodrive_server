<?php
declare(strict_types=1);

namespace App\Logic;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Illuminate\Database\ConnectionInterface;
use App\Logic\{HasTableInterface};

class Levels implements HasTableInterface {

    const ID_9 = 9;
    const ID_8 = 8;
    const ID_7 = 7;
    const ID_6 = 6;
    const ID_5 = 5;
    const ID_4 = 4;
    const ID_3 = 3;
    const ID_2 = 2;
    const ID_1 = 1;

    const NAME_9 = 'triple ambasador';
    const NAME_8 = 'double ambasador';
    const NAME_7 = 'ambasador';
    const NAME_6 = 'triple diamond';
    const NAME_5 = 'double diamond';
    const NAME_4 = 'diamond';
    const NAME_3 = 'gold';
    const NAME_2 = 'silver';
    const NAME_1 = 'bronze';

    const REQ_9 = 4;
    const REQ_8 = 5;
    const REQ_7 = 3;
    const REQ_6 = 4;
    const REQ_5 = 3;
    const REQ_4 = 5;
    const REQ_3 = 4;
    const REQ_2 = 3;
    const REQ_1 = null;

    public static $levels = [
        self::ID_1 => ['id' => self::ID_1, 'name' => self::NAME_1, 'requirement' => self::REQ_1],
        self::ID_2 => ['id' => self::ID_2, 'name' => self::NAME_2, 'requirement' => self::REQ_2],
        self::ID_3 => ['id' => self::ID_3, 'name' => self::NAME_3, 'requirement' => self::REQ_3],
        self::ID_4 => ['id' => self::ID_4, 'name' => self::NAME_4, 'requirement' => self::REQ_4],
        self::ID_5 => ['id' => self::ID_5, 'name' => self::NAME_5, 'requirement' => self::REQ_5],
        self::ID_6 => ['id' => self::ID_6, 'name' => self::NAME_6, 'requirement' => self::REQ_6],
        self::ID_7 => ['id' => self::ID_7, 'name' => self::NAME_7, 'requirement' => self::REQ_7],
        self::ID_8 => ['id' => self::ID_8, 'name' => self::NAME_8, 'requirement' => self::REQ_8],
        self::ID_9 => ['id' => self::ID_9, 'name' => self::NAME_9, 'requirement' => self::REQ_9],
    ];

    const DB_TABLE = 'levels';
    const DB_DATABASE = 'autodrive_1';

    public function __construct(DB $db) {
        $this->db = DB::connection(self::DB_DATABASE);
    }

    /**
     * Undocumented function
     *
     * @param ConnectionInterface $db
     * @return void
     */
    public static function create_table(ConnectionInterface &$db = null): void {
        $db = ($db === null) ? DB::connection(self::$db_connection) : $db;
        $db->statement('
            CREATE TABLE levels (
                id TINYINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(32) NOT NULL,
                requirement TINYINT NOT NULL,
                qualified MEDIUMINT UNSIGNED NOT NULL,
                unqualified MEDIUMINT UNSIGNED NOT NULL,
                treshold TINYINT unsigned NOT NULL DEFAULT 0,
                updated DATETIME NOT NULL DEFAULT NOW()
            )
        ');
    }

    /**
     * Undocumented function
     *
     * @param ConnectionInterface $db
     * @return void
     */
    public static function drop_table(ConnectionInterface &$db = null): void {
        $db = ($db === null) ? DB::connection(self::$db_connection) : $db;
        $db->statement('DROP TABLE IF EXISTS levels');
    }

    public function getLevels() {

    }

    public function levelCountById($id) {
        $db = $this->db;
        $query = '

        ';
        return $db->select($query);
    }

    public function getDownlineLevelCount($key, $value) {
        $db = $this->db;
        $query = '
            SELECT `downlineLevelCount`
            FROM `members`
            WHERE ' . $key . ' = ' . $value . '
        ';
        return $db->select($query);
    }

    public function getLevelHistory($key, $value) {
        $db = $this->db;
        $query = '
            SELECT `levelHistory`
            FROM `members`
            where ' . $key . ' = ' . $value . '
        ';
        return $db->select($query);
    }

    public static function get_max_level() {
        return sizeof(self::$levels);
    }

    /**
     * 'Undocumented function'
     *
     * @param integer $max_level
     * @return Collection
     */
    public static function create_scenario(int $max_level): Collection {

        $levels = collect(self::$levels)->take($max_level)->reverse();
        $create_scenario = function ($accumulator, $level) {
            $ids         = $accumulator['ids'];
            $nextStart   = $accumulator['ids'];
            $nextEnd     = $accumulator['ids'];
            $data        = $accumulator['data'];
            $parentStart = $accumulator['parentStart'];
            $parentEnd   = $accumulator['parentEnd'];
            $siblings    = $accumulator['siblings'];

            for($parentId = $parentStart; $parentId <= $parentEnd; $parentId++) {
                for($sibling = 1; $sibling <= $siblings; $sibling++) {
                    $data->push([
                        'id'       => $ids,
                        'level'    => $level['id'],
                        'parentId' => ($parentId === 0) ? null : $parentId
                    ]);
                    $ids++;
                    $nextEnd++;
                }
            }
            return [
                'ids' => $ids,
                'parentStart' => $nextStart,
                'parentEnd' => $nextEnd - 1,
                'siblings' => $level['requirement'],
                'data' => $data
            ];
        };
        $data = $levels->reduce($create_scenario, [
            'ids' => 1,
            'parentStart' => 0,
            'parentEnd' => 0,
            'siblings' => 1,
            'data' => collect([])
        ]);
        return $data['data'];
    }

}
