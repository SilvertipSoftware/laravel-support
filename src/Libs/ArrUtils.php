<?php

declare(strict_types=1);

namespace SilvertipSoftware\LaravelSupport\Libs;

use Illuminate\Support\Arr;

class ArrUtils {

    public static function extractOptions(array &$arr): array {
        $opts = [];

        foreach ($arr as $key => $value) {
            if (is_string($key)) {
                $opts[$key] = $value;
            }
        }

        Arr::forget($arr, array_keys($opts));

        return $opts;
    }
}
