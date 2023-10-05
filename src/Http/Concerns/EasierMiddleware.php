<?php

declare(strict_types=1);

namespace SilvertipSoftware\LaravelSupport\Http\Concerns;

use Closure;
use Illuminate\Routing\ControllerMiddlewareOptions;

trait EasierMiddleware {

    /**
     * @param array{only?: string[], except?: string} $options
     */
    protected function before(Closure $fn, array $options = []): ControllerMiddlewareOptions {
        $wrapper = function ($request, $next) use ($fn) {
            call_user_func($fn->bindTo($this, $this), $request);

            return $next($request);
        };

        return $this->middleware($wrapper, $options);
    }

    /**
     * @param array{only?: string[], except?: string} $options
     */
    protected function after(Closure $fn, array $options = []): ControllerMiddlewareOptions {
        $wrapper = function ($request, $next) use ($fn) {
            $response = $next($request);
            call_user_func($fn->bindTo($this, $this), $response);

            return $response;
        };

        return $this->middleware($wrapper, $options);
    }

    /**
     * @param string[] $controllerMethods
     */
    protected function callOnMethods(string $methodName, array $controllerMethods): ControllerMiddlewareOptions {
        return $this->before(function ($request) use ($methodName) {
            $this->{$methodName}($request);
        })->only($controllerMethods);
    }
}
