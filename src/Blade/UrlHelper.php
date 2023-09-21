<?php

declare(strict_types=1);

namespace SilvertipSoftware\LaravelSupport\Blade;

use Exception;
use Illuminate\Support\Arr;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use SilvertipSoftware\LaravelSupport\Libs\ArrUtils;
use SilvertipSoftware\LaravelSupport\Routing\RestRouter;

trait UrlHelper {

    public static function urlFor($options = null) {
        if (is_string($options)) {
            return $options != 'back'
                ? $options
                : throw new Exception('back not supported yet');
        } elseif ($options === null) {
            throw new Exception('null options for urlFor not handled yet');
        } elseif (is_object($options)) {
            return static::urlFor([$options]);
        } elseif (is_array($options)) {
            $components = $options;
            $options = ArrUtils::extractOptions($components);
            static::ensureOnlyPathOption($options);

            if (Arr::pull($options, 'only_path', false)) {
                return RestRouter::path($components, $options);
            }

            return RestRouter::url($components, $options);
        } else {
            throw new Exception('unsupported options to urlFor: ' . gettype($options));
        }
    }

    protected static function ensureOnlyPathOption(array &$options): void {
        if (!Arr::has($options, 'only_path')) {
            $options['only_path'] = static::generatePathsByDefault() && !Arr::has($options, 'host');
        }
    }

    protected static function generatePathsByDefault(): bool {
        return true;
    }
}
