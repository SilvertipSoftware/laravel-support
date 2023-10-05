<?php

declare(strict_types=1);

namespace SilvertipSoftware\LaravelSupport\Http\Mixins;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

class RequestAcceptsHelpers {

    public static function register(): void {
        Request::macro('wantsJavascript', function () {
            // @phpstan-ignore-next-line
            return RequestAcceptsHelpers::requestAcceptsTypes($this, ['/javascript', '-javascript']);
        });

        Request::macro('wantsTurboStream', function () {
            // @phpstan-ignore-next-line
            return RequestAcceptsHelpers::requestAcceptsTypes($this, ['/vnd.turbo-stream.html']);
        });
    }

    /**
     * @param string|string[] $types
     */
    public static function requestAcceptsTypes(Request $request, string|array $types): bool {
        $acceptable = $request->getAcceptableContentTypes();

        return isset($acceptable[0]) && Str::contains($acceptable[0], (array)$types);
    }
}
