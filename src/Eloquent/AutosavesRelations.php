<?php

namespace SilvertipSoftware\LaravelSupport\Eloquent;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\MessageBag;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;

trait AutosavesRelations {

    /** @var array<string, array<string,mixed>> */
    protected static array $autosavedRelations = [];
    protected bool $markedForDestruction = false;

    /**
     * @return array<string>
     */
    public static function getAutosavedRelations(): array {
        return array_keys(Arr::get(static::$autosavedRelations, static::class, []));
    }

    public function isAutosaveRelation(string $name): bool {
        return in_array($name, static::getAutosavedRelations());
    }

    public function isMarkedForDestruction(): bool {
        return $this->markedForDestruction;
    }

    public function markForDestruction(): void {
        $this->markedForDestruction = true;
    }

    public function push(): bool {
        return $this->save();
    }

    public function refresh(): static {
        $this->markedForDestruction = false;

        return parent::refresh();
    }

    /**
     * @param string|array<string> $names
     */
    protected static function addAutosavedRelation(string|array $names): void {
        $autosavedRelations = Arr::get(static::$autosavedRelations, static::class, []);

        foreach ((array)$names as $name) {
            if (!method_exists(static::class, $name)) {
                throw new RuntimeException('Relation ' . $name . ' does not exist on ' . static::class);
            }

            $opts = [];
            $autosavedRelations[$name] = $opts;
        }

        static::$autosavedRelations[static::class] = $autosavedRelations;
    }

    protected static function bootAutosavesRelations(): void {
        static::registerModelEvent('afterCommit', function ($model) {
            $model->syncAutosavedRelations();
        });
    }

    /**
     * @return array<string,mixed>
     */
    protected function getAutosaveOptionsFor(string $relationName): array {
        return static::$autosavedRelations[static::class][$relationName];
    }

    protected function getInverseRelationNameFor(string $relationName): string {
        $method = 'inverseRelationNameFor' . Str::studly($relationName);

        return method_exists($this, $method) ? $this->{$method}() : Str::singular($this->getTable());
    }

    /**
     * @return array<string,mixed>
     */
    protected function loadedAutosavedRelations(): array {
        return array_filter($this->relations, function ($key) {
            return $this->isAutosaveRelation($key);
        }, ARRAY_FILTER_USE_KEY);
    }

    /**
     * @return array<string,mixed>
     */
    protected function loadedAutosavedRelationsByOrder(): array {
        $ret = [
            'pre' => [],
            'post' => [],
        ];

        $loadedRelations = $this->loadedAutosavedRelations();
        foreach ($loadedRelations as $relationName => $value) {
            $order = $this->phaseForAutosavedRelation($relationName);
            $ret[$order][$relationName] = $value;
        }

        return $ret;
    }

    protected function phaseForAutosavedRelation(string $name): string {
        if (!method_exists($this, $name)) {
            throw new RuntimeException('Unknown relation ' . $name . ' on ' . get_class($this));
        }

        $order = 'post';

        $relationType = class_basename($this->{$name}());

        switch ($relationType) {
            case 'BelongsTo':
            case 'MorphTo':
                $order = 'pre';
                break;
            case 'HasOne':
            case 'HasMany':
            case 'MorphOne':
                break;
            default:
                throw new RuntimeException("Autosave for $relationType relations not supported.");
        }

        return $order;
    }

    /**
     * @param ?array<Model> $models
     * @param array<string,mixed> $options
     */
    protected function pushAutosavedModels(?array $models, string $relationName, array $options): bool {
        $ret = true;

        foreach (array_filter($models ?: []) as $model) {
            if ($model->isMarkedForDestruction() && $model->exists) {
                $ret = $model->delete();
            } else {
                $relation = $this->{$relationName}();
                $relationType = class_basename($relation);

                switch ($relationType) {
                    case 'HasOne':
                    case 'HasMany':
                    case 'MorphOne':
                        // $inverseName = $this->getInverseRelationNameFor($relationName);
                        // $model->{$inverseName}()->associate($this);
                        $model->setAttribute($relation->getForeignKeyName(), $relation->getParentKey());
                        $ret = $model->saveOrFail($options);
                        break;
                    case 'BelongsTo':
                    case 'MorphTo':
                        $ret = $model->saveOrFail($options);
                        if ($ret && $model->exists) {
                            $foreignKey = $relation->getForeignKeyName();
                            $this->{$foreignKey} = $model->getKey();
                            if ($relationType == 'MorphTo') {
                                $this->{$relation->getMorphType()} = $model->getMorphClass();
                            }
                        }
                        break;
                    default:
                        throw new RuntimeException("Autosave for $relationType relations not supported.");
                }
            }

            if (!$ret) {
                return false;
            }
        }

        return $ret;
    }

    /**
     * @param Collection|Model|array<Model>|null $value
     * @param array<string,mixed> $pushOptions
     */
    protected function pushAutosavedRelation(
        string $relationName,
        Collection|Model|array|null $value,
        array $pushOptions
    ): bool {
        $models = ($value instanceof Collection)
            ? $value->all()
            : ($value instanceof Model ? [$value] : $value);

        if (!$this->pushAutosavedModels($models, $relationName, $pushOptions)) {
            return false;
        }

        return true;
    }

