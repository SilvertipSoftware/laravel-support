<?php

declare(strict_types=1);

namespace SilvertipSoftware\LaravelSupport\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class DetectDesiredResponseFormat {

    public function handle(Request $request, Closure $next): mixed {
        $request->responseFormat = $this->detectResponseFormat($request);

        return $next($request);
    }

    protected function detectResponseFormat(Request $request): string {
        $format = 'html';

        if ($request->expectsJson()) {
            $format = 'json';
        // @phpstan-ignore-next-line
        } elseif ($request->wantsJavascript()) {
            $format = 'js';
        // @phpstan-ignore-next-line
        } elseif ($request->wantsTurboStream()) {
            $format = 'stream';
        } else {
            $path = $request->decodedPath();
            if (preg_match('/\.[a-z]+$/', $path, $matches)) {
                $format = substr($matches[0], 1);
            }
        }

        return $format;
    }
}
