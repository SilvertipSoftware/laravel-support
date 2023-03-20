<?php

namespace SilvertipSoftware\LaravelSupport\Eloquent\Naming;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Fluent;
use Illuminate\Support\Str;

class Name extends Fluent {

    public static $rootModelNamespace = 'App\\Models';

    protected $class;

    public function __construct($class, $namespace = null) {
        $name = Str::after($class, value(static::$rootModelNamespace, $class) . '\\');
        $unnamespaced = $namespace ? Str::after($class, $namespace . '\\') : null;
        $baseName = class_basename($class);

        $singular = Str::singular(str_replace('\\', '', Str::snake($name)));
        $plural = Str::plural($singular);
        $uncountable = $singular == $plural;
        $element = Str::snake($baseName);
        $human = str_replace('_', ' ', ucfirst($element));

        $collection = $this->tableize($name);

        // param_key is used in name/id of DOM elements. Standard is to separate with _
        $paramKey = $namespace
            ? Str::singular(str_replace('\\', '', Str::snake($unnamespaced)))
            : $singular;
        $i18nKey = str_replace('\\_', '.', Str::snake($name));

        // route keys are used in named routes. Laravel standard is to separate with .
        $routeKey = Str::plural(str_replace('\\-', '.', Str::kebab($namespace ? $unnamespaced : $name)));
        $singularRouteKey = Str::singular($routeKey);
        $routeKey = $uncountable
            ? $routeKey . '.index'
            : $routeKey;

        parent::__construct([
            'singular' => $singular,
            'plural' => $plural,
            'element' => $element,
            'collection' => $collection,
            'singular_route_key' => $singularRouteKey,
            'route_key' => $routeKey,
            'param_key' => $paramKey,
            'i18n_key' => $i18nKey,
            'name' => $name,
            'is_uncountable' => $uncountable,
            'human' => $human
        ]);

        $this->class = $class;
    }

    public function human($opts = []) {
        $fullKey = $this->qualifiedI18nKeyFor($this->class, $this->i18n_key);
        $locale = Arr::get($opts, 'locale');

        if (Lang::has($fullKey)) {
            if (Arr::has($opts, 'count')) {
                return Lang::choice($fullKey, Arr::get($opts, 'count'), $opts, $locale);
            }

            return Lang::get($fullKey, $opts, $locale);
        }

        return $this->attributes['human'];
    }

    private function tableize($str) {
        return Str::plural(str_replace('\\_', '/', Str::snake($str)));
    }

    private function qualifiedI18nKeyFor($class, $i18nKey) {
        $scope = method_exists($class, 'i18nScope')
            ? $class::i18nScope()
            : 'eloquent';

        return $scope
            . (Str::endsWith($scope, '::') ? '' : '.')
            . 'models.' . $i18nKey;
    }
}
