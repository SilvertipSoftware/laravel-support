<?php

namespace SilvertipSoftware\LaravelSupport\Eloquent;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Arr;

trait ConfigBound {

    protected static $cachedInstances = null;

    public static function find($key) {
        return Arr::get(static::allInstances(), $key);
    }

    public static function findOrFail($key) {
        $model = static::find($key);

        return $model ?: throw (new ModelNotFoundException)->setModel(static::class, $key);
    }

    public static function exists($key) {
        return array_key_exists($key, static::allInstances());
    }

    public static function all() {
        return collect(static::allInstances());
    }

    protected static function allInstances() {
        if (static::$cachedInstances == null) {
            static::bootIfNotBooted();
        }

        return static::$cachedInstances;
    }

    protected static function bootIfNotBooted() {
        if (static::$cachedInstances == null) {
            static::loadModels();
        }
    }

    protected static function loadModels() {
        foreach (config(static::$configKey) as $key => $attrs) {
            static::$cachedInstances[$key] = new static(['id' => $key] + $attrs);
        }
    }
}
