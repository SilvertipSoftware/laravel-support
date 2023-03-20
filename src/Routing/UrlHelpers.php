<?php

namespace SilvertipSoftware\LaravelSupport\Routing;

use Illuminate\Support\Facades\URL;

class UrlHelpers {

    public static function register() {
        URL::macro('url', function (...$models) {
            return RestRouter::url(...$models);
        });

        URL::macro('path', function (...$models) {
            return RestRouter::path(...$models);
        });
    }
}
