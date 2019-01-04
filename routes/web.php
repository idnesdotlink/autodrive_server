<?php

use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;

/* function get_ancestors() {
    $db = DB::connection('autodrive_tip');
    $query = '
    WITH RECURSIVE ancestorPath AS (
        SELECT parent.id, parent.parentId, parent.name, 1 depth, CAST(0 AS VARCHAR(200)) AS p
        FROM members parent
        WHERE parent.parentId IS NULL

        UNION ALL

        SELECT Child.id, Child.parentId, Child.name, ANC.depth + 1, CONCAT(ANC.p, ",", Child.parentId)
        FROM members Child
        INNER JOIN ancestorPath ANC
        ON ANC.id = Child.parentId
        WHERE Child.parentId IS NOT NULL
    )
    SELECT *
    FROM ancestorPath
    ';
    $ancestors = $db->select($query);
    return $ancestors;
} */

function get_dummy_levels() {
    $levels_dummy_data = [
        [1, null],
        [2,3],
        [3,4],
        [4,5],
        [5,3],
        [6,4],
        [7,3],
        [8,4],
        [9,4]
    ];
    $collection = collect($levels_dummy_data);
    return $collection;
}

function get_dummy_members() {
    $members_dummy_data = json_decode(include_once('members.php'), true);
    $collection = collect($members_dummy_data);
    return $collection;
}

function insert_dummy_members() {
    $db = DB::connection('autodrive_tip');
    $members = $db->table('members');
    $dummy = get_dummy_members();
    $db->transaction(
        function () use($members, $dummy) {
            $chunk = $dummy->chunk(1000);
            foreach($chunk as $chunked) {
                $withName = $chunked->map(function($item) {
                    $item['parentId'] = $item['parentId'] ? $item['parentId'] : 0;
                    $item['name'] = 'name_' . $item['id'];
                    return $item;
                });
                $members->insert($withName->all());
            }
        }
    );
}

function insert_dummy_levels($db) {
    $levels = $db->table('levels');
    $dummy = get_dummy_levels();
}

