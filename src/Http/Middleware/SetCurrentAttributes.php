<?php

namespace SilvertipSoftware\LaravelSupport\Http\Middleware;

use Closure;

class SetCurrentAttributes {

    public function handle($request, Closure $next) {
        $current = app('current');
        $current->ip_address = $request->ip();
        $current->user_agent = $request->userAgent();

        return $next($request);
    }
}
