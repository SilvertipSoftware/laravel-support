<?php

namespace SilvertipSoftware\LaravelSupport\Http\Concerns;

use Illuminate\Database\Eloquent\Model as EloquentModel;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use SilvertipSoftware\LaravelSupport\Eloquent\ModelContract;

trait LoadsResource {

    /** @var array<string,array<string>> */
    protected array|bool $loadResource = [];
    protected ?string $parentName = null;
    protected EloquentModel|ModelContract|null $parentModel = null;

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

    protected function authorizeIfRequired(
        string $action,
        string|EloquentModel|ModelContract $modelClass,
        EloquentModel|ModelContract|null $parentModel = null
    ): void {
        if (!in_array(AuthorizesRequests::class, class_uses_recursive($this))) {
            return;
        }

        // @phpstan-ignore-next-line
        $ability = $this->normalizeGuessedAbilityName($action);

        // @phpstan-ignore-next-line
        $this->authorize($ability, $parentModel ? [$modelClass, $parentModel] : $modelClass);
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

    protected function defaultResource(string $name, ?string $class = null): EloquentModel|ModelContract {
        throw (new ModelNotFoundException)->setModel($class);
    }

    protected function loadResourceForRoute(?string $modelName = null, bool $single = false): void {
        $currentRoute = Route::getCurrentRoute();
        $action = $currentRoute->getActionMethod();
        $modelName = $modelName ?: $this->getSubjectResourceTag();
        $modelClass = $this->getSubjectResourceClass();

        $single = $single ?: !in_array($action, ['index', 'create', 'store']);

        if ($single) {
            $this->loadResource($modelName, $modelClass);
            $this->authorizeIfRequired($action, $this->{$modelName}, null);
        } else {
            $parameterNames = $currentRoute->parameterNames();
            $lastParameter = end($parameterNames);
            if (!empty($lastParameter)) {
                $this->parentName = $this->nameFromRouteParameter($lastParameter);
                $parentClassName = $this->getActualClassNameFromTag($this->parentName);
                if (!str_contains($parentClassName, '\\')) {
                    $parentClassName = $this->modelRootNamespace . '\\' . Str::studly($parentClassName);
                }

                $this->parentModel = $this->loadResource($this->parentName, $parentClassName, false);
            }

            $this->authorizeIfRequired($action, $modelClass, $this->parentModel);
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

    protected function loadResource(
        string $name,
        ?string $class = null,
        bool $isSubjectModel = true
    ): EloquentModel|ModelContract {
        $class = $class ?: $this->getSubjectResourceClass();
        $id = request($this->routeParameterNameFor($name));

        $this->{$name} = ($isSubjectModel && $id === null)
            ? $this->defaultResource($name, $class)
            : $class::findOrFail($id);

        return $this->{$name};
    }

    protected function nameFromRouteParameter(string $param): string {
        return Str::replaceLast('_id', '', $param);
    }

    protected function routeParameterNameFor(string $name): string {
        return $name . '_id';
    }
}
