<?php

declare(strict_types=1);

namespace SilvertipSoftware\LaravelSupport\Blade;

use SilvertipSoftware\LaravelSupport\Eloquent\FluentModel;
use SilvertipSoftware\LaravelSupport\Eloquent\Model;
use SilvertipSoftware\LaravelSupport\Eloquent\Naming\Name;

trait ModelUtils {

    public static function domClass(object|string $modelOrClass, ?string $prefix = null): string {
        $singular = static::modelNameFrom($modelOrClass)->param_key;

        return $prefix
            ? ($prefix . '_' . $singular)
            : $singular;
    }

    public static function domId(Model $model, ?string $prefix = null): string {
        $id = $model->getKey();

        return $id
            ? static::domClass($model, $prefix) . '_' . $id
            : static::domClass($model, $prefix ?? 'new');
    }

    protected static function convertToModel(mixed $obj): mixed {
        $hasMethods = is_object($obj) || is_string($obj);

        if ($hasMethods && method_exists($obj, 'toModel')) {
            return call_user_func([$obj, 'toModel']);
        }

        return $obj;
    }

    protected static function modelNameFrom(string|object|null $obj): ?Name {
        if ($obj == null) {
            return null;
        }

        $model = static::convertToModel($obj);
        return $model->modelName();
    }

    public static function isManyRelation(string $class): bool {
        $base = class_basename($class);

        $relationsReturningCollections = [
            'BelongsToMany', 'HasMany', 'HasManyThrough', 'MorphMany', 'MorphToMany'
        ];

        return in_array($base, $relationsReturningCollections);
    }
}
