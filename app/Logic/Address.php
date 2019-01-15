<?php
declare(strict_types=1);

namespace App\Logic;
use Illuminate\Support\Facades\{DB, Storage, Artisan};
use \Closure;

class Address {

    public static function get_data($type) {
        $data = Storage::disk('local')->get('seed/' . $type . '.json');
        $data = json_decode($data);
        $data = collect($data);
        return $data;
    }

    public static function get_all_provinces() {
        return self::get_data('Provinces');
    }

    public static function get_regency_by_provinceId($provinceId) {
        $data = self::get_data('Regencies');
        $filter = function ($data) use ($provinceId) {
            return $data[0] === $provinceId;
        };
        $filtered = $data->filter($filter);
        $transform = function ($data) {
            return [$data[0] . $data[1], $data[2]];
        };
        return $filtered->transform($transform);
    }

    public static function get_district_by_regencyId($regencyId) {
        $data = self::get_data('Districts');
        $filter = function ($data) use ($regencyId) {
            return $data[0] . $data[1] === $regencyId;
        };
        $filtered = $data->filter($filter);
        $transform = function ($data) {
            return [$data[0] . $data[1] . $data[2], $data[3]];
        };
        return $filtered->transform($transform);
    }

    public static function get_district() {
        $data = self::get_data('Regencies');
    }

    public static function get_village_by_districtId($districtId) {
        $data = self::get_data('Villages');
        $filter = function ($data) use ($districtId) {
            return $data[0] . $data[1]. $data[2] === $districtId;
        };
        $filtered = $data->filter($filter);
        $transform = function ($data) {
            return [$data[0] . $data[1] . $data[2] . $data[3], $data[4]];
        };
        return $filtered->transform($transform);
    }

}
