<?php

namespace App\Repositories;
use Illuminate\Support\Facades\{DB, Storage};
use Illuminate\Support\Collection;
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

    /**
     *
     *
     * @param [type] $db
     * @return void
     */
    public static function drop_table(&$db): void {
        $db->statement('DROP TABLE IF EXISTS members');
    }

    /**
     *
     *
     * @param Array $item
     * @return Array
     */
    public static function set_dummy_name(Array $item): Array {
        $item['name'] = 'name_' . $item['id'];
        return $item;
    }

    /**
     * Undocumented function
     *
     * @return Collection
     */
    public static function get_dummy_members(): Collection {
        $data = Storage::disk('local')->get('data/members-level4.json');
        $data = json_decode($data, true);
        $collection = collect($data);
        $collection->transform(function ($item) {
            return self::set_dummy_name($item);
        });
        return $collection;
    }

    /**
     * Undocumented function
     *
     * @param Array $data
     * @param Object|null $db
     * @return void
     */
    public static function batch_insert(Array $data, &$db = null): void {
        $db = ($db === null) ? DB::connection(self::$db_connection) : $db;
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

    /**
     * Undocumented function
     *
     * @param Collection $collection
     * @param Object|null $db
     * @return integer
     */
    public static function query_increment_ancestor_downlineCount(Collection $collection, &$db = null): int {
        $db = ($db === null) ? DB::connection(self::$db_connection) : $db;
        $ancestors = $collection->implode(', ');
        $query = '
            UPDATE members
            SET downlineCount = downlineCount + 1
            WHERE id in (' . $ancestors . ')
        ';
        return $db->update($query);
    }

    /**
     * Undocumented function
     *
     * @param Collection $collection
     * @param Object|null $db
     * @return integer
     */
    public static function query_update_downlineLevelCount(Collection $collection, &$db = null): int {
        $db = ($db === null) ? DB::connection(self::$db_connection) : $db;
        $ancestors = $collection->implode(', ');
        $query = '
            UPDATE members
            SET downlineCount = downlineCount + 1
            WHERE id in (' . $ancestors . ')
        ';
        return $db->update($query);
    }

    /**
     * Undocumented function
     *
     * @param integer $id
     * @param Object|null $db
     * @return integer
     */
    public static function query_increment_level(int $id, &$db = null): int {
        $db = ($db === null) ? DB::connection(self::$db_connection) : $db;
        $query = '
            UPDATE members
            SET level = level + 1
            WHERE id = ' . $id . '
        ';
        return $db->update($query);
    }

    /**
     * Undocumented function
     *
     * @param integer $id
     * @param Object|null $db
     * @return integer
     */
    public static function query_increment_qualification(int $id, &$db = null): int {
        $db = ($db === null) ? DB::connection(self::$db_connection) : $db;
        $query = '
            UPDATE members
            SET qualification = qualification + 1
            WHERE id = ' . $id . '
        ';
        return $db->update($query);
    }

    /**
     * Undocumented function
     *
     * @param integer $id
     * @param integer $level
     * @param Object|null $db
     * @return integer
     */
    public static function query_count_level(int $id, int $level, &$db = null): int {
        $db = ($db === null) ? DB::connection(self::$db_connection) : $db;
        $query = '
            WITH RECURSIVE descendants AS
            (
                SELECT id, parentId, level
                FROM members
                WHERE id="' . $id .  '"

                UNION ALL

                SELECT member.id, member.parentId, member.level
                FROM members member,
                descendants descendant
                WHERE member.parentId = descendant.id AND
                member.level = ' . $level . '
            ),
            data AS (
                SELECT COUNT(id) AS counter
                FROM descendants
                WHERE id != ' . $id . '
            )
            SELECT *
            FROM data
        ';
        $ancestors = $db->select($query);
        return collect($ancestors)->first()->counter;
    }

    /**
     * Undocumented function
     *
     * @param integer $id
     * @param integer $level
     * @param Object|null $db
     * @return integer
     */
    public static function query_count_qualification(int $id, int $qualification, &$db = null): int {
        $db = ($db === null) ? DB::connection(self::$db_connection) : $db;
        $query = '
            WITH RECURSIVE descendants AS
            (
                SELECT id, parentId, qualification
                FROM members
                WHERE id="' . $id .  '"

                UNION ALL

                SELECT member.id, member.parentId, member.qualification
                FROM members member,
                descendants descendant
                WHERE member.parentId = descendant.id AND
                member.qualification = ' . $qualification . '
            ),
            data AS (
                SELECT COUNT(id) AS counter
                FROM descendants
                WHERE id != ' . $id . '
            )
            SELECT *
            FROM data
        ';
        $ancestors = $db->select($query);
        return collect($ancestors)->first()->counter;
    }

    /**
     * add new member
     * accepting array of member data
     * return new member id
     *
     * @param integer $parentId
     * @param Array $data
     * @param Object|null $db
     * @return integer
     */
    public static function add(int $parentId = NULL, Array $data = NULL, &$db = null): int {
        $db = ($db === null) ? DB::connection(self::$db_connection) : $db;
        $db->transaction(
            function () use($parentId, &$db, &$newId, $data) {
                $newId = $db->table('members')->insertGetId($data);
                $ancestors = self::query_get_ancestors($newId, $db);

                if ($parentId === NULL || $ancestors->isEmpty()) return;

                /* $ancestors->transform(
                    function($member) {
                        $member = self::get_one($member->id);
                    }
                ); */

                $ancestors->each(
                    function ($value) use(&$db) {
                        $ancestor = self::get_one($value->id);

                        $increment_qualification = true;
                        $increment_level = false;

                        if ($increment_qualification) {
                            $maxQualification = sizeof(Levels::$levels);
                            $currentQualification = $ancestor->qualification;
                            $ancestorId = $ancestor->id;
                            if ($currentQualification >= $maxQualification) return;
                            $nextQualification = $currentQualification + 1;
                            $required_qualification_count = self::query_count_qualification($ancestorId, $currentQualification, $db);
                            $required = Levels::$levels[$nextQualification]['requirement'];
                            if ($required_qualification_count >= $required) {
                                self::query_increment_qualification($ancestorId, $db);
                            }
                        }

                        if ($increment_level) {
                            $maxLevel = sizeof(Levels::$levels);
                            $currentLevel = $ancestor->level;
                            $ancestorId = $ancestor->id;
                            if($currentLevel >= $maxLevel) return;
                            $nextLevel = $currentLevel + 1;
                            $required_level_count = self::query_count_level($ancestorId, $currentLevel, $db);
                            $required = Levels::$levels[$nextLevel]['requirement'];
                            if ($required_level_count >= $required) {
                                self::query_increment_level($ancestorId, $db);
                            }
                        }
                    }
                );

                // downlineCount
                // self::query_increment_ancestor_downlineCount($ancestors, $db);
            },
            5
        );
        return $newId;
    }

    /**
     * Undocumented function
     *
     * @param integer $id
     * @param Object|null $db
     * @return Collection
     */
    public static function get_descendants(int $id, &$db = null): Collection {
        $db = ($db === null) ? DB::connection(self::$db_connection) : $db;
        $query = '
            WITH RECURSIVE descendants AS
            (
                SELECT id, parentId
                FROM members
                WHERE id="' . $id .  '"

                UNION ALL

                SELECT member.id, member.parentId
                FROM members member,
                descendants descendant
                WHERE member.parentId = descendant.id
            ),
            data AS (
                SELECT id
                FROM descendants
                WHERE id != ' . $id . '
                ORDER BY id
            )
            SELECT *
            FROM data
        ';
        $ancestors = $db->select($query);
        return collect($ancestors);
    }

    /**
     * get count of descendants
     *
     * @param integer $id
     * @param Object|null $db
     * @return integer
     */
    public static function get_descendants_count(int $id, &$db = null): int {
        $db = ($db === null) ? DB::connection(self::$db_connection) : $db;
        $query = '
            WITH RECURSIVE descendants AS
            (
                SELECT id, parentId
                FROM members
                WHERE id="' . $id .  '"

                UNION ALL

                SELECT member.id, member.parentId
                FROM members member,
                descendants descendant
                WHERE member.parentId = descendant.id
            ),
            get AS (
                SELECT id
                FROM descendants
                WHERE id != ' . $id . '
            )
            SELECT COUNT(id) as count
            FROM get
        ';
        $ancestors = $db->select($query);
        return collect($ancestors)->first()->count;
    }

    /**
     * Undocumented function
     *
     * @param integer $id
     * @param Object|null $db
     * @return Collection
     */
    public static function get_siblings(int $id, &$db = null): Collection {
        $db = ($db === null) ? DB::connection(self::$db_connection) : $db;
        $table_name = self::$table_name;
        $parent = '
            SELECT parentId FROM ' . $table_name . '
            WHERE id = ' . $id . '
            LIMIT 1
        ';
        $parent = collect($db->select($parent));
        $parentId = $parent->first()->parentId;
        if ($parent->isEmpty() || $parentId === NULL) return collect([]);
        $siblings = '
            SELECT * FROM ' . $table_name . '
            WHERE parentId = ' . $parentId . '
            AND id != ' . $id . '
        ';
        $siblings = collect($db->select($siblings));
        return $siblings;
    }

    /**
     * Undocumented function
     *
     * @param integer $id
     * @param [type] $db
     * @return Collection
     */
    public static function query_get_ancestors(int $id, &$db = null): Collection {
        $db = ($db === null) ? DB::connection(self::$db_connection) : $db;
        $query = '
            WITH RECURSIVE ancestors AS
            (
                SELECT id, parentId
                FROM members
                WHERE id="' . $id .  '"
                UNION
                SELECT member.id, member.parentId
                FROM members member,
                ancestors ancestor
                WHERE member.id = ancestor.parentId
            ),
            data AS (
                SELECT id, parentId
                FROM ancestors
                WHERE id != ' . $id . '
                ORDER BY id DESC, parentId DESC
            )
            SELECT id
            FROM data
        ';
        $ancestors = $db->select($query);
        return collect($ancestors);
    }

    /**
     * Get collection of ancestors id
     *
     * @param integer $id
     * @return Collection
     */
    public static function get_ancestors(int $id, &$db = null): Collection {
        $db = ($db === null) ? DB::connection(self::$db_connection) : $db;
        $ancestors = self::query_get_ancestors($id, $db);
        return $ancestors;
    }

    /**
     * Undocumented function
     *
     * @param integer $id
     * @param Object|null $db
     * @return integer
     */
    public static function get_ancestors_count(int $id, &$db = null): int {
        $db = ($db === null) ? DB::connection(self::$db_connection) : $db;
        $query = '
            WITH RECURSIVE ancestors AS
            (
                SELECT id, parentId
                FROM members
                WHERE id="' . $id .  '"
                UNION
                SELECT member.id, member.parentId
                FROM members member,
                ancestors ancestor
                WHERE member.id = ancestor.parentId
            ),
            data AS (
                SELECT id
                FROM ancestors
                WHERE id != ' . $id . '
            )
            SELECT COUNT(id) AS count
            FROM data
        ';
        $ancestors = $db->select($query);
        return collect($ancestors)->first()->count;
    }

    /**
     * get collection of direct descendant / children
     *
     * @param integer $id
     * @return Collection
     */
    public static function get_children(int $id, &$db = null): Collection {
        $db = ($db === null) ? DB::connection(self::$db_connection) : $db;
        $query = '
            WITH child AS (
                SELECT id, parentId
                FROM members
                WHERE parentId = "' . $id . '"
            )
            select id
            FROM child
            ORDER BY id
        ';
        $children = $db->select($query);
        return collect($children);
    }

    /**
     * get count of direct descendant / children
     *
     * @param integer $id
     * @return int
     */
    public static function get_children_count(int $id, &$db = null): int {
        $db = ($db === null) ? DB::connection(self::$db_connection) : $db;
        $query = '
            WITH child AS (
                SELECT id, parentId
                FROM members
                WHERE parentId = "' . $id . '"
            )
            select COUNT(id) as counter
            FROM child
            ORDER BY id
        ';
        $children = $db->select($query);
        return collect($children)->first()->counter;
    }

    /**
     * Undocumented function
     *
     * @param integer $page
     * @param integer $perPage
     * @param integer $level
     * @return Collection
     */
    public static function get_all(int $page, int $perPage, int $level): Collection {
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

    /**
     * Undocumented function
     *
     * @param integer $id
     * @param Object|null $db
     * @return Object
     */
    public static function get_one(int $id, &$db = null): Object {
        $db = ($db === null) ? DB::connection(self::$db_connection) : $db;
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

    /**
     * Undocumented function
     *
     * @param integer|null $id
     * @return Collection
     */
    public static function members_statistic(int $id = null): Collection {

    }
}
