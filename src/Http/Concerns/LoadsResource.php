<?php

namespace SilvertipSoftware\LaravelSupport\Http\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

trait LoadsResource {

    /** @var array<string,array<string>> */
    protected array|bool $loadResource = [];
    protected ?string $parentName = null;
    protected ?Model $parentModel = null;

    public function initializeLoadsResource(): void {
        if ($this->loadResource === false) {
            return;
        }

        $middleware = $this->before(function () {
            $this->loadResourceForRoute();
        });

        $modifiers = Arr::only($this->loadResource, ['only', 'except']);
        foreach ($modifiers as $key => $actions) {
            $middleware->{$key}($actions);
        }
    }

    protected function createCollectionQuery(string $name, string $class, bool $hasParent): void {
        $query = null;

        if ($hasParent) {
            $scopeMethod = 'scopeFor' . Str::studly($this->parentName);

            if (method_exists($class, $scopeMethod)) {
                $query = $class::{'for' . Str::studly($this->parentName)}($this->parentModel);
            }
        } else {
            $query = $class::query();
        }

        $this->{$name . '_query'} = $query;
    }

    protected function loadResourceForRoute(?string $modelName = null, bool $single = false): void {
        $currentRoute = Route::getCurrentRoute();
        $action = $currentRoute->getActionMethod();
        $modelName = $modelName ?: $this->getSubjectResourceTag();
        $modelClass = $this->getSubjectResourceClass();

        $single = $single ?: !in_array($action, ['index', 'create', 'store']);

        if ($single) {
            $this->loadResource($modelName, $modelClass);
        } else {
            $parameterNames = $currentRoute->parameterNames();
            $lastParameter = end($parameterNames);
            if (!empty($lastParameter)) {
                $this->parentName = $this->nameFromRouteParameter($lastParameter);
                $parentClassName = $this->getActualClassNameFromTag($this->parentName);
                if (!str_contains($parentClassName, '\\')) {
                    $parentClassName = $this->modelRootNamespace . '\\' . Str::studly($parentClassName);
                }

                $this->parentModel = $this->loadResource($this->parentName, $parentClassName);
            }

            $this->createCollectionQuery($modelName, $modelClass, !!$this->parentModel);

            if ($action != 'index') {
                $model = new $modelClass;
                $this->{$modelName} = $model;

                if (!!$this->parentModel) {
                    if (method_exists($model, $this->parentName)) {
                        $model->{$this->parentName}()->associate($this->parentModel);
                    }
                }
            }
        }
    }

    protected function loadResource(string $name, ?string $class = null): Model {
        $class = $class ?: $this->getSubjectResourceClass();
        $this->{$name} = $class::findOrFail(request($this->routeParameterNameFor($name)));

        return $this->{$name};
    }

    protected function nameFromRouteParameter(string $param): string {
        return Str::replaceLast('_id', '', $param);
    }

    protected function routeParameterNameFor(string $name): string {
        return $name . '_id';
    }
}
