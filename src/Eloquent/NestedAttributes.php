<?php

namespace SilvertipSoftware\LaravelSupport\Eloquent;

use Illuminate\Support\Arr;
use RuntimeException;

trait NestedAttributes {

    protected static $acceptsNestedAttributesFor = [];

    public static function getNestedAttributes() {
        return array_keys(Arr::get(static::$acceptsNestedAttributesFor, static::class, []));
    }

    public function isNestedAttribute($name) {
        return in_array($name, static::getNestedAttributes());
    }

    public function setAttribute($key, $value) {
        if (str_contains($key, '_attributes')) {
            $relationName = str_replace('_attributes', '', $key);
            if ($this->isNestedAttribute($relationName)) {
                return $this->assignNestedAttributes($relationName, $value);
            }
        }

        return parent::setAttribute($key, $value);
    }

    protected static function addNestedAttribute($names, $opts = []) {
        $nestedAttributes = Arr::get(static::$acceptsNestedAttributesFor, static::class, []);

        foreach ((array)$names as $name) {
            if (!method_exists(static::class, $name)) {
                throw new RuntimeException('Relation ' . $name . ' does not exist on ' . static::class);
            }

            $nestedAttributes[$name] = Arr::only($opts, ['update_only', 'allow_destroy', 'reject_if']);
            static::addAutosavedRelation($name);
        }

        static::$acceptsNestedAttributesFor[static::class] = $nestedAttributes;
    }

    protected function assignNestedAttributes($relationName, $attrs) {
        $relationType = class_basename($this->{$relationName}());

        switch ($relationType) {
            case 'HasOne':
            case 'MorphOne':
                $this->assignNestedAttributesForOneToOne($relationName, $attrs);
                break;
            case 'BelongsTo':
                $this->assignNestedAttributesForBelongsTo($relationName, $attrs);
                break;
            case 'HasMany':
                $this->assignNestedAttributesForOneToMany($relationName, $attrs);
                break;
            default:
                throw new RuntimeException("Nested attributes for $relationType not supported");
        }

        return $this;
    }

    protected function assignNestedAttributesForOneToOne($relationName, $attrs) {
        $existingRecord = $this->{$relationName};
        $relation = $this->{$relationName}();
        $options = $this->getOptionsForNestedAttributes($relationName);

        $updateOnlyOrId = Arr::get($options, 'update_only') || Arr::get($attrs, 'id');
        $updateOnlyOrMatchingId = Arr::get($options, 'update_only')
            || ($existingRecord && (Arr::get($attrs, 'id') == $existingRecord->getKey()));

        if ($updateOnlyOrId && $existingRecord && $updateOnlyOrMatchingId) {
            $this->assignOrMarkForDestruction($existingRecord, $attrs, $options);
            $existingRecord->setAttribute($relation->getForeignKeyName(), $relation->getParentKey());
        } elseif (Arr::get($attrs, 'id')) {
            throw new RuntimeException('Cannot set nested attributes for ' . $relationName . ' using id');
        } else {
            $fillableAttributes = Arr::except($attrs, $this->getUnassignableKeys());

            if ($existingRecord && !$existingRecord->exists) {
                $existingRecord->fill($fillableAttributes);
                $existingRecord->setAttribute($relation->getForeignKeyName(), $relation->getParentKey());
            } elseif (!Arr::get($attrs, '_destroy') | !Arr::get($options, 'allow_destroy')) {
                $newRecord = $relation->make($fillableAttributes);
                $this->setRelation($relationName, $newRecord);
            }
        }
    }

    protected function assignNestedAttributesForBelongsTo($relationName, $attrs) {
        $existingRecord = $this->{$relationName};
        $relation = $this->{$relationName}();
        $options = $this->getOptionsForNestedAttributes($relationName);

        $updateOnlyOrId = Arr::get($options, 'update_only') || Arr::get($attrs, 'id');
        $updateOnlyOrMatchingId = Arr::get($options, 'update_only')
            || ($existingRecord && (Arr::get($attrs, 'id') == $existingRecord->getKey()));

        if ($updateOnlyOrId && $existingRecord && $updateOnlyOrMatchingId) {
            $this->assignOrMarkForDestruction($existingRecord, $attrs, $options);
            if ($existingRecord->isMarkedForDestruction()) {
                $foreignKey = $relation->getForeignKeyName();
                $this->{$foreignKey} = null;
            }
        } elseif (Arr::get($attrs, 'id')) {
            throw new RuntimeException('Cannot set nested attributes for ' . $relationName . ' using id');
        } else {
            $fillableAttributes = Arr::except($attrs, $this->getUnassignableKeys());

            if ($existingRecord && !$existingRecord->exists) {
                $existingRecord->fill($fillableAttributes);
                $foreignKey = $relation->getForeignKeyName();
                $this->{$foreignKey} = null;
            } elseif (!Arr::get($attrs, '_destroy') | !Arr::get($options, 'allow_destroy')) {
                $newRecord = $relation->make($fillableAttributes);
                $this->setRelation($relationName, $newRecord);
            }
        }
    }

    protected function assignNestedAttributesForOneToMany($relationName, $attrsArray) {
        $relation = $this->{$relationName}();
        $options = $this->getOptionsForNestedAttributes($relationName);

        if ($this->relationLoaded($relationName)) {
            $existingRecords = $this->{$relationName}->all();
        } else {
            $attributeIds = Arr::pluck(Arr::where($attrsArray, function ($attrs) {
                return isset($attrs['id']);
            }), 'id');

            $existingRecords = empty($attributeIds)
                ? []
                : $relation->whereIn($relation->getRelated()->getKeyName(), $attributeIds)
                    ->get()
                    ->all();
        }

        foreach ($attrsArray as $attrs) {
            if (empty($attrs['id'])) {
                if (!Arr::get($attrs, '_destroy')) {
                    $newRecord = $relation->make(Arr::except($attrs, $this->getUnassignableKeys()));
                    $existingRecords[] = $newRecord;
                }
            } else {
                $existingRecord = Arr::first($existingRecords, function ($model) use ($attrs) {
                    return $model->getKey() == $attrs['id'];
                });
                if ($existingRecord) {
                    $this->assignOrMarkForDestruction($existingRecord, $attrs, $options);
                } else {
                    throw new RuntimeException("Could not find related $relationName with id ".$attrs['id']);
                }
            }
        }

        $this->setRelation($relationName, collect($existingRecords));
    }

    protected function assignOrMarkForDestruction($model, $attrs, $options) {
        $model->fill(Arr::except($attrs, $this->getUnassignableKeys($model)));

        if ($this->hasDestroyFlag($attrs) && Arr::get($options, 'allow_destroy')) {
            $model->markForDestruction();
        }
    }

    protected function getOptionsForNestedAttributes($name) {
        return static::$acceptsNestedAttributesFor[static::class][$name];
    }

    protected function getUnassignableKeys($model = null) {
        return [
            $model ? $model->primaryKey : 'id',
            '_destroy'
        ];
    }

    protected function hasDestroyFlag($attrs) {
        return isset($attrs['_destroy'])
            ? !!$attrs['_destroy']
            : false;
    }
}
