<?php

namespace SilvertipSoftware\LaravelSupport\Http\Concerns;

trait EasierMiddleware {

    protected function before($fn, array $options = []) {
        $wrapper = function ($request, $next) use ($fn) {
            call_user_func($fn->bindTo($this, $this), $request);

            return $next($request);
        };

        return $this->middleware($wrapper, $options);
    }

    protected function after($fn, array $options = []) {
        $wrapper = function ($request, $next) use ($fn) {
            $response = $next($request);
            call_user_func($fn->bindTo($this, $this), $response);

            return $response;
        };

        return $this->middleware($wrapper, $options);
    }

    protected function callOnMethods($methodName, $controllerMethods) {
        return $this->before(function ($request) use ($methodName) {
            $this->{$methodName}($request);
        })->only($controllerMethods);
    }
}
