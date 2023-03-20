<?php

namespace SilvertipSoftware\LaravelSupport\Eloquent;

use Exception;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Arr;

trait Transactions {

    public static function createOrFail(array $attributes = []) {
        return tap(static::newModelInstance($attributes), function ($instance) {
            $instance->saveOrFail();
        });
    }

    public function delete() {
        $ret = false;

        try {
            $ret = $this->transactionalDeleteOrFail();
        } catch (Exception $ex) {
        }

        return $ret;
    }

    public function deleteOrFail() {
        return $this->transactionalDeleteOrFail();
    }

    public function save(array $options = []) {
        $ret = false;

        try {
            $ret = $this->transactionalSaveOrFail($options);
        } catch (Exception $ex) {
        }

        return $ret;
    }

    public function saveOrFail(array $options = []) {
        return $this->transactionalSaveOrFail($options);
    }

    public function updateOrFail(array $attributes = [], array $options = []) {
        if (!$this->exists) {
            return false;
        }

        return $this->fill($attributes)->saveOrFail($options);
    }

    abstract protected function processRollback();
    abstract protected function processSave($options);

    protected static function bootTransactions() {
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

    protected function finishSave(array $options) {
        $this->fireModelEvent('saved', false);

        if (Arr::get($options, 'touch', true)) {
            $this->touchOwners();
        }
    }

    protected function processDelete() {
        return parent::delete();
    }

    protected function processDeletionRollback() {
        $this->exists = true;

        if (in_array(SoftDeletes::class, class_uses_recursive(static::class))) {
            $this->{$this->getDeletedAtColumn()} = null;
        }
    }

    protected function rollbackSelf() {
        if ($this->exists && $this->isDirty($this->primaryKey)) {
            $this->{$this->primaryKey} = $this->getOriginal($this->primaryKey);
            $this->exists = $this->{$this->primaryKey} !== null;
            $this->wasRecentlyCreated = $this->wasRecentlyCreated && $this->exists;
        }
    }

    protected function transactionalDeleteOrFail() {
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

    protected function transactionalSaveOrFail($options) {
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
