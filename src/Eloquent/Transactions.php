<?php

declare(strict_types=1);

namespace SilvertipSoftware\LaravelSupport\Eloquent;

use Exception;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Arr;

trait Transactions {

    /**
     * @param array<string, mixed>|Arrayable $attributes
     */
    public static function createOrFail(array|Arrayable $attributes = []): static {
        $attrs = $attributes instanceof Arrayable
            ? $attributes->toArray()
            : $attributes;

        // @phpstan-ignore-next-line
        return tap(static::newModelInstance($attrs), function ($instance) {
            $instance->saveOrFail();
        });
    }

    public function delete(): bool {
        $ret = false;

        try {
            $ret = $this->transactionalDeleteOrFail();
        } catch (Exception $ex) {
        }

        return $ret;
    }

    public function deleteOrFail(): bool {
        return $this->transactionalDeleteOrFail();
    }

    /**
     * @param array<string, mixed> $options
     */
    public function save(array $options = []): bool {
        $ret = false;

        try {
            $ret = $this->transactionalSaveOrFail($options);
        } catch (Exception $ex) {
        }

        return $ret;
    }

    /**
     * @param array<string, mixed> $options
     */
    public function saveOrFail(array $options = []): bool {
        return $this->transactionalSaveOrFail($options);
    }

    /**
     * @param array<string, mixed>|Arrayable $attributes
     * @param array<string, mixed> $options
     */
    public function update(array|Arrayable $attributes = [], array $options = []) {
        $ret = false;

        try {
            $ret = $this->updateOrFail($attributes, $options);
        } catch (Exception $ex) {
        }

        return $ret;
    }

    /**
     * @param array<string, mixed>|Arrayable $attributes
     * @param array<string, mixed> $options
     */
    public function updateOrFail(array|Arrayable $attributes = [], array $options = []) {
        if (!$this->exists) {
            return false;
        }

        $attributes = $attributes instanceof Arrayable
            ? $attributes->toArray()
            : $attributes;

        return $this->fill($attributes)->saveOrFail($options);
    }

    abstract protected function processRollback();

    /**
     * @param array<string, mixed> $options
     */
    abstract protected function processSave(array $options): bool;

    protected static function bootTransactions(): void {
        static::registerModelEvent('afterCommit', function ($model) {
            $model->syncOriginal();
        });
        static::registerModelEvent('afterRollback', function ($model) {
            $model->processRollback();
        });
        static::registerModelEvent('afterDeletingRollback', function ($model) {
            $model->processDeletionRollback();
        });
    }

    /**
     * @param array<string, mixed> $options
     */
    protected function finishSave(array $options): void {
        $this->fireModelEvent('saved', false);

        if (Arr::get($options, 'touch', true)) {
            $this->touchOwners();
        }
    }

    protected function processDelete(): ?bool {
        return parent::delete();
    }

    protected function processDeletionRollback(): void {
        $this->exists = true;

        if (in_array(SoftDeletes::class, class_uses_recursive(static::class))) {
            // @phpstan-ignore-next-line
            $this->{$this->getDeletedAtColumn()} = null;
        }
    }

    protected function rollbackSelf(): void {
        if ($this->exists && $this->isDirty($this->primaryKey)) {
            $this->{$this->primaryKey} = $this->getOriginal($this->primaryKey);
            $this->exists = $this->{$this->primaryKey} !== null;
            $this->wasRecentlyCreated = $this->wasRecentlyCreated && $this->exists;
        }
    }

    protected function transactionalDeleteOrFail(): bool {
        $fn = function () {
            try {
                if (!$this->processDelete()) {
                    throw new Exception('transactional delete failed');
                }

                return true;
            } catch (Exception $ex) {
                throw $ex;
            }
        };

        return $this->getConnection()->transactionLevel() == 0
            ? $this->getConnection()->transaction($fn)
            : $fn();
    }

    /**
     * @param array<string, mixed> $options
     */
    protected function transactionalSaveOrFail(array $options): bool {
        $fn = function () use ($options) {
            try {
                if (!$this->processSave($options)) {
                    throw new Exception('transactional save failed');
                }

                return true;
            } catch (Exception $ex) {
                throw $ex;
            }
        };

        return $this->getConnection()->transactionLevel() == 0
            ? $this->getConnection()->transaction($fn)
            : $fn();
    }
}
