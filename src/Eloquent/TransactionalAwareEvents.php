<?php

namespace SilvertipSoftware\LaravelSupport\Eloquent;

use Exception;
use Illuminate\Database\Events\TransactionCommitted;
use Illuminate\Database\Events\TransactionRolledBack;
use Illuminate\Support\Arr;

trait TransactionalAwareEvents {

    protected static $orderedTransactionalEvents = ['', 'saving', 'deleting'];

    protected static $queuedTransactionalEvents = [];

    protected static function bootTransactionalAwareEvents() {
        $dispatcher = static::getEventDispatcher();

        foreach (static::$orderedTransactionalEvents as $beforeEvent) {
            if ($beforeEvent != '') {
                static::registerModelEvent($beforeEvent, function ($model) use ($beforeEvent) {
                    if ($model->getConnection()->transactionLevel()) {
                        $connectionName = $model->getConnectionName() ?: $model->getConnection()->getName();
                        $connectionQueuedEvents = Arr::get(self::$queuedTransactionalEvents, $connectionName, []);
                        $baseQueuedEvents = Arr::get($connectionQueuedEvents, '', []);
                        if (!in_array($model, $baseQueuedEvents)) {
                            self::$queuedTransactionalEvents[$connectionName][''][] = $model;
                        }
                        self::$queuedTransactionalEvents[$connectionName][$beforeEvent][] = $model;
                    } else {
                        throw new Exception('model saving/deleting event outside of a txn should never happen!!!');
                    }
                });
            }
        }

        $dispatcher->listen(TransactionCommitted::class, function ($event) {
            if ($event->connection->transactionLevel() > 0) {
                return;
            }

            $queuedEvents = Arr::get(self::$queuedTransactionalEvents, $event->connectionName, []);

            foreach (static::$orderedTransactionalEvents as $eventName) {
                foreach (Arr::get($queuedEvents, $eventName, []) as $model) {
                    $model->fireModelEvent(static::transactionalEventNameFor('commit', $eventName));
                }
            }

            self::$queuedTransactionalEvents[$event->connectionName] = [];
        });

        $dispatcher->listen(TransactionRolledBack::class, function ($event) {
            if ($event->connection->transactionLevel() > 0) {
                return;
            }

            $queuedEvents = Arr::get(self::$queuedTransactionalEvents, $event->connectionName, []);

            foreach (static::$orderedTransactionalEvents as $eventName) {
                foreach (Arr::get($queuedEvents, $eventName, []) as $model) {
                    $model->fireModelEvent(static::transactionalEventNameFor('rollback', $eventName));
                }
            }

            self::$queuedTransactionalEvents[$event->connectionName] = [];
        });
    }

    protected static function transactionalEventNameFor($transactionEvent, $eventName) {
        return 'after' . ucfirst($eventName) . ucfirst($transactionEvent);
    }

    protected function initializeTransactionalAwareEvents() {
        foreach (static::$orderedTransactionalEvents as $eventName) {
            $this->addObservableEvents(static::transactionalEventNameFor('commit', $eventName));
            $this->addObservableEvents(static::transactionalEventNameFor('rollback', $eventName));
        }
    }
}
