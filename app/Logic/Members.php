<?php
declare(strict_types=1);

namespace App\Logic;
use Illuminate\Support\Facades\{DB, Storage};
use Illuminate\Support\Collection;
use App\Logic\{Levels, MemberQualification, HasTableInterface};
use Illuminate\Database\ConnectionInterface;
// use stdClass;
class Members implements HasTableInterface {

    public static $table_name = 'members';
    public static $db_connection = 'autodrive_tip';

    /**
     * Undocumented function
     *
     * @param ConnectionInterface $db
     * @return void
     */
    public static function create_table(ConnectionInterface &$db) {
        $validUntilConfig = 12;
        $db->statement('
            CREATE TABLE members (
                id MEDIUMINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                memberId VARBINARY(16) NOT NULL DEFAULT \'\',
                parentId MEDIUMINT UNSIGNED DEFAULT NULL,
                outletId SMALLINT UNSIGNED NOT NULL DEFAULT 0,
                name VARCHAR(128) NOT NULL,
                gender CHAR(1) NOT NULL DEFAULT \'L\',
                mobile_phone VARCHAR(32) NOT NULL DEFAULT \'\',
                mobile_phone_secondary VARCHAR(32) NOT NULL DEFAULT \'\',
                email VARCHAR(32) NOT NULL DEFAULT \'\',
                village CHAR(10) DEFAULT \'\',
                address TEXT,
                level TINYINT(1) UNSIGNED NOT NULL DEFAULT 1,
                qualification TINYINT(1) UNSIGNED NOT NULL DEFAULT 1,
                downlineCount MEDIUMINT UNSIGNED NOT NULL DEFAULT 0,
                children MEDIUMINT UNSIGNED NOT NULL DEFAULT 0,
                status TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
                created DATETIME DEFAULT NOW(),
                updated DATETIME NOT NULL DEFAULT NOW(),
                validUntil DATETIME NOT NULL DEFAULT DATE_ADD(NOW(), INTERVAL ' . $validUntilConfig . ' MONTH),
                registrationFee DOUBLE UNSIGNED NOT NULL DEFAULT 0,
                UNIQUE KEY memberId (memberId)
            )
        ');
    }

    /**
     * Undocumented function
     *
     * @param ConnectionInterface $db
     * @return void
     */
    public static function drop_table(ConnectionInterface &$db): void {
        $db->statement('DROP TABLE IF EXISTS members');
    }

    /**
     * Undocumented function
     *
     * @param array $data
     * @param Object|null $db
     * @return void
     */
    public static function batch_insert(array $data, &$db = null): void {
        $db = ($db === null) ? DB::connection(self::$db_connection) : $db;
        $table_name = self::$table_name;
        try {
            $db->transaction(
                function () use ($data, $db, $table_name) {
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

    public static function get_status_human_readable(int $id, &$db) {
        $db = ($db === null) ? DB::connection(self::$db_connection) : $db;
        $query = '

        ';
    }

    /**
     * Undocumented function
     *
     * @param string $term
     * @return integer
     */
    public static function get_status_key(string $term = ''): int {
        switch($term) {
            case 'active':
                $status = 1;
                break;
            case 'grace period':
                $status = 2;
                break;
            case 'expired':
                $status = 3;
                break;
            default:
                $status = 0;
                break;
        }
        return $status;
    }

    /**
     * Undocumented function
     *
     * @param integer $key
     * @return string
     */
    public static function get_status_term(int $key): string {
        switch($key) {
            case 1:
                $term = 'active';
                break;
            case 2:
                $term = 'grace period';
                break;
            case 3:
                $term = 'expired';
                break;
            default:
                $term = 'unknown';
                break;
        }
        return $term;
    }

    /**
     * Undocumented function
     *
     * @param Object $db
     * @return void
     */
    public static function set_grace_period(Object &$db): void {
        $db = ($db === null) ? DB::connection(self::$db_connection) : $db;
        $query = '
            UPDATE members
            SET status = 2
            WHERE validUntil < DATE_SUB(NOW(), INTERVAL 1 MONTH)
        ';
        $db->update($query);
    }

    /**
     * Undocumented function
     *
     * @param Object $db
     * @return void
     */
    public static function set_expired(Object &$db): void {
        $db = ($db === null) ? DB::connection(self::$db_connection) : $db;
        $query = '
            UPDATE members
            SET status = 2
            WHERE validUntil < NOW()
        ';
        $db->update($query);
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
        // $count = self::query_count_qualification($id, $current, $db);
        $required = Levels::$levels[$next]['requirement'];
        $count = MemberQualification::get_qualification('q' . $current, $id, $db);
        return ($count >= $required);
    }

    /**
     * add new member
     * accepting array of member data
     * return new member id
     *
     * @param integer|null $parentId
     * @param array $data
     * @param Object|null $db
     * @return integer
     */
    public static function add(int $parentId = null, array $data = null, Object &$db = null): int {
        $db = ($db === null) ? DB::connection(self::$db_connection) : $db;

        $add_trans = function () use ($parentId, &$db, &$newId, $data) {
            $newId = $db->table('members')->insertGetId($data);
            $ancestors = self::query_get_ancestors($newId, $db);
            MemberQualification::increment(0, 'q1', null, $db);
            if ($parentId === null || $ancestors->isEmpty()) return;

            $eachCall = function ($member) use (&$db, $ancestors) {
                MemberQualification::increment($member->id, 'q1', $member->qualification, $db);

                if (self::can_increment_qualification($member, $db)) {
                    $q = self::query_increment_qualification($member->id, $db);
                    $to = $q->get('to');
                    $from = $q->get('from');
                    MemberQualification::increment(0, 'q' . $to, $member->qualification, $db);
                    MemberQualification::decrement(0, 'q' . $from, $member->qualification, $db);
                    self::query_get_ancestors($member->id, $db)->each(
                        function ($v) use ($to, $from, &$db) {
                            MemberQualification::increment($v->id, 'q' . $to, $v->qualification, $db);
                            MemberQualification::decrement($v->id, 'q' . $from, $v->qualification, $db);
                        }
                    );
                }
            };

            $ancestors->each($eachCall);
        };

        $db->transaction($add_trans, 5);
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
     * @param ConnectionInterface $db
     * @return Collection
     */
    public static function get_children(int $id, ConnectionInterface &$db = null): Collection {
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
     * @param ConnectionInterface $db
     * @return integer
     */
    public static function get_children_count(int $id, ConnectionInterface &$db = null): int {
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

    public static function test() {
        print_r('pusing');
    }
}
