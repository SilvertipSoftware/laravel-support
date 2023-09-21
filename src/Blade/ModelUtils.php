<?php

declare(strict_types=1);

namespace SilvertipSoftware\LaravelSupport\Blade;

trait ModelUtils {

    public static function domClass($modelOrClass, $prefix = null) {
        $singular = static::modelNameFrom($modelOrClass)->param_key;

        return $prefix
            ? ($prefix . '_' . $singular)
            : $singular;
    }

    public static function domId($model, $prefix = null) {
        $id = $model->getKey();

        return $id
            ? static::domClass($model, $prefix) . '_' . $id
            : static::domClass($model, $prefix ?? 'new');
    }

    protected static function convertToModel($obj) {
        $hasMethods = is_object($obj) || is_string($obj);

        if ($hasMethods && method_exists($obj, 'toModel')) {
            return call_user_func([$obj, 'toModel']);
        }

        return $obj;
    }

    protected static function modelNameFrom($obj) {
        if ($obj == null) {
            return null;
        }

        $model = static::convertToModel($obj);
        return $model->modelName();
    }

    public static function isManyRelation($class) {
        $base = class_basename($class);

        $relationsReturningCollections = [
            'BelongsToMany', 'HasMany', 'HasManyThrough', 'MorphMany', 'MorphToMany'
        ];

        return in_array($base, $relationsReturningCollections);
    }
}