    /**
     * @param array<string,mixed> $options
     */
    protected function pushSelfAndAutosavedRelations(array $options): bool {
        $relationsToAutosave = $this->loadedAutosavedRelationsByOrder();

        foreach ($relationsToAutosave['pre'] as $relationName => $value) {
            if (!$this->pushAutosavedRelation($relationName, $value, $options)) {
                return false;
            }
        }

        if (!$this->saveSelf($options)) {
            return false;
        }

        foreach ($relationsToAutosave['post'] as $relationName => $value) {
            if (!$this->pushAutosavedRelation($relationName, $value, $options)) {
                return false;
            }
        }

        return true;
    }

    protected function syncAutosavedRelations(): void {
        $loadedRelations = $this->loadedAutosavedRelations();

        foreach ($loadedRelations as $relationName => $value) {
            if ($value instanceof Collection) {
                $stillExisting = $value->filter(function ($child) {
                    return !$child->isMarkedForDestruction();
                });

                $this->setRelation($relationName, $stillExisting);
            } elseif ($value && method_exists($value, 'isMarkedForDestruction') && $value->isMarkedForDestruction()) {
                $this->setRelation($relationName, null);
            }
        }
    }

    protected function validateAutosavedRelations(): void {
        $relationsToAutosave = $this->loadedAutosavedRelations();

        foreach ($relationsToAutosave as $relationName => $value) {
            $models = ($value instanceof Collection)
                ? $value->all()
                : ($value instanceof Model ? [$value] : $value);

            $this->validateAutosavedModels($models, $relationName);
        }
    }

    /**
     * @param ?array<Model> $models
     */
    protected function rollbackAutosavedModels(?array $models, string $relationName): void {
        foreach (array_filter($models ?: []) as $model) {
            if ($model->isMarkedForDestruction() && $model->id) {
                $model->exists = true;
            } else {
                $model->processRollback();
            }
        }
    }

    protected function rollbackSelfAndAutosavedRelations(): void {
        $this->rollbackSelf();
        // $relationsToRollback = $this->loadedAutosavedRelations();

        // foreach ($relationsToRollback as $relationName => $value) {
        //     $models = ($value instanceof Collection)
        //         ? $value->all()
        //         : ($value instanceof Model ? [$value] : $value);

        //     $this->rollbackAutosavedModels($models, $relationName);
        // }
    }

    /**
     * @param array<string,mixed> $options
     */
    protected function saveSelf(array $options): bool {
        return parent::save();
    }

    /**
     * @param ?array<Model> $models
     */
    protected function validateAutosavedModels(?array $models, string $relationName): void {
        $relationType = class_basename($this->{$relationName}());

        foreach (array_filter($models ?: []) as $model) {
            if (!$model->isMarkedForDestruction()) {
                try {
                    $ignoredRules = $this->validationRulesToIgnore($model, $relationName);
                    $model->validate(null, $ignoredRules);
                } catch (ValidationException $vex) {
//                    $this->mergeErrors($model->errors, $relationName);
                    $this->mergeErrors(new MessageBag([
                        $relationName => [Lang::get($this->genericInvalidMessageKey)]
                    ]));
                }
            }
        }
    }

    /**
     * @return array<string|array<string,string>>
     */
    protected function validationRulesToIgnore(Model $model, string $relationName): array {
        $ignored = [];

        $relationType = class_basename($this->{$relationName}());

        switch ($relationType) {
            case 'BelongsTo':
            case 'MorphTo':
                break;
            default:
                $inverseName = $this->getInverseRelationNameFor($relationName);
                if (method_exists($model, $inverseName)) {
                    $inverseRelation = $model->{$inverseName}();
                    $inverseRelationType = class_basename(get_class($inverseRelation));
                    switch ($inverseRelationType) {
                        case 'MorphTo':
                            $ignored = [$inverseRelation->getForeignKeyName(), $inverseRelation->getMorphType()];
                            break;
                        case 'BelongsTo':
                            $ignored = [$inverseRelation->getForeignKeyName()];
                            break;
                        default:
                            throw new RuntimeException(
                                'Nested validation for ' . $inverseRelationType
                                . ' (inverse) relations not supported.'
                            );
                    }
                }
                break;
        }

        return $ignored;
    }

    /**
     * @return array<string>
     */
    protected function validationRulesToIgnoreForParentRelations(): array {
        $ignore = [];
        $relationsToAutosave = $this->loadedAutosavedRelationsByOrder();

        foreach ($relationsToAutosave['pre'] as $relationName => $value) {
            $relation = $this->{$relationName}();
            // old Laravel needs getForeignKey()
            $ignore[] = method_exists($relation, 'getForeignKeyName')
                ? $relation->getForeignKeyName()
                : $relation->getForeignKey();
        }

        return $ignore;
    }
}
