<?php

namespace App\Logic;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class Promos {
    public        $collection    = [];
    public static $table_name    = 'promos';
    public static $db_connection = 'autodrive_tip';

    public static function create_table(&$db) {
        $db->statement('
            CREATE TABLE ' . self::$table_name . ' (
                id SMALLINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                created DATETIME NOT NULL DEFAULT NOW(),
                updated DATETIME NOT NULL DEFAULT NOW(),
                validUntil DATETIME NOT NULL DEFAULT \'1000-01-01 00:00:00\',
                note TEXT,
                image BLOB
            )
        ');
    }

    public static function drop_table(&$db) {
        $db->statement('DROP TABLE IF EXISTS ' . self::$table_name);        
    }

    public static function create_promo() {

    }
}