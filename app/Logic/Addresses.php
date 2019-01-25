<?php
declare(strict_types=1);

namespace App\Logic;
use Illuminate\Support\Facades\Storage;
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
        return self::from_file('provinces');
    }

    /**
     * Undocumented function
     *
     * @param string $id
     * @return Collection
     */
    public static function get_regency_by_provinceId(string $id): Collection {
        return self::from_file('regencies', $id);
    }

    /**
     * Undocumented function
     *
     * @param string $id
     * @return Collection
     */
    public static function get_district_by_regencyId(string $id): Collection {
        return self::from_file('districts', $id);
    }

    /**
     * Undocumented function
     *
     * @param string $id
     * @return Collection
     */
    public static function get_village_by_districtId(string $id): Collection {
        return self::from_file('villages', $id);
    }

}
