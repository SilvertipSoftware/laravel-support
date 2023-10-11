<?php

namespace SilvertipSoftware\LaravelSupport\Http\Concerns;

use Illuminate\Contracts\View\ViewObject;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request as RequestObject;
use Illuminate\Routing\Route as RouteObject;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

trait AutoResponds {
    use AutoResponds\WithHtml,
        AutoResponds\WithJavascript,
        AutoResponds\WithJson,
        AutoResponds\WithStream;

    /**
     * @param array<string,mixed> $parameters
     */
    public function callAction(mixed $method, mixed $parameters): mixed {
        $request = request();
        $request->controller = View::share('controller', $this);

        $response = call_user_func_array([$this, $method], array_values($parameters));

        if ($response instanceof RedirectResponse) {
            $response = $this->mapRedirectResponse($request, $response);
        }

        if ($response === null) {
            $response = $this->createResponse($request);
        }

        return $response;
    }

    protected function controllerRootNamespace(): string {
        return 'App\Http\Controllers';
    }

    protected function createResponse(RequestObject $request): Response {
        // @phpstan-ignore-next-line
        if (Request::hasMacro('isFresh') && $request->isFresh()) {
            return response(null)->setNotModified();
        }

        $response = null;

        $methodName = 'create' . Str::studly($this->desiredResponseFormat()) . 'Response';
        if (method_exists($this, $methodName)) {
            $response = $this->{$methodName}();
        }

        return $response;
    }

    /**
     * @return array<string,mixed>
     */
    protected function dataForView(): array {
        return get_object_vars($this);
    }

    protected function desiredResponseFormat(): string {
        return request()->responseFormat ?: 'html';
    }

    protected function mapRedirectResponse(RequestObject $request, RedirectResponse $response): mixed {
        $methodName = 'mapRedirectFor' . Str::studly($this->desiredResponseFormat());

        if (method_exists($this, $methodName)) {
            $response = $this->{$methodName}($response);
        }

        return $response;
    }

    protected function viewNamePrefix(): string {
        return '';
    }

    protected function viewNameForRoute(?string $format = null, ?RouteObject $route = null): string {
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
            : [$actionMethod, $format];

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
