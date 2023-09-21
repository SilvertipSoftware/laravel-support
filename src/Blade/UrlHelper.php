<?php

declare(strict_types=1);

namespace SilvertipSoftware\LaravelSupport\Blade;

use Exception;
use Illuminate\Support\Arr;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

trait UrlHelper {

    public static function urlFor($options = null) {
        if (is_string($options)) {
            return $options;
        } else {
            throw new Exception('non-string urls (like "back") not supported yet');
        }
    }
}
