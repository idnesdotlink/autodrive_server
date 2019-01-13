<?php

namespace App\Logic;
use Illuminate\Support\Facades\{DB, Storage};
use Illuminate\Support\Collection;

class Installer {

    public static $dbName = 'autodrive_tip';

    public static function get_all_table() {
        return 'get all table';
    }

    public static function show_table() {
        $dbName = 'autodrive_1';
        $key = 'Tables_in_' . $dbName;
        $db = DB::connection('autodrive_tip');
        $arr = [];
        $db->transaction(
            function () use(&$db, $key, &$arr) {
                $tables = $db->select('show tables');
                // $size = sizeof($tables);
                /* if($size > 0) {
                    foreach($tables as $table) {
                        $arr[] = ['name' => $table->$key];
                    }
                } */
                foreach($tables as $table) {
                    $arr[] = ['name' => $table->$key];
                }
                // $arr[] = ['name' => 'psg'];
            }
        );
        return $arr;
    }

    public static function drop_all_table() {
        $dbName = 'autodrive_1';
        $key = 'Tables_in_' . $dbName;
        $db = DB::connection('autodrive_tip');
        $db->transaction(
            function () use($db, $key) {
                $tables = $db->select('show tables');
                $size = sizeof($tables);
                if($size > 0) {
                    foreach($tables as $table) {
                        $db->statement('DROP TABLE IF EXISTS ' . $table->$key);
                    }
                }
            }
        );
    }

    public static function tables($drop = false) {
        $db = DB::connection(self::$dbName);
        $db->transaction(
            function () use($db) {
                Members::drop_table($db);
                Members::create_table($db);
                Promos::drop_table($db);
                Promos::create_table($db);
                MemberQualification::drop_table($db);
                MemberQualification::create_table($db);
                $db->statement('DROP TABLE IF EXISTS member_revenues');
                $db->statement('
                    CREATE TABLE member_revenues (
                        memberId INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                        month CHAR(2) NOT NULL,
                        year CHAR(4) NOT NULL,
                        revenue DOUBLE UNSIGNED NOT NULL DEFAULT 0
                    )
                ');
                $db->statement('DROP TABLE IF EXISTS level_bonuses');
                $db->statement('
                    CREATE TABLE level_bonuses (
                        id SMALLINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                        levelId TINYINT UNSIGNED NOT NULL,
                        status TINYINT UNSIGNED NOT NULL DEFAULT 0,
                        type TINYINT UNSIGNED NOT NULL DEFAULT 1,
                        value DOUBLE UNSIGNED NOT NULL DEFAULT 0
                    )
                ');
                $db->statement('DROP TABLE IF EXISTS member_purchases');
                $db->statement('
                    CREATE TABLE member_purchases (
                        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                        memberId MEDIUMINT UNSIGNED NOT NULL,
                        created DATETIME DEFAULT NOW(),
                        updated DATETIME NOT NULL DEFAULT NOW(),
                        amount DOUBLE UNSIGNED DEFAULT 0,
                        discount DOUBLE UNSIGNED DEFAULT 0,
                        discounted DOUBLE UNSIGNED DEFAULT 0
                    )
                ');
                $db->statement('DROP TABLE IF EXISTS outlets');
                $db->statement('
                    CREATE TABLE outlets (
                        id SMALLINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                        created DATETIME NOT NULL DEFAULT NOW(),
                        updated DATETIME NOT NULL DEFAULT NOW(),
                        validUntil DATETIME NOT NULL DEFAULT \'1000-01-01 00:00:00\',
                        memberCount MEDIUMINT UNSIGNED NOT NULL DEFAULT 0,
                        note TEXT,
                        image BLOB
                    )
                ');
                $db->statement('DROP TABLE IF EXISTS products');
                $db->statement('
                    CREATE TABLE products (
                        id SMALLINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                        created DATETIME NOT NULL DEFAULT NOW(),
                        updated DATETIME NOT NULL DEFAULT NOW(),
                        price DOUBLE UNSIGNED DEFAULT 0,
                        note TEXT,
                        image BLOB
                    )
                ');
                $db->statement('DROP TABLE IF EXISTS agregate');
                $db->statement('
                    CREATE TABLE agregate (
                        id SMALLINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                        created DATETIME NOT NULL DEFAULT NOW(),
                        name VARCHAR(128) NOT NULL,
                        value TEXT,
                        type SMALLINT UNSIGNED DEFAULT 0
                    )
                ');
                $db->statement('DROP TABLE IF EXISTS event_logs');
                $db->statement('
                    CREATE TABLE event_logs (
                        id SMALLINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                        name VARCHAR(128) NOT NULL,
                        created DATETIME NOT NULL DEFAULT NOW()
                    )
                ');
            }
        );
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
        $data = Storage::disk('local')->get('data/members-level-5.json');
        $data = json_decode($data, true);
        $collection = collect($data);
        $collection->transform(function ($item) {
            return self::set_dummy_name($item);
        });
        return $collection;
    }
}
