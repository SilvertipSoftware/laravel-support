<?php

namespace SilvertipSoftware\LaravelSupport\Eloquent;

use Illuminate\Database\Eloquent\Concerns\HasEvents;
use Illuminate\Support\Fluent;
use Illuminate\Support\Str;
use SilvertipSoftware\LaravelSupport\Libs\StrongParameters\Parameters;

class FluentModel extends Fluent {
    use HasEvents,
        Naming,
        Translation,
        Validation;

    public function __construct($attrsOrParams = []) {
        $attrs = $attrsOrParams instanceof Parameters ? $attrsOrParams->toArray() : $attrsOrParams;

        parent::__construct($attrs);
    }

    public function get($key, $default = null) {
        $method = 'get' . Str::studly($key) . 'Attribute';

        if (method_exists($this, $method)) {
            return $this->{$method}();
        }

        return parent::get($key, $default);
    }

    public function offsetSet($key, $value): void {
        $method = 'set' . Str::studly($key) . 'Attribute';

        if (method_exists($this, $method)) {
            $this->{$method}($value);
        } else {
            parent::offsetSet($key, $value);
        }
    }

    public function toModel() {
        return $this;
    }

    protected function validateAutosavedRelations() {
    }

    protected function validationRulesToIgnoreForParentRelations() {
        return [];
    }
}