function tables($drop = false) {
    $db = DB::connection('autodrive_tip');
    $db->transaction(
        function () use($db) {
            $db->statement('DROP TABLE IF EXISTS levels');
            $db->statement('
                CREATE TABLE levels (
                    id TINYINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    name VARCHAR(32) NOT NULL,
                    requirement TINYINT NOT NULL,
                    qualified INT(32) NOT NULL,
                    unqualified INT(32) NOT NULL,
                    treshold TINYINT unsigned NOT NULL DEFAULT 0
                )
            ');
            $db->statement('DROP TABLE IF EXISTS members');
            $db->statement('
                CREATE TABLE members (
                    id MEDIUMINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    parentId MEDIUMINT NOT NULL DEFAULT 0,
                    name VARCHAR(128) NOT NULL,
                    level TINYINT UNSIGNED NOT NULL DEFAULT 1,
                    qualification TINYINT UNSIGNED NOT NULL DEFAULT 1,
                    downlineLevelCount VARCHAR(512) NOT NULL DEFAULT "[]",
                    downlineCount INT(11) UNSIGNED,
                    levelHistory VARCHAR(256) NOT NULL DEFAULT "[]",
                    status TINYINT UNSIGNED NOT NULL DEFAULT 1,
                    created DATETIME NOT NULL DEFAULT NOW(),
                    CHECK (JSON_VALID(downlineLevelCount)),
                    CHECK (JSON_VALID(levelHistory))
                )
            ');
            $db->statement('DROP TABLE IF EXISTS member_revenue');
            $db->statement('
                CREATE TABLE member_revenue (
                    memberId INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    month CHAR(2) NOT NULL,
                    year CHAR(4) NOT NULL,
                    revenue DOUBLE UNSIGNED DEFAULT 0
                )
            ');
            $db->statement('DROP TABLE IF EXISTS member_purchase');
            $db->statement('
                CREATE TABLE member_purchase (
                    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    memberId MEDIUMINT,
                    created DATETIME,
                    amount DOUBLE UNSIGNED DEFAULT 0
                )
            ');
            $db->statement('DROP TABLE IF EXISTS promo');
            $db->statement('
                CREATE TABLE promo (
                    id SMALLINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    created DATETIME,
                    validUntil DATETIME,
                    note TEXT,
                    image 
                )
            ');
        }
    );
}

function drop_all_table() {
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

function get_ancestors($search, $key = 'id') {
    $db = DB::connection('autodrive_tip');
    $query = '
    WITH RECURSIVE ancestor AS
    (
        SELECT * FROM members WHERE ' . $key . '="' . $search .  '"
        UNION ALL
        SELECT member.*
        FROM members member
        JOIN ancestor
        ON member.id=ancestor.parentId
    ),
    get AS (
        SELECT * FROM ancestor
    )
    SELECT * FROM get
';
    $ancestors = $db->select($query);
    return $ancestors;
}

Route::domain('api.trial205.tiptech.network')->group(function () {
    Route::get('/', function () {
        return view('api.welcome');
    });
});

Route::get('/login', function () {
    return view('main.welcome');
})->name('login');

Route::get('/', function () {
    return view('main.welcome');
})->name('home');

Route::get('/db', function () {
    return view('main.db');
})->name('db');

Route::get('/db/create', function () {
    try {
        tables();
    } catch (Exception $error) {
        return redirect('/db')->with('error', $error->getMessage());
    }
    return redirect('/db')->with('status', 'created!');
})->name('db.create');

Route::get('/db/delete', function () {
    try {
        drop_all_table();
    } catch(Exception $error) {
        return redirect('/db')->with('error', $error->getMessage());
    }
    return redirect('/db')->with('status', 'deleted!');
})->name('db.delete');

Route::get('/db/seed', function () {
    try {
        insert_dummy_members();
    } catch (Exception $error) {
        return redirect('/db')->with('error', $error->getMessage());
    }
    return redirect('/db')->with('status', 'seeded!');
})->name('db.seed');

Route::get('/db/count', function () {
    $db = DB::connection('autodrive_tip');
    $tables = $db->select('show tables');
    $tables = sizeof($tables);
    return redirect('/db')->with('status', 'count: ' . $tables);
})->name('db.count');

Route::get('/db/list', function () {
    $dbName = 'autodrive_1';
    $key = 'Tables_in_' . $dbName;
    $db = DB::connection('autodrive_tip');
    $tables = $db->select('show tables');
    $size = sizeof($tables);
    $tableName = [];
    if($size > 0) {
        foreach($tables as $table) {
            $tableName[] = $table->$key;
        }
    }
    return redirect('/db')->with('tables', $tableName);
})->name('db.list');

Route::get('/db/hierarchy', function () {
    $db = DB::connection('autodrive_tip');

$query = '
WITH RECURSIVE ancestorPath AS (
    SELECT parent.id, parent.parentId, parent.name, 1 depth, CAST(0 AS VARCHAR(200)) AS p
    FROM members parent
    WHERE parent.parentId IS NULL

    UNION ALL

    SELECT Child.id, Child.parentId, Child.name, ANC.depth + 1, CONCAT(ANC.p, ",", Child.parentId)
    FROM members Child
    INNER JOIN ancestorPath ANC
    ON ANC.id = Child.parentId
    WHERE Child.parentId IS NOT NULL
)
SELECT *
FROM ancestorPath
';

$query = 'WITH RECURSIVE cte AS
(
    SELECT id, name, parentId FROM members WHERE name="name_11"
    UNION ALL
    SELECT c.id, c.name, c.parentId FROM members c JOIN cte
    ON c.id=cte.parentId
)
SELECT * FROM cte
';

/* $query3 = '
WITH RECURSIVE descendants AS
(
SELECT person
FROM tree
WHERE person="' . $id . '"
UNION ALL
SELECT t.person
FROM descendants d, tree t
WHERE t.parent=d.person
)
SELECT * FROM descendants;
'; */

$query2 = 'SELECT * FROM members';

    $tables = $db->select($query);
    print_r($tables);
    foreach($tables as $table) {
        // echo 'id: ' . $table->id . ' level:' . $table->level . ' path:' . $table->p . '<br/>';
        echo 'id: ' . $table->id . ' parent:' . $table->parentId . '<br/>';
    }

})->name('db.hierarchy');

Route::get('/db/sessions', function () {
    Artisan::call('session:table');
})->name('db.sessions');

Route::get('/db/migrate', function () {
    try {
        Artisan::call('migrate');
    } catch (Exception $error) {
        return redirect('/db')->with('error', $error->getMessage());
    }
    return redirect('/db')->with('status', 'migrated');
})->name('db.migrate');

Route::get('/db/add', function () {
    return view('main.add');
})->name('db.add');

Route::post('/db/a/add', function () {
    $db = DB::connection('autodrive_tip');
    $input = request()->input();
    ['name' => $name, 'parent' => $parent] = $input;
    $query = '';
    $error = false;
    $errorMessage = null;
    if ( $name === null && strlen($name) > 0) return redirect()->route('db.add');
    try {
    $db->transaction(
        function () use($db, $parent, $name, &$tables) {

            $table = $db->table('members');
            $lastId = $table->insertGetId(
                [
                    'parentId' => $parent,
                    'name' => $name
                ]
            );

            $list = $db->select('
            WITH RECURSIVE cteUpd AS
            (
                SELECT id, name, parentId, qualification FROM members WHERE id="' . $lastId .  '"
                UNION ALL
                SELECT c.id, c.name, c.parentId, c.qualification FROM members c JOIN cteUpd
                ON c.id=cteUpd.parentId
            ),
            test3 AS (
                SELECT id, name, parentId, qualification, qualification + 1 AS newQualification FROM cteUpd
            )
            SELECT id, newQualification FROM test3
            ');

            foreach($list as $d) {
                $db->update('
                    UPDATE members SET qualification = ' . $d->newQualification . '
                    WHERE id = ' . $d->id . '
                ');
            }

            $tables = $db->select('
            WITH RECURSIVE cte AS
            (
                SELECT id, name, parentId, qualification FROM members WHERE id="' . $lastId .  '"
                UNION ALL
                SELECT c.id, c.name, c.parentId, c.qualification FROM members c JOIN cte
                ON c.id=cte.parentId
            ),
            test2 AS (
                SELECT id, parentId, qualification, CONCAT(name, "CONCATED") AS name FROM cte
            ),
            test AS (
                SELECT CONCAT("[id:", id, "]", "[q:",qualification, "]", "[parent:", parentId, "]", "[name:", name, "]") AS tbl FROM test2
            )
            SELECT * FROM test
            ');
        },
        5
    );
    } catch (Exception $e) {
        $error = true;
        $errorMessage = $e->getMessage();
    }

    if ($error) {
        return redirect()->route('db.add')->with('error', $errorMessage);
    } else {
        $data = ['name' => $name, 'parent' => $parent, 'tables' => $tables, 'status' => 'stat'];
        return redirect()->route('db.add')->with('data', $data);
    }

})->name('db.a.add');

Route::get('/db/y', function () {
    // $v = include_once('d.php');
    // $v = json_decode($v);
    // echo sizeof($v);
    insert_dummy_members();
});

Route::post('/db/l', function () {
    $db = DB::connection('autodrive_tip');
    $input = request()->input();
    ['name' => $name, 'parent' => $parent] = $input;
    $query = '';
    $error = false;
    $errorMessage = null;
    $psg = [];
    if ( $name === null && strlen($name) > 0) return redirect()->route('db.add');
    try {
    $db->transaction(
        function () use($db, $parent, $name, &$tables, &$psg) {

            $table = $db->table('members');
            $lastId = $table->insertGetId(
                [
                    'parentId' => $parent,
                    'name' => $name
                ]
            );

            $list = $db->select('
            WITH RECURSIVE cteUpd AS
            (
                SELECT id, name, parentId, qualification FROM members WHERE id="' . $lastId .  '"
                UNION ALL
                SELECT c.id, c.name, c.parentId, c.qualification FROM members c JOIN cteUpd
                ON c.id=cteUpd.parentId
            ),
            test3 AS (
                SELECT id, name, parentId, qualification, qualification + 1 AS newQualification FROM cteUpd
            )
            SELECT id, newQualification FROM test3
            ');

            foreach($list as $d) {
                $db->update('
                    UPDATE members SET qualification = ' . $d->newQualification . '
                    WHERE id = ' . $d->id . '
                ');
                $psg[] = $d;
            }
            $query = '
            WITH RECURSIVE ancestorPath AS (
                SELECT parent.id, parent.parentId, parent.name, 1 depth, CAST(0 AS VARCHAR(200)) AS p
                FROM members parent
                WHERE parent.parentId IS NULL

                UNION ALL

                SELECT Child.id, Child.parentId, Child.name, ANC.depth + 1, CONCAT(ANC.p, ",", Child.parentId)
                FROM members Child
                JOIN ancestorPath ANC
                ON ANC.id = Child.parentId
                WHERE Child.parentId IS NOT NULL
            )
            SELECT *
            FROM ancestorPath
            ';
            $tables_a = $db->select($query);
            $tables = $db->select('
            WITH RECURSIVE Ancestors AS
            (
                SELECT * FROM members WHERE id="' . $lastId .  '"
                UNION ALL
                SELECT member.* FROM members member JOIN Ancestors
                ON member.id=Ancestors.parentId OR
                member.id = NULL
            ),
            test AS (
                SELECT CONCAT("[id:", id, "]", "[q:",qualification, "]", "[parent:", parentId, "]", "[name:", name, "]") AS tbl FROM Ancestors
            )
            SELECT * FROM test
            ');
        },
        5
    );
    } catch (Exception $e) {
        $error = true;
        $errorMessage = $e->getMessage();
    }

    if ($error) {
        return redirect()->route('db.add')->with('error', $errorMessage);
    } else {

        $data = ['name' => $name, 'parent' => $parent, 'tables' => $tables, 'status' => 'stat', 'psg' => print_r($psg, true)];
        return redirect()->route('db.add')->with('data', $data);
    }

})->name('db.a.add');

Route::get('/db/descendants/{descendant}', function () {
    $id = request()->descendant;
    $ancestors = collect(get_ancestors($id));
    print_r($ancestors->where('level', 7));
})->name('db.descendants');

Route::get('/db/descendants', function () {

})->name('api.descendants');

Route::get('/db/ancestors', function () {

})->name('api.ancestors');