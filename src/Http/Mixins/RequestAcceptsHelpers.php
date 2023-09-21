<?php

namespace SilvertipSoftware\LaravelSupport\Http\Mixins;

use Illuminate\Support\Facades\Request;
use Illuminate\Support\Str;

class RequestAcceptsHelpers {

    public static function register() {
        Request::macro('wantsJavascript', function () {
            // @phpstan-ignore-next-line
            return RequestAcceptsHelpers::requestAcceptsTypes($this, ['/javascript', '-javascript']);
        });

        Request::macro('wantsTurboStream', function () {
            // @phpstan-ignore-next-line
            return RequestAcceptsHelpers::requestAcceptsTypes($this, ['/vnd.turbo-stream.html']);
        });
    }

    public static function requestAcceptsTypes($request, $types) {
        $acceptable = $request->getAcceptableContentTypes();

        return isset($acceptable[0]) && Str::contains($acceptable[0], (array)$types);
    }
}
