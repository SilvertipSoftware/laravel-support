<?php

declare(strict_types=1);

namespace SilvertipSoftware\LaravelSupport\Eloquent;

use SilvertipSoftware\LaravelSupport\Eloquent\Naming\Name;

trait Naming {

    protected static $modelNames = [];
    protected static $modelRelativeNamespace = null;

    public static function modelName() {
        if (!isset(static::$modelNames[static::class])) {
            static::$modelNames[static::class] = new Name(
                static::class,
                value(static::$modelRelativeNamespace, static::class)
            );
        }

        return static::$modelNames[static::class];
    }

    public function getModelNameAttribute() {
        return static::modelName();
    }
}
