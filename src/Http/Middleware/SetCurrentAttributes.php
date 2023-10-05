<?php

declare(strict_types=1);

namespace SilvertipSoftware\LaravelSupport\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SetCurrentAttributes {

    public function handle(Request $request, Closure $next): mixed {
        $current = app('current');
        $current->ip_address = $request->ip();
        $current->user_agent = $request->userAgent();

        return $next($request);
    }
}
