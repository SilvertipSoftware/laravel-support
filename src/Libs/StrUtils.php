<?php

namespace SilvertipSoftware\LaravelSupport\Libs;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Str;

class StrUtils {

    public static function humanize($str) {
        return Str::headline($str);
    }

    public static function translate($possibleKeys, $fallback, $opts = []) {
        $count = Arr::get($opts, 'count', 1);
        $locale = Arr::get($opts, 'locale', null);

        foreach ($possibleKeys as $key) {
            if (Lang::has($key, $locale)) {
                if ($count) {
                    return Lang::choice($key, $count, $opts, $locale);
                }

                return Lang::get($key, $opts, $locale);
            }
        }

        return $fallback;
    }
}
