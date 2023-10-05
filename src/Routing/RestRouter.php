<?php

namespace SilvertipSoftware\LaravelSupport\Routing;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

class RestRouter {

    public static bool $shallowResources = true;

    public static function url(mixed ...$models): string {
        list($models, $options) = static::processArgs(...$models);

        return static::polymorphicRoute($models, $options);
    }

    public static function path(mixed ...$models): string {
        list($models, $options) = static::processArgs(...$models);

        return static::polymorphicRoute($models, $options, false);
    }

    /**
     * @param array<Model|string> $models
     * @param array<string,mixed> $options
     */
    public static function polymorphicRoute(array $models, array $options = [], bool $absolute = true): string {
        $models = Arr::flatten([$models]);

        $routePrefixes = [];
        $routeParams = [];
        $isCollection = false;
        $options['shallow'] = Arr::get($options, 'shallow', static::$shallowResources);

        foreach ($models as $index => $model) {
            if ($index == count($models) - 1) {
                static::processLastFragment($model, $routePrefixes, $routeParams, $isCollection, $options);
            } else {
                static::processIntermediateFragment($model, $routePrefixes, $routeParams, $options);
            }
        }

        $routePrefix = implode('.', $routePrefixes);
        $routeName = static::findRoute($routePrefix, $isCollection, $options);

        if (!$routeName) {
            throw new Exception(
                'Cannot find route with prefix "'. $routePrefix .'." using the '
                    . ($isCollection ? 'collection' : 'single')
                    . ' action variants.'
            );
        }

        $format = Arr::pull($options, 'format');
        $options = Arr::except($options, ['action', 'shallow']);
        $url = route($routeName, array_merge($routeParams, $options), $absolute);
        if ($format) {
            $url .= '.' . $format;
        }

        return $url;
    }

    /**
     * @return array<mixed>
     */
    protected static function processArgs(mixed ...$models): array {
        $options = [];
        $last_arg = end($models);
        if (count($models) <= 1) {
            return [$models, $options];
        }
        if (is_array($last_arg)) {
            $options = array_pop($models);
        }
        if (is_string($last_arg) && in_array($last_arg, ['edit', 'create'])) {
            $options = ['action' => array_pop($models)];
        }
        $models = Arr::flatten([$models]);

        return [$models, $options];
    }

    /**
     * @param array<mixed> $prefixes
     * @param array<string,string|int> $params
     * @param array<string,mixed> $options
     */
    protected static function processLastFragment(
        Model|string $model,
        array &$prefixes,
        array &$params,
        bool &$isCollection,
        array $options
    ): void {
        if ($model instanceof Model) {
            $class = get_class($model);
            $isCollection = !$model->exists;
            if (!$isCollection) {
                if ($options['shallow'] && count($params) > 0) {
                    // Remove the last prefix and param. `users.emails` becomes
                    // just `emails`, but only if we've gotten a model above us.
                    array_pop($prefixes);
                    array_pop($params);
                }
                $params[static::parameterNameFromClass($class)] = $model->getKey();
            }
            $prefixes[] = static::prefixFromClass($class);
        } elseif (is_string($model)) {
            $prefixes[] = static::prefixFromClass($model);
            $isCollection = true;
        }
    }

    /**
     * @param array<mixed> $prefixes
     * @param array<string,string|int> $params
     * @param array<string,mixed> $options
     */
    protected static function processIntermediateFragment(
        Model|string $model,
        array &$prefixes,
        array &$params,
        array $options
    ): void {
        if ($model instanceof Model) {
            $class = get_class($model);
            $prefixes[] = static::prefixFromClass($class);
            $params[static::parameterNameFromClass($class)] = $model->getKey();
        } elseif (is_string($model)) {
            $prefixes[] = $model;
        }
    }

    /**
     * @param array<string,mixed> $options
     */
    protected static function findRoute(string $prefix, bool $isCollection, array $options): ?string {
        $defaultActions = $isCollection ? [null, 'index', 'store'] : ['show', 'update', 'destroy'];
        $actions = Arr::get($options, 'action', $defaultActions);

        $actions = (array)$actions;

        foreach ($actions as $action) {
            $route = $prefix . ($action ? '.' . $action : '');
            if (Route::has($route)) {
                return $route;
            }
        }

        return null;
    }

    protected static function prefixFromClass(string $class): string {
        if (method_exists($class, 'modelName')) {
            return call_user_func([$class, 'modelName'])->route_key;
        }

        return Str::plural(Str::kebab(class_basename($class)));
    }

    protected static function parameterNameFromClass(string $class): string {
        $value = Str::snake(Str::plural(class_basename($class)));
        $resource_registrar = app('Illuminate\Routing\ResourceRegistrar');

        return $resource_registrar->getResourceWildcard($value);
    }
}
