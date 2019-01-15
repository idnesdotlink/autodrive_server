<?php

namespace App\Logic;

use Illuminate\Database\ConnectionInterface;

interface HasTableInterface {
    public static function create_table(ConnectionInterface &$db);
    public static function drop_table(ConnectionInterface &$db);
}
