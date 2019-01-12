<?php
declare(strict_types=1);

namespace App\Repositories;
use Illuminate\Support\Facades\{DB, Storage};
use Illuminate\Support\Collection;
use App\Repositories\{Levels, MemberStatistics};
// use stdClass;
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
                qualificationHistory VARCHAR(128) NOT NULL DEFAULT \'[]\',
                downlineLevelCount VARCHAR(128) NOT NULL DEFAULT \'[]\',
                downlineCount MEDIUMINT UNSIGNED NOT NULL DEFAULT 0,
                levelHistory VARCHAR(128) NOT NULL DEFAULT \'[]\',
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
     * @return Collection
     */
    public static function query_increment_qualification(int $id, &$db = null): Collection {
        $db = ($db === null) ? DB::connection(self::$db_connection) : $db;
        $query = '
            UPDATE members
            SET qualification = qualification + 1

            WHERE id = ' . $id . '
        ';
        $db->update($query);
        $query = '
            SELECT qualification
            FROM members
            WHERE id = ' . $id . '
        ';
        $qualification = collect($db->select($query))->first()->qualification;
        $return = collect([
            'from' => $qualification - 1, 'to' => $qualification
        ]);
        return $return;
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
     * Undocumented function
     *
     * @param Object $member
     * @param Object|null $db
     * @return Bool
     */
    public static function can_increment_qualification(Object $member, Object &$db = null): bool {
        $db = ($db === null) ? DB::connection(self::$db_connection) : $db;
        $max = sizeof(Levels::$levels);
        $current = $member->qualification;
        $id = $member->id;
        if ($current >= $max) return false;
        $next = $current + 1;
        $count = self::query_count_qualification($id, $current, $db);
        $required = Levels::$levels[$next]['requirement'];
        return ($count >= $required);
    }

    public static function query_increment_downline(int $id, Object &$db = null) {
        $db = ($db === null) ? DB::connection(self::$db_connection) : $db;
        $query = '
            UPDATE members
            SET downlineCount = downlineCount + 1
            WHERE id = ' . $id . '
        ';
        $db->update($query);
    }

    /**
     * add new member
     * accepting array of member data
     * return new member id
     *
     * @param integer|null $parentId
     * @param Array $data
     * @param Object|null $db
     * @return integer
     */
    public static function add(int $parentId = null, Array $data = null, &$db = null): int {
        $db = ($db === null) ? DB::connection(self::$db_connection) : $db;
        $db->transaction(
            function () use($parentId, &$db, &$newId, $data) {
                $newId = $db->table('members')->insertGetId($data);
                $ancestors = self::query_get_ancestors($newId, $db);
                MemberStatistics::incrementStat(0, 'q1', $db);
                if ($parentId === null || $ancestors->isEmpty()) return;
                $ancestors->each(
                    function ($member) use(&$db, $ancestors, $parentId) {
                        MemberStatistics::incrementStat($member->id, 'q1', $db);
                        if (self::can_increment_qualification($member, $db)) {
                            $q = self::query_increment_qualification($member->id, $db);
                            $to = $q->get('to');
                            $from = $q->get('from');
                            MemberStatistics::incrementStat(0, 'q' . $to, $db);
                            MemberStatistics::decrementStat(0, 'q' . $from, $db);
                            self::query_get_ancestors($member->id, $db)->each(
                                function ($v) use($to, $from, &$db) {
                                    MemberStatistics::incrementStat($v->id, 'q' . $to, $db);
                                    MemberStatistics::decrementStat($v->id, 'q' . $from, $db);
                                }
                            );
                        }
                    }
                );
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
                SELECT id, parentId, qualification
                FROM members
                WHERE id="' . $id .  '"
                UNION
                SELECT member.id, member.parentId, member.qualification
                FROM members member,
                ancestors ancestor
                WHERE member.id = ancestor.parentId
            ),
            data AS (
                SELECT *
                FROM ancestors
                WHERE id != ' . $id . '
                ORDER BY id DESC, parentId DESC
            )
            SELECT *
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
