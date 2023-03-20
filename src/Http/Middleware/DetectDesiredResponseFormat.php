<?php

namespace SilvertipSoftware\LaravelSupport\Http\Middleware;

class DetectDesiredResponseFormat {

    public function handle($request, $next) {
        $request->responseFormat = $this->detectResponseFormat($request);

        return $next($request);
    }

    protected function detectResponseFormat($request) {
        $format = 'html';

        if ($request->expectsJson()) {
            $format = 'json';
        } elseif ($request->wantsJavascript()) {
            $format = 'js';
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
