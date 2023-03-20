<?php

namespace SilvertipSoftware\LaravelSupport\Http\Middleware;

use \Closure;
use Illuminate\Http\Request;

class SealInFreshness {

    public function handle($request, Closure $next) {
        $response = $next($request);

        if ($response && $this->isPotentiallyCacheable($response) && Request::hasMacro('addFreshnessHeaders')) {
            $request->addFreshnessHeaders($response);
        }

        return $response;
    }

    /**
     * Response::isCacheable() looks at cache-control headers which we want to set here, so need our own check
     */
    private function isPotentiallyCacheable($response) {
        if (!in_array($response->getStatusCode(), [200, 203, 300, 301, 302, 304, 404, 410])) {
            return false;
        }

        return true;
    }
}
