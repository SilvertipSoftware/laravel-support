<?php

declare(strict_types=1);

namespace SilvertipSoftware\LaravelSupport\Eloquent;

use Closure;
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

    protected static function addNestedAttribute($names, $options = []) {
        $nestedAttributes = Arr::get(static::$acceptsNestedAttributesFor, static::class, []);
        $options = array_merge(['allow_destroy' => false, 'update_only' => false], $options);

        foreach ((array)$names as $name) {
            if (!method_exists(static::class, $name)) {
                throw new RuntimeException('Relation ' . $name . ' does not exist on ' . static::class);
            }

            if (Arr::get($options, 'reject_if') === 'all_blank') {
                $options['reject_if'] = function ($attributes) {
                    $nonBlank = Arr::first($attributes, function ($value, $key) {
                        return $key !== '_destroy' && !empty($value);
                    });
                };
            }

            $nestedAttributes[$name] = Arr::only($options, ['update_only', 'allow_destroy', 'reject_if']);
            static::addAutosavedRelation($name);
        }

        static::$acceptsNestedAttributesFor[static::class] = $nestedAttributes;
    }

    protected function allowsDestroy(string $relationName): bool {
        return (bool) Arr::get($this->getOptionsForNestedAttributes($relationName), 'allow_destroy');
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
            if (!$this->callRejectIf($relationName, $attrs)) {
                $this->assignOrMarkForDestruction($existingRecord, $attrs, $options);
                $existingRecord->setAttribute($relation->getForeignKeyName(), $relation->getParentKey());
            }
        } elseif (Arr::get($attrs, 'id')) {
            throw new RuntimeException('Cannot set nested attributes for ' . $relationName . ' using id');
        } elseif (!$this->rejectNewRecord($relationName, $attrs)) {
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
            if (!$this->callRejectIf($relationName, $attrs)) {
                $this->assignOrMarkForDestruction($existingRecord, $attrs, $options);
                if ($existingRecord->isMarkedForDestruction()) {
                    $foreignKey = $relation->getForeignKeyName();
                    $this->{$foreignKey} = null;
                }
            }
        } elseif (Arr::get($attrs, 'id')) {
            throw new RuntimeException('Cannot set nested attributes for ' . $relationName . ' using id');
        } elseif (!$this->rejectNewRecord($relationName, $attrs)) {
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
                if (!$this->rejectNewRecord($relationName, $attrs)) {
                    $newRecord = $relation->make(Arr::except($attrs, $this->getUnassignableKeys()));
                    $existingRecords[] = $newRecord;
                }
            } else {
                $existingRecord = Arr::first($existingRecords, function ($model) use ($attrs) {
                    return $model->getKey() == $attrs['id'];
                });
                if ($existingRecord) {
                    if (!$this->callRejectIf($relationName, $attrs)) {
                        $this->assignOrMarkForDestruction($existingRecord, $attrs, $options);
                    }
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

    protected function callRejectIf(string $relationName, array $attributes): bool {
        if ($this->willBeDestroyed($relationName, $attributes)) {
            return false;
        }

        $callback = Arr::get($this->getOptionsForNestedAttributes($relationName), 'reject_if');

        if ($callback instanceof Closure) {
            return $callback($attributes);
        } elseif (is_string($callback) && preg_match('/^call:(.+)$/', $callback, $matches)) {
            return $this->{$matches[1]}($attributes);
        }

        return false;
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

    protected function hasDestroyFlag(array $attrs): bool {
        return (bool) Arr::get($attrs, '_destroy', false);
    }

    protected function rejectNewRecord(string $relationName, array $attributes): bool {
        return $this->willBeDestroyed($relationName, $attributes)
            || $this->callRejectIf($relationName, $attributes);
    }

    protected function willBeDestroyed(string $relationName, array $attributes): bool {
        return $this->allowsDestroy($relationName) && $this->hasDestroyFlag($attributes);
    }
}
