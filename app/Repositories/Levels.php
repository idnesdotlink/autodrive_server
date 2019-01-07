<?php

namespace App\Repositories;

use Illuminate\Support\Facades\DB;

class Levels {

    const ID_1 = 1;
    const ID_2 = 2;
    const ID_3 = 3;
    const ID_4 = 4;
    const ID_5 = 5;
    const ID_6 = 6;
    const ID_7 = 7;
    const ID_8 = 8;
    const ID_9 = 9;

    const NAME_1 = 'bronze';
    const NAME_2 = 'silver';
    const NAME_3 = 'gold';
    const NAME_4 = 'diamond';
    const NAME_5 = 'double diamond';
    const NAME_6 = 'triple diamond';
    const NAME_7 = 'ambasador';
    const NAME_8 = 'double ambasador';
    const NAME_9 = 'triple ambasador';

    const REQ_1 = null;
    const REQ_2 = 3;
    const REQ_3 = 4;
    const REQ_4 = 5;
    const REQ_5 = 3;
    const REQ_6 = 4;
    const REQ_7 = 3;
    const REQ_8 = 5;
    const REQ_9 = 3;

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

    public function create_table() {
        $table = self::DB_TABLE;
        $query = 'CREATE TABLE ' . $table .
        '(

        )';
        $this->db->statement($query);
    }

    public function drop_table() {
        $query = 'DROP TABLE IF EXISTS ' . self::DB_TABLE;
        $this->db->statement($query);
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

}
