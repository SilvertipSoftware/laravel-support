<?php

namespace SilvertipSoftware\LaravelSupport\Eloquent;

use Illuminate\Contracts\Support\Arrayable;
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

    public function __construct($attrsOrParams = []) {
        $attrs = $attrsOrParams instanceof Arrayable
            ? $attrsOrParams->toArray()
            : $attrsOrParams;

        parent::__construct($attrs);
    }

    protected function processRollback() {
        $this->rollbackSelfAndAutosavedRelations();
    }

    protected function processSave($options) {
        $this->validate();

        return $this->pushSelfAndAutosavedRelations($options);
    }
}
