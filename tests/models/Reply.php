<?php

namespace App\Models;

use SilvertipSoftware\LaravelSupport\Eloquent\Model;

class Reply extends Model {

    public function __construct(array $attributes = []) {
        parent::__construct($attributes);

        $this->addValidationRules('title', 'required');
        $this->addValidationRules('content', ['required']);
    }

    public function addMethodRule() {
        $this->addValidationRules('title', 'call:title_start,ABC');
    }

    public function validateTitleStart($attr, $value, $params) {
        return strpos($this->title, $params[0]) === 0;
    }
}
