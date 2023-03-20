<?php

namespace SilvertipSoftware\LaravelSupport\Routing;

use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\URL;

class RedirectHelpers {

    public static function register() {
        Redirect::macro('url', function (...$models) {
            return $this->to(URL::url(...$models));
        });

        Redirect::macro('path', function (...$models) {
            return $this->to(URL::path(...$models));
        });
    }
}
