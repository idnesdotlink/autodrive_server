<?php

use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;

use App\Events\CreateMember;
use App\Repositories\Members;
use App\Repositories\Promos;
use App\Repositories\Levels;

function set_dummy_name($item) {
    // $item['parentId'] = $item['parentId'] ? $item['parentId'] : 0;
    $item['name'] = 'name_' . $item['id'];
    return $item;
}

function get_dummy_members() {
    $members_dummy_data = json_decode(include_once('members.php'), true);
    $collection = collect($members_dummy_data);
    $collection->transform(function ($item) {
        return set_dummy_name($item);
    });
    return $collection;
}

function batch_insert_dummy_members() {
    $db = DB::connection('autodrive_tip');
    $dummy = get_dummy_members();
    $db->transaction(
        function () use($dummy, $db) {
            $members = $db->table('members');
            $chunk = $dummy->chunk(500);
            foreach($chunk as $chunked) {
                $members->insert($chunked->all());
            }
        }
    );
}

function insert_dummy_members() {
    $db = DB::connection('autodrive_tip');
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

function batch_insert_dummy_levels($db) {
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
                    qualified MEDIUMINT UNSIGNED NOT NULL,
                    unqualified MEDIUMINT UNSIGNED NOT NULL,
                    treshold TINYINT unsigned NOT NULL DEFAULT 0,
                    updated DATETIME NOT NULL DEFAULT NOW()
                )
            ');
            Members::drop_table($db);
            Members::create_table($db);
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
            Promos::drop_table($db);
            Promos::create_table($db);
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
            /* $db->statement('DROP TABLE IF EXISTS config');
            $db->statement('
                CREATE TABLE config (
                    id SMALLINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    key VARCHAR(32) NOT NULL,
                    value TEXT
                )
            '); */
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

$query2 = '
WITH RECURSIVE ancestors AS
(
    SELECT * FROM members
    WHERE ' . $key . '="' . $search .  '"
    UNION
    SELECT member.*
    FROM members member,
    ancestors ancestor
    WHERE member.id = ancestor.parentId
),
get AS (
    SELECT * FROM ancestors
)
SELECT * FROM get
';
    $ancestors = $db->select($query);
    // print_r($ancestors->toSql());
    // exit();
    return collect($ancestors)->splice(1)->reverse();
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
        // batch_insert_dummy_members();
        $data = Members::get_dummy_members();
        Members::batch_insert($data);
    } catch (Exception $error) {
        return redirect('/db')->with('error', $error->getMessage());
    }
    return redirect('/db')->with('status', 'seeded!');
})->name('db.seed');

Route::get('/db/seed2', function () {
    try {
        insert_dummy_members();
    } catch (Exception $error) {
        return redirect('/db')->with('error', $error->getMessage());
    }
    return redirect('/db')->with('status', 'seeded!');
})->name('db.seed2');

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
    batch_insert_dummy_members();
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

function x($k, $v, &$db) {
    $query = 'WITH RECURSIVE ancestor AS
    (
        SELECT * FROM members WHERE ' . $k . '="' . $v .  '"
        UNION ALL
        SELECT member.*
        FROM members member
        JOIN ancestor
        ON member.id=ancestor.parentId
    ),
    get AS (
        SELECT * FROM ancestor
    )
    SELECT * FROM get';
    // $db->select($query);
    $ancestors = $db->select($query);
    return collect($ancestors)->splice(1)->reverse();
}

function y($k, $v, &$db) {
    $query = '
        UPDATE members
        SET qualification = ' . ($k+1) . '
        WHERE id = ' . $v . '
    ';
    // $db->select($query);
    return $db->update($query);
}

Route::get('/db/ll', function () {
    try {
        $db = DB::connection('autodrive_tip');
        $k = 'id';
        $v = 900;
        $db->transaction(
            function () use(&$db, &$rv, $k, $v) {
                $x = x($k, $v, $db)->filter(function ($value) { return $value->level === 6; })->first();
                y($x->qualification, $x->id, $db);
                $rv = x($k, $v, $db)->filter(function ($value) { return $value->level === 6; })->first();
            }
        );
        /* $select = $db->select($query); */
        print_r($rv);
    } catch(Exception $e) {
        throw $e;
    }
})->name('api.ll');

Route::get('/installer', function () {
    return 'hello';
});

Route::get('/members/{member}/siblings', function () {
    $id = request()->member;
    $siblings = Members::get_siblings($id);
    return response()
        ->json($siblings);
})->name('members.siblings');

Route::get('/members/{member}/ancestors', function () {
    $id = request()->member;
    $ancestors = Members::get_ancestors($id);
    $ancestors = $ancestors->toArray();
    return response()->json($ancestors);
})->name('members.ancestors');

Route::get('/members/{member}/children', function () {
    $id = request()->member;
    $children = Members::get_children($id);
    $children = $children->toArray();
    return response()->json($children);
})->name('members.children');

Route::get('/members/{member}', function () {
    $id = request()->member;
    $member = Members::get_one($id);
    return response()->json($member);
})->name('members.detail');

Route::get('/members/search', function () {
    $id = request()->member;
    $per_page = request()->query('per_page');
    $page = request()->query('page');
    $level = request()->query('level');
    $outletId  =request()->query('outletId');
    $members = Members::get_all($page, $per_page, $level);
    return response()->json($members);
})->name('members.search');

Route::get('/members', function () {
    $id = request()->member;
    $per_page = request()->query('per_page');
    $page = request()->query('page');
    $level = request()->query('level');
    $outletId  =request()->query('outletId');
    $members = Members::get_all($page, $per_page, $level);
    return response()->json($members);
})->name('members');

Route::prefix('admin')->middleware([])->group(function () {
    Route::get('/data/member.json', function () {
        try {
            $file = Members::get_dummy_members();
        } catch (Exception $e) {
            throw $e;
        }
        $json = $file;
        return response()
            ->json($json);
    });
});
