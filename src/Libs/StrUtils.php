<?php

declare(strict_types=1);

namespace SilvertipSoftware\LaravelSupport\Libs;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Str;

class StrUtils {

    public static function humanize(string $str): string {
        return Str::headline($str);
    }

    /**
     * @param string[] $possibleKeys
     * @param array<string,mixed> $opts
     */
    public static function translate(array $possibleKeys, string $fallback, array $opts = []): string {
        $count = Arr::get($opts, 'count', 1);
        $locale = Arr::get($opts, 'locale', null);

        foreach ($possibleKeys as $key) {
            if (Lang::has($key, $locale) && !is_array(Lang::get($key, locale: $locale))) {
                if ($count !== false) {
                    return Lang::choice($key, $count, $opts, $locale);
                }

                return Lang::get($key, $opts, $locale);
            }
        }

        return $fallback;
    }
}
