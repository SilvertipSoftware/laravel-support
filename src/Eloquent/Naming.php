<?php

declare(strict_types=1);

namespace SilvertipSoftware\LaravelSupport\Eloquent;

use Closure;
use SilvertipSoftware\LaravelSupport\Eloquent\Naming\Name;

trait Naming {

    /** @var array<string,Name> */
    protected static array $modelNames = [];
    protected static string|Closure|null $modelRelativeNamespace = null;

    public static function modelName(): Name {
        if (!isset(static::$modelNames[static::class])) {
            static::$modelNames[static::class] = new Name(
                static::class,
                value(static::$modelRelativeNamespace, static::class)
            );
        }

        return static::$modelNames[static::class];
    }

    public function getModelNameAttribute(): Name {
        return static::modelName();
    }
}
