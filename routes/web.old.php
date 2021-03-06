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

Route::get('/db/delete', function () {
    try {
        drop_all_table();
    } catch(Exception $error) {
        return redirect()->route('admin.data')->with('error', $error->getMessage());
    }
    return redirect()->route('admin.data')->with('status', 'deleted!');
})->name('db.delete');

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

Route::get('/members/{member}/siblings', function () {
    $id = request()->member;
    $siblings = Members::get_siblings($id);
    return response()
        ->json($siblings);
})->name('members.siblings');

Route::get('/members/{member}/ancestors', function () {
    $id = request()->member;
    $ancestors = Members::get_ancestors($id);
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

Route::get('/members/{member}/add', function () {
    $id = request()->member;
    $member = Members::get_one($id);
    $ancestors = Members::get_ancestors($id)->map(
        function ($member) {
            $member = collect($member);
            return $member->only(['id', 'level']);
        }
    );
    print_r(json_encode([$ancestors], true));
    print_r('<a href="' . route('post.members.add', ['member' => $id]) . '">add</a>');
})->name('get.members.add');

Route::get('/members/{member}/addpost', function () {
    $id = request()->member;
    $newId = Members::add_downline($id);
    return redirect()->route('get.members.add', ['member' => $newId]);
})->name('post.members.add');

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

    Route::get('/data', function () {
        return view('main.db');
    })->name('admin.data');

    Route::get('/data/member.json', function () {
        try {
            $file = Members::get_dummy_members();
        } catch (Exception $e) {
            throw $e;
        }
        $json = $file;
        return response()
            ->json($json);
    })->name('admin.data.json');

    Route::get('/data/create', function () {
        try {
            tables();
        } catch (Exception $error) {
            return redirect()->route('admin.data')->with('error', $error->getMessage());
        }
        return redirect()->route('admin.data')->with('status', 'created!');
    })->name('admin.data.create');

    Route::get('/data/desc/', function () {
        print_r(Members::get_descendants(300));
    });
});
