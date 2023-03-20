<?php

namespace SilvertipSoftware\LaravelSupport\Http\Concerns;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;

trait AutoResponds {
    use AutoResponds\WithHtml,
        AutoResponds\WithJavascript,
        AutoResponds\WithJson,
        AutoResponds\WithStream;

    protected static $controllerRootNamespace = 'App\\Http\\Controllers';

    public function callAction($method, $parameters) {
        $request = request();
        $request->controller = View::share('controller', $this);

        $response = call_user_func_array([$this, $method], $parameters);

        if ($response instanceof RedirectResponse) {
            $response = $this->mapRedirectResponse($request, $response);
        }

        if ($response === null) {
            $response = $this->createResponse($request);
        }

        return $response;
    }

    protected function controllerRootNamespace() {
        return 'App\\Http\\Controllers';
    }

    protected function createResponse($request) {
        if (Request::hasMacro('isFresh') && $request->isFresh()) {
            return response(null)->setNotModified();
        }

        $response = null;

        $methodName = 'create' . ucfirst($this->desiredResponseFormat()) . 'Response';
        if (method_exists($this, $methodName)) {
            $response = $this->{$methodName}();
        }

        return $response;
    }

    protected function desiredResponseFormat() {
        return request()->responseFormat ?: 'html';
    }

    protected function mapRedirectResponse($request, $response) {
        $methodName = 'mapRedirectFor' . ucfirst($this->desiredResponseFormat());

        if (method_exists($this, $methodName)) {
            $response = $this->{$methodName}($response);
        }

        return $response;
    }

    protected function viewNamePrefix() {
        return '';
    }

    protected function viewNameForRoute($format = null, $route = null) {
        if ($route == null) {
            $route = Route::getCurrentRoute();
        }

        if ($format == null) {
            $format = $this->desiredResponseFormat();
        }

        $actionName = $route->getActionName();
        list($controllerClass, $actionMethod) = explode('@', $actionName, 2);

        $controllerName = str_replace($this->controllerRootNamespace() . '\\', '', $controllerClass);
        $leafParts = $format == 'html'
            ? [$actionMethod]
            : [$format, $actionMethod];

        $segmentNames = array_map(function ($part) {
            $fragment = str_replace('Controller', '', $part);

            return strtolower(Str::snake($fragment));
        }, explode('\\', $controllerName));

        $leafSegments = array_map(function ($leaf) {
            return strtolower(Str::snake($leaf));
        }, $leafParts);

        return $this->viewNamePrefix() . implode('.', array_merge($segmentNames, $leafSegments));
    }
}
