<?php

namespace SilvertipSoftware\LaravelSupport\Eloquent;

use Illuminate\Database\Eloquent\Model as EloquentModel;

class Model extends EloquentModel {
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

    protected function processRollback() {
        $this->rollbackSelfAndAutosavedRelations();
    }

    protected function processSave($options) {
        $this->validate();

        return $this->pushSelfAndAutosavedRelations($options);
    }
}
