<?php

namespace App\Repositories;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Repositories\Levels;

class Members {

    public static $table_name = 'members';
    public static $db_connection = 'autodrive_tip';

    public static function create_table(&$db) {
        $db->statement('
            CREATE TABLE members (
                id MEDIUMINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                parentId MEDIUMINT UNSIGNED DEFAULT NULL,
                outletId SMALLINT UNSIGNED NOT NULL DEFAULT 0,
                name VARCHAR(128) NOT NULL,
                mobile_phone VARCHAR(32) NOT NULL DEFAULT \'\',
                email VARCHAR(32) NOT NULL DEFAULT \'\',
                address TEXT,
                city VARCHAR(128) NOT NULL DEFAULT \'\',
                province VARCHAR(128) NOT NULL DEFAULT \'\',
                level TINYINT UNSIGNED NOT NULL DEFAULT 1,
                qualification TINYINT UNSIGNED NOT NULL DEFAULT 1,
                downlineLevelCount VARCHAR(512) NOT NULL DEFAULT \'[]\',
                downlineCount MEDIUMINT UNSIGNED NOT NULL DEFAULT 0,
                levelHistory VARCHAR(256) NOT NULL DEFAULT \'[]\',
                status TINYINT UNSIGNED NOT NULL DEFAULT 1,
                created DATETIME DEFAULT NOW(),
                updated DATETIME NOT NULL DEFAULT NOW(),
                validUntil DATETIME NOT NULL DEFAULT \'1000-01-01 00:00:00\',
                registationFee DOUBLE UNSIGNED NOT NULL DEFAULT 0,
                CHECK (JSON_VALID(downlineLevelCount)),
                CHECK (JSON_VALID(levelHistory))
            )
        ');
    }

    public static function drop_table(&$db) {
        $db->statement('DROP TABLE IF EXISTS members');
    }

    public static function set_dummy_name($item) {
        $item['name'] = 'name_' . $item['id'];
        return $item;
    }

    public static function get_dummy_members() {
        $data = Storage::disk('local')->get('data/members.json');
        $data = json_decode($data, true);
        $collection = collect($data);
        $collection->transform(function ($item) {
            return self::set_dummy_name($item);
        });
        return $collection;
    }

    /**
     * argument
     * $data = collection
     * return void
    */
    public static function batch_insert($data) {
        $db = DB::connection(self::$db_connection);
        $table_name = self::$table_name;
        try {
            $db->transaction(
                function () use($data, $db, $table_name) {
                    $members = $db->table($table_name);
                    $chunk = $data->chunk(500);
                    foreach($chunk as $chunked) {
                        $members->insert($chunked->all());
                    }
                }
            );
        } catch (Exception $e) {
            throw $e;
        }
    }

    public static function query_increment_level($id, &$db, $update = false) {
        $query = '
            UPDATE members
            SET level = level + 1
            WHERE id = ' . $id . '
        ';
        return $db->update($query);
    }

    public static function query_increment_qualification($id, &$db, $update = false) {
        $query = '
            UPDATE members
            SET qualification = qualification + 1
            WHERE id = ' . $id . '
        ';
        return $db->update($query);
    }

    public static function query_count_level($id, $level, &$db) {
        $query = '
            WITH RECURSIVE descendants AS
            (
                SELECT id, level, qualification
                FROM members
                WHERE id="' . $id .  '"

                UNION ALL

                SELECT member.id, member.level, member.qualification
                FROM members member,
                descendants descendant
                WHERE member.parentId=descendant.id AND
                member.level > ' . $level . '
            ),
            get AS (
                SELECT *
                FROM descendants
                WHERE id != ' . $id . '
            )
            SELECT COUNT(id) as LevelCount
            FROM get
        ';
        $ancestors = $db->select($query);
        return collect($ancestors)->first()->LevelCount;
    }

    public static function query_get_ancestors($id, &$db) {
        $query = 'WITH RECURSIVE ancestor AS
        (
            SELECT * FROM members WHERE id="' . $id .  '"
            UNION ALL
            SELECT member.*
            FROM members member
            JOIN ancestor
            ON member.id=ancestor.parentId
        ),
        get AS (
            SELECT * FROM ancestor
        )
        SELECT id, level, qualification FROM get';
        $ancestors = $db->select($query);
        return collect($ancestors)->splice(1)->reverse();
    }

    public static function add_downline($id = null) {
        $db = DB::connection(self::$db_connection);
        $db->transaction(
            function () use($id, &$db, &$newId) {
                $newId = $db->table('members')->insertGetId([
                    'parentId' => $id,
                    'name'     => 'member name ' . $id
                ]);
                // if ($id === null) return;
                $ancestors = self::query_get_ancestors($id, $db);
                if ($ancestors->count() < 1) return;
                $ancestors->each(
                    function ($value) use(&$db) {
                        // $nextLevel = $value->level + 1;
                        // $required = self::query_count_level($value->id, $value->level + 1, $db);
                        // if ($required >= Levels::$levels[$value->level]['requirement']) {
                            self::query_increment_level($value->id, $db);
                        // }
                    }
                );
            }
        );
        return $newId;
    }

    public static function get_descendants($id) {
        /* $db = DB::connection(self::$db_connection);
        // Levels::$levels[$value->level];
        $descendant = self::query_count_level($id, 12, $db);
        return $descendant; */
    }

    public static function get_siblings($id) {
        $db = DB::connection(self::$db_connection);
        $table_name = self::$table_name;
        $parent = '
            SELECT parentId FROM ' . $table_name . '
            WHERE id = ' . $id . '
            LIMIT 1
        ';
        $parent = collect($db->select($parent));
        $parentId = $parent->first()->parentId;
        if ($parent->isEmpty() || $parentId === null) return collect([]);
        $siblings = '
            SELECT * FROM ' . $table_name . '
            WHERE parentId = ' . $parentId . '
            AND id != ' . $id . '
        ';
        $siblings = collect($db->select($siblings));
        return $siblings->all();
    }

    public static function get_ancestors($search, $key = 'id') {
        $db = DB::connection(self::$db_connection);
        $query = '
            WITH RECURSIVE ancestors AS
            (
                SELECT *
                FROM members
                WHERE ' . $key . '="' . $search .  '"
                UNION
                SELECT member.*
                FROM members member,
                ancestors ancestor
                WHERE member.id = ancestor.parentId
            ),
            get AS (
                SELECT * FROM ancestors
                WHERE id != ' . $search . '
            )
            SELECT * FROM get
        ';
        $ancestors = $db->select($query);
        return collect($ancestors);
    }

    public static function get_children($id) {
        $db = DB::connection(self::$db_connection);
        $query = '
            SELECT *
            FROM members
            WHERE parentId = "' . $id . '"
        ';
        $children = $db->select($query);
        return collect($children);
    }

    public static function get_all($page, $perPage, $level) {
        $db     = DB::connection(self::$db_connection);
        $offset = 0;
        $count  = $perPage ? $perPage : 10;

        $query = '
            SELECT *
            FROM members
            ORDER BY id
            LIMIT ' . $offset . ', ' . $count . '
        ';
        $members = $db->select($query);
        return collect($members);
    }

    public static function get_one($id) {
        $db     = DB::connection(self::$db_connection);
        $query = '
            SELECT *
            FROM members
            WHERE id = "' . $id . '"
            ORDER BY id
            LIMIT 1
        ';
        $members = $db->select($query);
        return collect($members)->first();
    }

    public static function insert() {
        $db = DB::connection(self::$db_connection);
        $dummy = get_dummy_members();
        $chunk = $dummy->chunk(500);
        foreach($chunk as $chunked) {
            try {
                $chunked->transform(
                    function ($item) {
                        $item['parentId'] = $item['parentId'] ? $item['parentId'] : 0;
                        $item['name'] = 'name_' . $item['id'];
                        return $item;
                    }
                )->each(
                    function ($item, $key) {

                    }
                );
            } catch (Exception $error) {

            }
        }
    }

    public static function add($members) {
        $db = DB::connection(self::$db_connection);
        $db->transaction(
            function () use(&$db) {
            //
        }
        );
    }

    public static function update() {

    }

    public static function getByProperty($property, $propertyKey = 'id') {

    }

    public static function getByName($name) {}

    public static function getById($id) {
        return $this->getByProperty($id, 'id')->first();
    }

    public static function create_dummy_one($parentId) {
        $newId = self::add_downline($parentId);
        return $newId;
    }
}
