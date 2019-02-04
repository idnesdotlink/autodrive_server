<?php
declare(strict_types=1);

namespace App\Logic;

use Ramsey\Uuid\Uuid;
use Faker\Factory;

class Mock {

    public static function members() {
        $faker = Factory::create();
        $mem = [];
        for($i = 1; $i <= 50; $i++) {
            $mem[] = [
                $faker->name(),
                Uuid::uuid4(),
                $faker->randomElement([1,2,3,4,5,6,7,8])
            ];
        }
        $size = sizeof($mem);
        return response()->json(
            [
                'list' => $mem,
                'meta' => [
                    'size' => $size,
                    'column' => ['name', 'id', 'level']
                ]
            ]
        );
    }
}
