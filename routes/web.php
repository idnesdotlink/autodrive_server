<?php

use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;

function get_ancestors() {
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
}

function get_descendants() {

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
    $db = DB::connection('autodrive_tip');
    $db->statement('DROP TABLE IF EXISTS members');
    $db->statement('
        CREATE TABLE members (
            id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            parentId INT(6) NULL,
            name VARCHAR(30) NOT NULL,
            level TINYINT(1) NOT NULL DEFAULT 1,
            qualification TINYINT(1) NOT NULL DEFAULT 1
        )
    ');
    $db->statement('DROP TABLE IF EXISTS member_transactions');
    $db->statement('
        CREATE TABLE member_transactions (
            id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            memberId INT(6) NULL,
            createdAt DATETIME NULL,
            clientCreatedAt DATETIME NULL,
            level TINYINT(1) NOT NULL DEFAULT 1,
            ammount DOUBLE
        )
    ');
    $db->statement('DROP TABLE IF EXISTS levels');
    $db->statement('
        CREATE TABLE levels (
            id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(32) NOT NULL
        )
    ');
    } catch (Exception $error) {
        return redirect('/db')->with('error', $error->getMessage());
    }
    // echo 'created';
    return redirect('/db')->with('status', 'created!');
})->name('db.create');

Route::get('/db/delete', function () {
    $dbName = 'autodrive_1';
    $key = 'Tables_in_' . $dbName;
    $db = DB::connection('autodrive_tip');
    $tables = $db->select('show tables');
    $size = sizeof($tables);
    // $tableName = [];
    if($size > 0) {
        foreach($tables as $table) {
            // $tableName[] = $table->$key;
            $db->statement('DROP TABLE IF EXISTS ' . $table->$key);
        }
    }
    return redirect('/db')->with('status', 'deleted!');
})->name('db.delete');

Route::get('/db/seed', function () {
    $db = DB::connection('autodrive_tip');
    $table = $db->table('members');
    // $table->insert(['name' => 'name_1', 'parentId' => null]);
    $lst = [
        [1,null,2],
        [2,1,1],
        [3,1,1],
        [4,1,1],
        [5,2,1],
        [6,2,1],
        [7,3,1],
        [8,3,1],
        [9,4,1],
        [10,6,1],
        [11,6,1],
        [12,4,1]
    ];
    for($a = 0; $a <= (sizeof($lst) - 1); $a++) {
        $b = $lst[$a];
        $table->insert(['id' => $b[0], 'parentId' => $b[1], 'name' => 'name_' . $b[0], 'qualification' => $b[2]]);
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
                ON member.id = Ancestors.parentId
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
