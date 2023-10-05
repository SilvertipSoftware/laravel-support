<?php

declare(strict_types=1);

namespace SilvertipSoftware\LaravelSupport\Http\Middleware;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use \Closure;

class SealInFreshness {

    public function handle(Request $request, Closure $next): mixed {
        $response = $next($request);

        if ($response && $this->isPotentiallyCacheable($response) && Request::hasMacro('addFreshnessHeaders')) {
            // @phpstan-ignore-next-line
            $request->addFreshnessHeaders($response);
        }

        return $response;
    }

    /**
     * Response::isCacheable() looks at cache-control headers which we want to set here, so need our own check
     */
    private function isPotentiallyCacheable(Response $response): bool {
        if (!in_array($response->getStatusCode(), [200, 203, 300, 301, 302, 304, 404, 410])) {
            return false;
        }

        return true;
    }
}
