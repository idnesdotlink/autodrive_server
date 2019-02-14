<?php
declare(strict_types=1);

namespace App\Logic;
use Illuminate\Support\Facades\{DB, Storage};
use Illuminate\Support\Collection;

class Addresses {

    /**
     * Get data collection from json data
     *
     * @param string $type
     * @return Collection
     */
    public static function data_json(string $type): Collection {
        $data = Storage::disk('local')->get('seed/' . $type . '.json');
        $data = json_decode($data);
        $data = collect($data);
        return $data;
    }

    /**
     * Undocumented function
     *
     * @param string $type
     * @param string $id
     * @return Collection
     */
    public static function from_file(string $type, string $id = null): Collection {
        $data = self::data_json($type);

        if ($id === null) return $data;

        $filter = function ($data) use ($id) {
            $data = collect($data);
            return $data->take($data->count()-2)->implode('') === $id;
        };
        $reducer = function ($collection, $data) {
            $data = collect($data);
            $last = $data->pop();
            return $collection->push([$data->implode(''), $last]);
        };
        return $data->filter($filter)->reduce($reducer, collect([]));
    }

    /**
     * Undocumented function
     *
     * @return Collection
     */
    public static function get_all_provinces():Collection {
        $db = DB::connection('autodrive_tip');

        $sql = '
            SELECT * from provinces;
        ';

        $provinces = $db->select($sql);

        return collect($provinces)->map(
            function ($area) {
                return [$area->id, $area->name];
            }
        );
    }

    /**
     * Undocumented function
     *
     * @param string $id
     * @return Collection
     */
    public static function get_regency_by_provinceId(string $id): Collection {
        $db = DB::connection('autodrive_tip');

        $sql = '
            SELECT * from regencies where provinceId = ' . $id . ';
        ';

        $areas = $db->select($sql);

        return collect($areas)->map(
            function ($area) {
                return [$area->id, $area->name];
            }
        );
    }

    /**
     * Undocumented function
     *
     * @param string $id
     * @return Collection
     */
    public static function get_district_by_regencyId(string $id): Collection {
        $db = DB::connection('autodrive_tip');

        $sql = '
            SELECT * from districts where regencyId = ' . $id . ';
        ';

        $areas = $db->select($sql);

        return collect($areas)->map(
            function ($area) {
                return [$area->id, $area->name];
            }
        );
    }

    /**
     * Undocumented function
     *
     * @param string $id
     * @return Collection
     */
    public static function get_village_by_districtId(string $id): Collection {
        $db = DB::connection('autodrive_tip');

        $sql = '
            SELECT * from villages where districtId = ' . $id . ';
        ';

        $areas = $db->select($sql);

        return collect($areas)->map(
            function ($area) {
                return [$area->id, $area->name];
            }
        );
    }

}
