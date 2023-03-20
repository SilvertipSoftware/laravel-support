<?php

namespace SilvertipSoftware\LaravelSupport\Http\Concerns;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

trait LoadsResource {

    protected $parentName = null;
    protected $parentModel = null;

    public function initializeLoadsResource() {
        if (isset($this->loadResource) && $this->loadResource == false) {
            return null;
        }

        $middleware = $this->before(function () {
            $this->loadResourceForRoute();
        });

        foreach (Arr::only(isset($this->loadResource) ? $this->loadResource : [], ['only', 'except']) as $key => $actions) {
            $middleware->{$key}($actions);
        }
    }

    protected function createCollectionQuery($name, $class, $hasParent) {
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

    protected function loadResourceForRoute($modelName = null, $single = null) {
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

    protected function loadResource($name, $class = null) {
        $class = $class ?: $this->getSubjectResourceClass();
        $this->{$name} = $class::findOrFail(request($this->routeParameterNameFor($name)));

        return $this->{$name};
    }

    protected function nameFromRouteParameter($param) {
        return Str::replaceLast('_id', '', $param);
    }

    protected function routeParameterNameFor($name) {
        return $name . '_id';
    }
}
