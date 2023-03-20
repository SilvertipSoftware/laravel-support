<?php

namespace SilvertipSoftware\LaravelSupport\Eloquent;

use SilvertipSoftware\LaravelSupport\Eloquent\Naming\Name;

trait Naming {

    protected static $modelNames = [];

    public static function modelName() {
        if (!isset(static::$modelNames[static::class])) {
            static::$modelNames[static::class] = new Name(
                static::class,
                isset(static::$modelRelativeNamespace)
                    ? value(static::$modelRelativeNamespace, static::class)
                    : null
            );
        }

        return static::$modelNames[static::class];
    }

    public function getModelNameAttribute() {
        return static::modelName();
    }
}
