<?php

namespace SilvertipSoftware\LaravelSupport\Routing;

use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\URL;

class RedirectHelpers {

    public static function register(): void {
        Redirect::macro('url', function (...$models) {
            // @phpstan-ignore-next-line
            return $this->to(URL::url(...$models));
        });

        Redirect::macro('path', function (...$models) {
            // @phpstan-ignore-next-line
            return $this->to(URL::path(...$models));
        });
    }
}
