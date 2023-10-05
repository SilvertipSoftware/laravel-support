<?php

declare(strict_types=1);

namespace SilvertipSoftware\LaravelSupport\Eloquent;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Str;
use SilvertipSoftware\LaravelSupport\Libs\StrUtils;

trait Translation {

    /**
     * @param array<string, mixed> $opts
     */
    public static function humanAttributeName(string $attr, array $opts = []): string {
        $parts = explode('.', $attr);
        $attribute = array_pop($parts);
        $namespace = !empty($parts) ? implode('/', $parts) : null;

        $scope = static::i18nScope();
        $attributesScope = $scope
            . (Str::endsWith($scope, '::') ? '' : '.')
            . 'attributes';

        if ($namespace) {
            $possibleKeys = [
                $attributesScope . '.' . static::modelName()->i18n_key . '/' . $namespace . '.' . $attribute,
                $attributesScope . '.' . $namespace . '.' . $attribute
            ];
        } else {
            $possibleKeys = [
                $attributesScope . '.' . static::modelName()->i18n_key . '.' . $attribute
            ];
        }
        $possibleKeys[] = 'attributes.' . $attribute;

        return StrUtils::translate(
            $possibleKeys,
            Arr::get($opts, 'default', StrUtils::humanize($attribute)),
            $opts
        );
    }

    public static function i18nScope(): string {
        return 'eloquent';
    }
}
