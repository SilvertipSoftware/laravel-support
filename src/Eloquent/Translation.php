<?php

namespace SilvertipSoftware\LaravelSupport\Eloquent;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Str;

trait Translation {

    public static function humanAttributeName($attr, $opts = []) {
        $scope = static::i18nScope();
        $attributesScope = $scope
            . (Str::endsWith($scope, '::') ? '' : '.')
            . 'attributes';
        $locale = Arr::get($opts, 'locale', null);
        $count = Arr::get($opts, 'count');

        $possibleKeys = [
            $attributesScope . '.' . static::modelName()->i18n_key . '.' . $attr,
            'attributes.' . $attr
        ];

        foreach ($possibleKeys as $key) {
            if (Lang::has($key, $locale)) {
                if ($count) {
                    return Lang::choice($key, $count, $opts, $locale);
                }

                return Lang::get($key, $opts, $locale);
            }
        }

        if (Arr::has($opts, 'default')) {
            return Arr::get($opts, 'default');
        }

        return str_replace('_', ' ', ucfirst($attr));
    }

    public static function i18nScope() {
        return 'eloquent';
    }
}
