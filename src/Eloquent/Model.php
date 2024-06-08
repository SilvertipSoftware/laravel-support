<?php

namespace SilvertipSoftware\LaravelSupport\Eloquent;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Model as EloquentModel;

class Model extends EloquentModel implements ModelContract {
    use AutosavesRelations,
        Naming,
        NestedAttributes,
        TransactionalAwareEvents,
        Transactions,
        Translation,
        Validation {
            AutosavesRelations::validateAutosavedRelations insteadof Validation;
            AutosavesRelations::validationRulesToIgnoreForParentRelations insteadof Validation;
    }

    protected $guarded = [];

    /**
     * @param array<string, mixed>|Arrayable<string, mixed> $attributes
     */
    public function __construct(array|Arrayable $attributes = []) {
        $attributes = $attributes instanceof Arrayable
            ? $attributes->toArray()
            : $attributes;

        parent::__construct($attributes);
    }

    public function toModel(): Model|FluentModel {
        return $this;
    }

    public function toParam(): int|string {
        return $this->getKey();
    }

    protected function processRollback(): void {
        $this->rollbackSelfAndAutosavedRelations();
    }

    /**
     * @param array<string, mixed> $options
     */
    protected function processSave(array $options): bool {
        $this->validate();

        return $this->pushSelfAndAutosavedRelations($options);
    }
}
